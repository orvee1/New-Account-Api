<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchasePayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function createBill(array $payload, int $userId): PurchaseBill
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {

        //--------------------------------
        // 1. Create Bill
        //--------------------------------
            /** @var PurchaseBill $bill */
            $bill = PurchaseBill::create([
                'company_id'        => $companyId,
                'vendor_id'         => $payload['vendor_id'],
                'bill_no'           => $payload['bill_no'],
                'bill_date'         => $payload['bill_date'],
                'due_date'          => Arr::get($payload, 'due_date'),
                'warehouse_id'      => Arr::get($payload, 'warehouse_id'),
                'notes'             => Arr::get($payload, 'notes'),
                'tax_amount'        => (float) Arr::get($payload, 'tax_amount', 0),
                'created_by'        => $userId,
            ]);

            //--------------------------------
            // 2. Insert Items + Stock Movements
            //--------------------------------
            $totals = $this->attachBillItemsAndMovements(
                $bill,
                $payload['items'],
                $userId
            );

            //--------------------------------
            // 3. Update Totals
            //--------------------------------
            $totalAmount = $totals['subtotal']
                - $totals['discount_total']
                + (float) $bill->tax_amount;

            $bill->update([
                'subtotal'        => $totals['subtotal'],
                'discount_total'  => $totals['discount_total'],
                'total_amount'    => $totalAmount,
                'updated_by'      => $userId,
            ]);

            //--------------------------------
            // 4. Payment Breakdown
            //--------------------------------
            $cash   = Arr::get($payload, 'payments.cash', 0);
            $bank   = Arr::get($payload, 'payments.bank', 0);
            $credit = $totalAmount - ($cash + $bank);

            PurchasePayment::create([
                'purchase_bill_id'  => $bill->id,
                'cash_amount'       => $cash,
                'bank_amount'       => $bank,
                'credit_amount'     => $credit,
            ]);

            //--------------------------------
            // 5. Create Journal Entry (Header)
            //--------------------------------
            $journalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'reference'  => "PB-" . $bill->id,
                'entry_date' => $payload['bill_date'],
                'description' => "Purchase Bill #{$bill->bill_no}",
                'created_by' => $userId,
            ]);

            //--------------------------------
            // 6. Journal Lines (DR/CR Lines)
            //--------------------------------

            // INVENTORY DR
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'company_id'       => $companyId,
                // 'account_id'       => coa('inventory'),
                'account_id'       => config('coa_map.inventory'),
                'debit'            => $totalAmount,
                'credit'           => 0,
                'narration'        => 'Purchase Inventory',
            ]);

            // CASH CR
            if ($cash > 0) {
                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'company_id'       => $companyId,
                    // 'account_id'       => coa('cash'),
                    'account_id'       => config('coa_map.cash'),
                    'debit'            => 0,
                    'credit'           => $cash,
                    'narration'        => 'Cash Payment',
                ]);
            }

            // BANK CR
            if ($bank > 0) {
                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'company_id'       => $companyId,
                    // 'account_id'       => coa('bank'),
                    'account_id'       => config('coa_map.bank'),
                    'debit'            => 0,
                    'credit'           => $bank,
                    'narration'        => 'Bank Payment',
                ]);
            }

            // ACCOUNTS PAYABLE / VENDOR CREDIT CR
            if ($credit > 0) {
                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'company_id'       => $companyId,
                    // 'account_id'       => coa('accounts_payable'),
                    'account_id'       => config('coa_map.accounts_payable'),
                    'debit'            => 0,
                    'credit'           => $credit,
                    'narration'        => 'Vendor Credit',
                ]);
            }

            //--------------------------------
            // 7. Return fresh Bill
            //--------------------------------
            return $bill->load(['vendor', 'items.product', 'payment']);
        });
    }

    public function createReturn(array $payload, int $userId): PurchaseReturn
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            /** @var PurchaseReturn $ret */
            $ret = PurchaseReturn::create([
                'company_id'   => $companyId,
                'vendor_id'    => $payload['vendor_id'],
                'return_no'    => $payload['return_no'],
                'return_date'  => $payload['return_date'],
                'warehouse_id' => Arr::get($payload, 'warehouse_id'),
                'notes'        => Arr::get($payload, 'notes'),
                'tax_amount'   => (float) Arr::get($payload, 'tax_amount', 0),
                'created_by'   => $userId,
            ]);

            $totals = $this->attachReturnItemsAndMovements($ret, $payload['items'], $userId);

            $ret->update([
                'subtotal'       => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'total_amount'   => $totals['subtotal'] - $totals['discount_total'] + (float)$ret->tax_amount,
                'updated_by'     => $userId,
            ]);

            return $ret->load(['vendor', 'items.product']);
        });
    }

    /* ---------------------- internal helpers ---------------------- */

    private function attachBillItemsAndMovements(PurchaseBill $bill, array $items, int $userId): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;

        foreach ($items as $i) {
            $product   = Product::query()->findOrFail($i['product_id']);
            $qtyUnit   = ProductUnit::query()->findOrFail($i['qty_unit_id']);
            $rateUnit  = ProductUnit::query()->findOrFail($i['rate_unit_id']);

            $qtyBase   = round((float)$i['qty'] * (float)$qtyUnit->factor, 6);
            $rateBase  = round(((float)$i['rate_per_unit'] / max((float)$rateUnit->factor, 0.000001)), 6);
            $lineSub   = round($qtyBase * $rateBase, 4);

            $discAmt   = (float)($i['discount_amount'] ?? 0);
            $discPct   = (float)($i['discount_percent'] ?? 0);
            if ($discAmt <= 0 && $discPct > 0) {
                $discAmt = round($lineSub * ($discPct / 100), 4);
            }
            $lineTotal = round($lineSub - $discAmt, 4);

            // batch ensure
            $batchId = null;
            $batchNo = Arr::get($i, 'batch_no');
            $mfg     = Arr::get($i, 'manufactured_at');
            $exp     = Arr::get($i, 'expired_at');

            if ($batchNo) {
                $batch = ProductBatch::firstOrCreate(
                    ['company_id' => $bill->company_id, 'product_id' => $product->id, 'batch_no' => $batchNo],
                    ['manufactured_at' => $mfg, 'expired_at' => $exp]
                );
                // if new info comes later and empty previously, we can fill
                if (!$batch->manufactured_at && $mfg) $batch->manufactured_at = $mfg;
                if (!$batch->expired_at && $exp) $batch->expired_at = $exp;
                $batch->save();
                $batchId = $batch->id;
            }

            $warehouseId = Arr::get($i, 'warehouse_id', $bill->warehouse_id);

            PurchaseBillItem::create([
                'company_id'     => $bill->company_id,
                'purchase_bill_id' => $bill->id,
                'product_id'     => $product->id,

                'qty_unit_id'    => $qtyUnit->id,
                'qty'            => $i['qty'],
                'qty_base'       => $qtyBase,

                'rate_unit_id'   => $rateUnit->id,
                'rate_per_unit'  => $i['rate_per_unit'],
                'rate_per_base'  => $rateBase,

                'discount_percent' => $discPct,
                'discount_amount' => $discAmt,

                'line_subtotal'  => $lineSub,
                'line_total'     => $lineTotal,

                'warehouse_id'   => $warehouseId,
                'batch_id'       => $batchId,
                'batch_no'       => $batchNo,
                'manufactured_at' => $mfg,
                'expired_at'     => $exp,
            ]);

            // Inventory In (+)
            if ($this->isStockTracked($product)) {
                InventoryMovement::create([
                    'company_id'    => $bill->company_id,
                    'product_id'    => $product->id,
                    'warehouse_id'  => $warehouseId,
                    'batch_id'      => $batchId,
                    'quantity_base' => $qtyBase,              // +ve
                    'unit_cost_base' => $rateBase,
                    'document_type' => 'purchase_bill',
                    'document_id'   => $bill->id,
                    'meta'          => ['bill_no' => $bill->bill_no],
                    'created_by'    => $userId,
                ]);
            }

            $subtotal     = round($subtotal + $lineSub, 4);
            $discountTotal = round($discountTotal + $discAmt, 4);
        }

        return ['subtotal' => $subtotal, 'discount_total' => $discountTotal];
    }

    private function attachReturnItemsAndMovements(PurchaseReturn $ret, array $items, int $userId): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;

        foreach ($items as $i) {
            $product   = Product::query()->findOrFail($i['product_id']);
            $qtyUnit   = ProductUnit::query()->findOrFail($i['qty_unit_id']);
            $rateUnit  = ProductUnit::query()->findOrFail($i['rate_unit_id']);

            $qtyBase   = round((float)$i['qty'] * (float)$qtyUnit->factor, 6);
            $rateBase  = round(((float)$i['rate_per_unit'] / max((float)$rateUnit->factor, 0.000001)), 6);
            $lineSub   = round($qtyBase * $rateBase, 4);

            $discAmt   = (float)($i['discount_amount'] ?? 0);
            $discPct   = (float)($i['discount_percent'] ?? 0);
            if ($discAmt <= 0 && $discPct > 0) {
                $discAmt = round($lineSub * ($discPct / 100), 4);
            }
            $lineTotal = round($lineSub - $discAmt, 4);

            $batchId = null;
            $batchNo = Arr::get($i, 'batch_no');
            $mfg     = Arr::get($i, 'manufactured_at');
            $exp     = Arr::get($i, 'expired_at');

            if ($batchNo) {
                $batch = ProductBatch::firstOrCreate(
                    ['company_id' => $ret->company_id, 'product_id' => $product->id, 'batch_no' => $batchNo],
                    ['manufactured_at' => $mfg, 'expired_at' => $exp]
                );
                if (!$batch->manufactured_at && $mfg) $batch->manufactured_at = $mfg;
                if (!$batch->expired_at && $exp) $batch->expired_at = $exp;
                $batch->save();
                $batchId = $batch->id;
            }

            $warehouseId = Arr::get($i, 'warehouse_id', $ret->warehouse_id);

            PurchaseReturnItem::create([
                'company_id'        => $ret->company_id,
                'purchase_return_id' => $ret->id,
                'product_id'        => $product->id,

                'qty_unit_id'       => $qtyUnit->id,
                'qty'               => $i['qty'],
                'qty_base'          => $qtyBase,

                'rate_unit_id'      => $rateUnit->id,
                'rate_per_unit'     => $i['rate_per_unit'],
                'rate_per_base'     => $rateBase,

                'discount_percent'  => $discPct,
                'discount_amount'   => $discAmt,

                'line_subtotal'     => $lineSub,
                'line_total'        => $lineTotal,

                'warehouse_id'      => $warehouseId,
                'batch_id'          => $batchId,
                'batch_no'          => $batchNo,
                'manufactured_at'   => $mfg,
                'expired_at'        => $exp,
            ]);

            // Inventory Out (-)
            if ($this->isStockTracked($product)) {
                InventoryMovement::create([
                    'company_id'    => $ret->company_id,
                    'product_id'    => $product->id,
                    'warehouse_id'  => $warehouseId,
                    'batch_id'      => $batchId,
                    'quantity_base' => -1 * $qtyBase,         // -ve
                    'unit_cost_base' => $rateBase,
                    'document_type' => 'purchase_return',
                    'document_id'   => $ret->id,
                    'meta'          => ['return_no' => $ret->return_no],
                    'created_by'    => $userId,
                ]);
            }

            $subtotal     = round($subtotal + $lineSub, 4);
            $discountTotal = round($discountTotal + $discAmt, 4);
        }

        return ['subtotal' => $subtotal, 'discount_total' => $discountTotal];
    }

    private function isStockTracked(Product $product): bool
    {
        return in_array($product->product_type, ['Stock', 'Combo']); // Service/Non-stock typically not tracked
    }
}
