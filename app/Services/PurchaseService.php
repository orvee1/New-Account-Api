<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUom;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\InventoryLedger;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ChartAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService
{
    public function createBill(array $payload, int $userId): PurchaseBill
    {
        $companyId = Auth::guard('sanctum')->user()->company_id ?? 1;

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
            $product = Product::findOrFail($itemData['product_id']);
            $purchaseUom = ProductUom::findOrFail($itemData['purchase_uom_id']);
            $priceUom = ProductUom::findOrFail($itemData['price_uom_id']);

            $quantity = (float)$itemData['quantity'];
            $unitPrice = (float)$itemData['unit_price'];

            // Multi-UOM Calculation
            $qtyInBase = $quantity * (float)$purchaseUom->conversion_factor;
            $pricePerBaseUnit = $unitPrice / (float)$priceUom->conversion_factor;
            $unitPriceOriginal = $pricePerBaseUnit * (float)$purchaseUom->conversion_factor;
            $lineGrossAmount = $quantity * unitPriceOriginal;

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

    private function generateJournalEntries(PurchaseBill $bill, bool $isVatRegistered)
    {
        $companyId = $bill->company_id;
        
        $journalEntry = JournalEntry::create([
            'company_id'     => $companyId,
            'reference_id'   => $bill->id,
            'reference_type' => PurchaseBill::class,
            'entry_date'     => $bill->bill_date,
            'description'    => "Purchase Bill #{$bill->bill_no}",
            'created_by'     => Auth::id() ?? $bill->created_by,
        ]);

        $inventoryAccountId = config('coa_map.inventory') ?? $this->getAccountId('Inventory', 'asset', $companyId);
        $apAccountId = config('coa_map.accounts_payable') ?? $this->getAccountId('Accounts Payable', 'liability', $companyId);
        $vatReceivableAccountId = $this->getAccountId('Input VAT Receivable', 'asset', $companyId);
        $aitPayableAccountId = $this->getAccountId('AIT Payable', 'liability', $companyId);
        $purchaseDiscountAccountId = $bill->bill_discount_account_id ?? $this->getAccountId('Purchase Discount', 'income', $companyId);

        foreach ($bill->items as $item) {
            // DR Inventory
            $inventoryDr = $isVatRegistered ? $item->line_subtotal : ($item->line_subtotal + $item->vat_amount);
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'company_id'       => $companyId,
                'account_id'       => $inventoryAccountId,
                'debit'            => $inventoryDr,
                'credit'           => 0,
                'narration'        => "Inventory Purchase - {$item->product->name}",
            ]);

            // DR Input VAT (if registered)
            if ($isVatRegistered && $item->vat_amount > 0) {
                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'company_id'       => $companyId,
                    'account_id'       => $vatReceivableAccountId,
                    'debit'            => $item->vat_amount,
                    'credit'           => 0,
                    'narration'        => "Input VAT on Purchase - {$item->product->name}",
                ]);
            }

            // CR Accounts Payable
            // AP = (line_subtotal + vat_exclusive) - ait
            $itemApAmount = $item->line_subtotal + ($bill->vat_mode === 'exclusive' ? $item->vat_amount : 0) - $item->ait_amount;
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'company_id'       => $companyId,
                'account_id'       => $apAccountId,
                'debit'            => 0,
                'credit'           => $itemApAmount,
                'narration'        => "Payable to Vendor for {$item->product->name}",
            ]);

            // CR AIT Payable
            if ($item->ait_amount > 0) {
                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'company_id'       => $companyId,
                    'account_id'       => $aitPayableAccountId,
                    'debit'            => 0,
                    'credit'           => $item->ait_amount,
                    'narration'        => "AIT Payable on Purchase - {$item->product->name}",
                ]);
            }
        }

        // Bill level discount
        if ($bill->bill_discount_amt > 0) {
            // DR Accounts Payable
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'company_id'       => $companyId,
                'account_id'       => $apAccountId,
                'debit'            => $bill->bill_discount_amt,
                'credit'           => 0,
                'narration'        => "Bill Discount applied",
            ]);

            // CR Purchase Discount
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'company_id'       => $companyId,
                'account_id'       => $purchaseDiscountAccountId,
                'debit'            => 0,
                'credit'           => $bill->bill_discount_amt,
                'narration'        => "Purchase Discount income",
            ]);
        }
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
        JournalEntry::where('reference_id', $bill->id)
            ->where('reference_type', PurchaseBill::class)
            ->delete();
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

    private function getAccountId(string $name, string $type, int $companyId): int
    {
        $account = ChartAccount::where('company_id', $companyId)
            ->where('name', 'like', "%$name%")
            ->where('type', 'ledger')
            ->first();

        if (!$account) {
            $account = ChartAccount::create([
                'company_id' => $companyId,
                'name'       => $name,
                'type'       => 'ledger',
                'base_type'  => $type,
                'code'       => 'AUTO-' . rand(1000, 9999),
            ]);
        }

        return $account->id;
    }
}
