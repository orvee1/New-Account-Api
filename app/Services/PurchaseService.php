<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\ProductUom;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\InventoryLedger;
use App\Models\InventoryMovement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function createBill(array $payload, int $userId): PurchaseBill
    {
        $companyId = Auth::guard('sanctum')->user()?->company_id ?? Auth::user()?->company_id;

        if (! $companyId) {
            throw new Exception('Authenticated company context is required.');
        }

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            // 0. Get Settings
            $isVatRegistered = $this->getSetting($companyId, 'is_vat_registered', true);
            $status = Arr::get($payload, 'status', 'confirmed');

            // 1. Create Bill Header
            $bill = PurchaseBill::create([
                'company_id'               => $companyId,
                'vendor_id'                => $payload['vendor_id'],
                'bill_no'                  => $payload['bill_no'],
                'bill_date'                => $payload['bill_date'],
                'due_date'                 => Arr::get($payload, 'due_date'),
                'supplier_ref_no'          => Arr::get($payload, 'supplier_ref_no'),
                'notes'                    => Arr::get($payload, 'notes'),
                'vat_mode'                 => Arr::get($payload, 'vat_mode', 'exclusive'),
                'bill_discount_amt'        => Arr::get($payload, 'bill_discount_amt', 0),
                'bill_discount_account_id' => Arr::get($payload, 'bill_discount_account_id'),
                'created_by'               => $userId,
                'payment_status'           => 'unpaid',
                'status'                   => $status,
            ]);

            // 2. Process Line Items (Sequential for WAC only if NOT draft)
            $totals = $this->processBillItems($bill, $payload['items'], $isVatRegistered, $status !== 'draft');

            // 3. Update Bill Header with totals
            $bill->update([
                'subtotal'           => $totals['subtotal'],
                'trade_discount_amt' => $totals['trade_discount_amt'],
                'line_discount_amt'  => $totals['line_discount_amt'],
                'taxable_amount'     => $totals['taxable_amount'],
                'vat_amount'         => $totals['vat_amount'],
                'ait_amount'         => $totals['ait_amount'],
                'total_amount'       => $totals['taxable_amount'] + ($bill->vat_mode === 'exclusive' ? $totals['vat_amount'] : 0) - $totals['ait_amount'] - $bill->bill_discount_amt,
            ]);

            // 4. Generate Journal Entries (Only if NOT draft)
            if ($status !== 'draft') {
                $this->generateJournalEntries($bill, $isVatRegistered);
            }

            return $bill->load(['vendor', 'items.product']);
        });
    }

    private function processBillItems(PurchaseBill $bill, array $items, bool $isVatRegistered, bool $updateStock = true): array
    {
        $totals = [
            'subtotal'           => 0,
            'trade_discount_amt' => 0,
            'line_discount_amt'  => 0,
            'taxable_amount'     => 0,
            'vat_amount'         => 0,
            'ait_amount'         => 0,
        ];

        foreach ($items as $itemData) {
            $product = Product::query()
                ->where('company_id', $bill->company_id)
                ->lockForUpdate()
                ->findOrFail($itemData['product_id']);
            $purchaseUom = ProductUom::query()
                ->where('product_id', $product->id)
                ->findOrFail($itemData['purchase_uom_id']);
            $priceUom = ProductUom::query()
                ->where('product_id', $product->id)
                ->findOrFail($itemData['price_uom_id']);

            $quantity = (float)$itemData['quantity'];
            $unitPrice = (float)$itemData['unit_price'];

            // Multi-UOM Calculation
            $qtyInBase = $quantity * (float)$purchaseUom->conversion_factor;
            $pricePerBaseUnit = $unitPrice / (float)$priceUom->conversion_factor;
            $unitPriceOriginal = $pricePerBaseUnit * (float)$purchaseUom->conversion_factor;
            $lineGrossAmount = $quantity * $unitPriceOriginal;

            // Discounts
            $tradeDiscountPct = (float)Arr::get($itemData, 'trade_discount_pct', 0);
            $tradeDiscountAmt = $lineGrossAmount * ($tradeDiscountPct / 100);
            $amountAfterTradeDiscount = $lineGrossAmount - $tradeDiscountAmt;

            $lineDiscountPct = (float)Arr::get($itemData, 'line_discount_pct', 0);
            $lineDiscountAmt = (float)Arr::get($itemData, 'line_discount_amt', 0);
            if ($lineDiscountAmt == 0 && $lineDiscountPct > 0) {
                $lineDiscountAmt = $amountAfterTradeDiscount * ($lineDiscountPct / 100);
            }

            $lineSubtotal = $amountAfterTradeDiscount - $lineDiscountAmt;

            // VAT & AIT
            $vatRate = (float)Arr::get($itemData, 'vat_rate', 0);
            $aitRate = (float)Arr::get($itemData, 'ait_rate', 0);
            $vatAmount = 0;
            if ($bill->vat_mode === 'inclusive') {
                $vatAmount = $lineSubtotal * $vatRate / (100 + $vatRate);
            } else {
                $vatAmount = $lineSubtotal * ($vatRate / 100);
            }
            $aitAmount = $lineSubtotal * ($aitRate / 100);

            // Net Unit Cost for WAC
            $netUnitCost = 0;
            if ($qtyInBase > 0) {
                if ($isVatRegistered) {
                    $netUnitCost = $lineSubtotal / $qtyInBase;
                } else {
                    $netUnitCost = ($lineSubtotal + $vatAmount) / $qtyInBase;
                }
            }

            // Sequential WAC Calculation
            // Calculate WAC only if requested
            $oldAvg = 0;
            $newAvg = 0;

            if ($updateStock) {
                $oldStock = (float)$product->current_stock_in_base_uom;
                $oldAvg = (float)$product->weighted_avg_cost;
                $newStock = $oldStock + $qtyInBase;
                $newAvg = $oldAvg;

                if ($newStock > 0) {
                    $newAvg = (($oldStock * $oldAvg) + ($qtyInBase * $netUnitCost)) / $newStock;
                }

                // Update Product
                $product->update([
                    'current_stock_in_base_uom' => $newStock,
                    'weighted_avg_cost'         => $newAvg,
                ]);

                // Inventory Ledger
                InventoryLedger::create([
                    'product_id'            => $product->id,
                    'reference_id'          => $bill->id,
                    'reference_type'        => 'purchase',
                    'qty_in'                => $qtyInBase,
                    'qty_out'               => 0,
                    'qty_balance'           => $newStock,
                    'unit_cost'             => $netUnitCost,
                    'total_cost'            => $qtyInBase * $netUnitCost,
                    'new_weighted_avg_cost' => $newAvg,
                ]);
            }

            // Save Item
            PurchaseBillItem::create([
                'company_id'               => $bill->company_id,
                'purchase_bill_id'         => $bill->id,
                'product_id'               => $product->id,
                'purchase_uom_id'          => $purchaseUom->id,
                'price_uom_id'             => $priceUom->id,
                'quantity_in_purchase_uom' => $quantity,
                'quantity_in_base_uom'     => $qtyInBase,
                'unit_price_original'      => $unitPriceOriginal,
                'trade_discount_pct'       => $tradeDiscountPct,
                'trade_discount_amt'       => $tradeDiscountAmt,
                'net_unit_price'           => $unitPriceOriginal * (1 - $tradeDiscountPct / 100),
                'line_gross_amount'        => $lineGrossAmount,
                'line_discount_pct'        => $lineDiscountPct,
                'line_discount_amt'        => $lineDiscountAmt,
                'line_subtotal'            => $lineSubtotal,
                'vat_rate'                 => $vatRate,
                'vat_amount'               => $vatAmount,
                'ait_rate'                 => $aitRate,
                'ait_amount'               => $aitAmount,
                'net_unit_cost'            => $netUnitCost,
                'weighted_avg_cost_before' => $oldAvg,
                'weighted_avg_cost_after'  => $newAvg,
                'line_total'               => $lineSubtotal + ($bill->vat_mode === 'exclusive' ? $vatAmount : 0),
            ]);

            // Accumulate Totals
            $totals['subtotal']           += $lineGrossAmount;
            $totals['trade_discount_amt'] += $tradeDiscountAmt;
            $totals['line_discount_amt']  += $lineDiscountAmt;
            $totals['taxable_amount']     += $lineSubtotal;
            $totals['vat_amount']         += $vatAmount;
            $totals['ait_amount']         += $aitAmount;
        }

        return $totals;
    }

    private function generateJournalEntries(PurchaseBill $bill, bool $isVatRegistered): void
    {
        $bill->loadMissing('items.product');

        $inventoryDebit = 0.0;
        $inputVatDebit = 0.0;
        $aitCredit = 0.0;

        foreach ($bill->items as $item) {
            $lineSubtotal = (float) $item->line_subtotal;
            $vatAmount = (float) $item->vat_amount;

            if ($isVatRegistered) {
                $inventoryDebit += $bill->vat_mode === 'inclusive'
                    ? max(0, $lineSubtotal - $vatAmount)
                    : $lineSubtotal;
                $inputVatDebit += $vatAmount;
            } else {
                $inventoryDebit += $bill->vat_mode === 'inclusive'
                    ? $lineSubtotal
                    : $lineSubtotal + $vatAmount;
            }

            $aitCredit += (float) $item->ait_amount;
        }

        $lines = [];
        if (round($inventoryDebit, 2) > 0) {
            $lines[] = [
                'key' => 'inventory',
                'debit' => round($inventoryDebit, 2),
                'credit' => 0,
                'narration' => "Inventory purchase {$bill->bill_no}",
            ];
        }

        if (round($inputVatDebit, 2) > 0) {
            $lines[] = [
                'key' => 'input_vat_receivable',
                'debit' => round($inputVatDebit, 2),
                'credit' => 0,
                'narration' => "Input VAT on purchase {$bill->bill_no}",
            ];
        }

        if ((float) $bill->total_amount > 0) {
            $lines[] = [
                'key' => 'accounts_payable',
                'debit' => 0,
                'credit' => round((float) $bill->total_amount, 2),
                'narration' => "Vendor payable {$bill->bill_no}",
            ];
        }

        if (round($aitCredit, 2) > 0) {
            $lines[] = [
                'key' => 'ait_payable',
                'debit' => 0,
                'credit' => round($aitCredit, 2),
                'narration' => "AIT payable {$bill->bill_no}",
            ];
        }

        if ((float) $bill->bill_discount_amt > 0) {
            $discountLine = [
                'debit' => 0,
                'credit' => round((float) $bill->bill_discount_amt, 2),
                'narration' => "Purchase discount {$bill->bill_no}",
            ];

            if ($bill->bill_discount_account_id) {
                $discountLine['account_id'] = $bill->bill_discount_account_id;
            } else {
                $discountLine['key'] = 'purchase_discount';
            }

            $lines[] = $discountLine;
        }

        $this->postingService->post([
            'company_id' => $bill->company_id,
            'reference_type' => PurchaseBill::class,
            'reference_id' => $bill->id,
            'entry_date' => $bill->bill_date?->toDateString() ?? now()->toDateString(),
            'description' => "Purchase Bill #{$bill->bill_no}",
            'created_by' => Auth::id() ?? $bill->created_by,
            'lines' => $lines,
        ]);
    }

    public function updateBill(PurchaseBill $bill, array $payload): PurchaseBill
    {
        if ($bill->payment_status !== 'unpaid') {
            throw new Exception("Only unpaid bills can be updated.");
        }

        return DB::transaction(function () use ($bill, $payload) {
            // 1. Reverse old stock and journal entries
            $this->reverseBillImpact($bill);

            $status = Arr::get($payload, 'status', $bill->status);

            // 2. Update Header
            $bill->update([
                'vendor_id'                => $payload['vendor_id'],
                'bill_no'                  => $payload['bill_no'],
                'bill_date'                => $payload['bill_date'],
                'due_date'                 => Arr::get($payload, 'due_date'),
                'supplier_ref_no'          => Arr::get($payload, 'supplier_ref_no'),
                'notes'                    => Arr::get($payload, 'notes'),
                'vat_mode'                 => Arr::get($payload, 'vat_mode', 'exclusive'),
                'bill_discount_amt'        => Arr::get($payload, 'bill_discount_amt', 0),
                'bill_discount_account_id' => Arr::get($payload, 'bill_discount_account_id'),
                'status'                   => $status,
            ]);

            // 3. Process new items (updateStock only if new status is confirmed)
            $isVatRegistered = $this->getSetting($bill->company_id, 'is_vat_registered', true);
            $totals = $this->processBillItems($bill, $payload['items'], $isVatRegistered, $status !== 'draft');

            // 4. Update totals
            $bill->update([
                'subtotal'           => $totals['subtotal'],
                'trade_discount_amt' => $totals['trade_discount_amt'],
                'line_discount_amt'  => $totals['line_discount_amt'],
                'taxable_amount'     => $totals['taxable_amount'],
                'vat_amount'         => $totals['vat_amount'],
                'ait_amount'         => $totals['ait_amount'],
                'total_amount'       => $totals['taxable_amount'] + ($bill->vat_mode === 'exclusive' ? $totals['vat_amount'] : 0) - $totals['ait_amount'] - $bill->bill_discount_amt,
            ]);

            // 5. Re-generate journal entries (Only if confirmed)
            if ($status !== 'draft') {
                $this->generateJournalEntries($bill, $isVatRegistered);
            }

            return $bill->refresh();
        });
    }

    public function deleteBill(PurchaseBill $bill): void
    {
        DB::transaction(function () use ($bill) {
            $this->reverseBillImpact($bill);
            $bill->delete();
        });
    }

    private function reverseBillImpact(PurchaseBill $bill)
    {
        // 1. Reverse Stock and WAC for each item (Only if confirmed)
        if ($bill->status !== 'draft') {
            foreach ($bill->items as $item) {
                $product = $item->product;
                
                $oldStock = (float)$product->current_stock_in_base_uom;
                $oldAvg = (float)$product->weighted_avg_cost;
                $oldTotalValue = $oldStock * $oldAvg;

                $removedQty = (float)$item->quantity_in_base_uom;
                $removedCost = (float)$item->net_unit_cost;
                $removedValue = $removedQty * $removedCost;

                $newStock = $oldStock - $removedQty;
                $newAvg = $oldAvg;
                
                if ($newStock > 0) {
                    $newAvg = ($oldTotalValue - $removedValue) / $newStock;
                } elseif ($newStock == 0) {
                    $newAvg = 0;
                }
                // If newStock < 0, we keep the oldAvg

                $product->update([
                    'current_stock_in_base_uom' => $newStock,
                    'weighted_avg_cost'         => $newAvg,
                ]);

                // Insert reversal in inventory ledger
                InventoryLedger::create([
                    'product_id'            => $product->id,
                    'reference_id'          => $bill->id,
                    'reference_type'        => 'purchase_reversal',
                    'qty_in'                => 0,
                    'qty_out'               => $removedQty,
                    'qty_balance'           => $newStock,
                    'unit_cost'             => $removedCost,
                    'total_cost'            => $removedValue,
                    'new_weighted_avg_cost' => $newAvg,
                ]);
            }
        }

        // 2. Delete old items
        $bill->items()->delete();

        // 3. Delete old journal entries
        $this->postingService->deleteForReference($bill->company_id, PurchaseBill::class, $bill->id);
    }

    public function createReturn(array $payload, int $userId): PurchaseReturn
    {
        $companyId = Auth::guard('sanctum')->user()?->company_id ?? Auth::user()?->company_id;

        if (! $companyId) {
            throw new Exception('Authenticated company context is required.');
        }

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            $return = PurchaseReturn::create([
                'company_id'   => $companyId,
                'vendor_id'    => $payload['vendor_id'],
                'return_no'    => $payload['return_no'],
                'return_date'  => $payload['return_date'],
                'warehouse_id' => Arr::get($payload, 'warehouse_id'),
                'notes'        => Arr::get($payload, 'notes'),
                'tax_amount'   => (float) Arr::get($payload, 'tax_amount', 0),
                'created_by'   => $userId,
            ]);

            $totals = $this->attachReturnItemsAndMovements($return, $payload['items'], $userId);

            $return->update([
                'subtotal'       => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'total_amount'   => $totals['subtotal'] - $totals['discount_total'] + (float) $return->tax_amount,
                'updated_by'     => $userId,
            ]);

            return $return->load(['vendor', 'items.product']);
        });
    }

    private function attachReturnItemsAndMovements(PurchaseReturn $return, array $items, int $userId): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;

        foreach ($items as $item) {
            $product = Product::query()
                ->where('company_id', $return->company_id)
                ->lockForUpdate()
                ->findOrFail($item['product_id']);
            $qtyUnit = ProductUnit::query()->findOrFail($item['qty_unit_id']);
            $rateUnit = ProductUnit::query()->findOrFail($item['rate_unit_id']);

            $qtyBase = round((float) $item['qty'] * (float) $qtyUnit->factor, 6);
            $rateBase = round(((float) $item['rate_per_unit'] / max((float) $rateUnit->factor, 0.000001)), 6);
            $lineSubtotal = round($qtyBase * $rateBase, 4);

            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            if ($discountAmount <= 0 && $discountPercent > 0) {
                $discountAmount = round($lineSubtotal * ($discountPercent / 100), 4);
            }

            $batchId = null;
            $batchNo = Arr::get($item, 'batch_no');
            $manufacturedAt = Arr::get($item, 'manufactured_at');
            $expiredAt = Arr::get($item, 'expired_at');

            if ($batchNo) {
                $batch = ProductBatch::firstOrCreate(
                    ['company_id' => $return->company_id, 'product_id' => $product->id, 'batch_no' => $batchNo],
                    ['manufactured_at' => $manufacturedAt, 'expired_at' => $expiredAt]
                );
                $batchId = $batch->id;
            }

            $warehouseId = Arr::get($item, 'warehouse_id', $return->warehouse_id);

            PurchaseReturnItem::create([
                'company_id' => $return->company_id,
                'purchase_return_id' => $return->id,
                'product_id' => $product->id,
                'qty_unit_id' => $qtyUnit->id,
                'qty' => $item['qty'],
                'qty_base' => $qtyBase,
                'rate_unit_id' => $rateUnit->id,
                'rate_per_unit' => $item['rate_per_unit'],
                'rate_per_base' => $rateBase,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'line_subtotal' => $lineSubtotal,
                'line_total' => round($lineSubtotal - $discountAmount, 4),
                'warehouse_id' => $warehouseId,
                'batch_id' => $batchId,
                'batch_no' => $batchNo,
                'manufactured_at' => $manufacturedAt,
                'expired_at' => $expiredAt,
            ]);

            if (in_array($product->product_type, ['Stock', 'Combo'], true)) {
                InventoryMovement::create([
                    'company_id' => $return->company_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'batch_id' => $batchId,
                    'quantity_base' => -1 * $qtyBase,
                    'unit_cost_base' => $rateBase,
                    'document_type' => 'purchase_return',
                    'document_id' => $return->id,
                    'meta' => ['return_no' => $return->return_no],
                    'created_by' => $userId,
                ]);
            }

            $subtotal = round($subtotal + $lineSubtotal, 4);
            $discountTotal = round($discountTotal + $discountAmount, 4);
        }

        return ['subtotal' => $subtotal, 'discount_total' => $discountTotal];
    }

    private function getSetting(int $companyId, string $key, $default)
    {
        $val = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->value('value');
        
        if ($val === null) return $default;
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

}
