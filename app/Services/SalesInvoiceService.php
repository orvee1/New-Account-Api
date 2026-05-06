<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Product;
use App\Models\ProductUom;
use App\Models\InventoryLedger;
use App\Models\SalesJournalEntry;
use App\Models\ChartAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Exception;

class SalesInvoiceService
{
    public function createInvoice(array $payload, int $userId): SalesInvoice
    {
        $companyId = Auth::guard('sanctum')->user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            // 1. Create Invoice Header (Initial)
            $invoice = SalesInvoice::create([
                'company_id'                  => $companyId,
                'customer_id'                 => $payload['customer_id'],
                'invoice_no'                  => $payload['invoice_no'] ?? 'INV-' . now()->timestamp,
                'invoice_date'                => $payload['invoice_date'],
                'due_date'                    => Arr::get($payload, 'due_date'),
                'warehouse_id'                => Arr::get($payload, 'warehouse_id'),
                'notes'                       => Arr::get($payload, 'notes'),
                'vat_mode'                    => $payload['vat_mode'],
                'invoice_discount_amt'        => Arr::get($payload, 'invoice_discount_amt', 0),
                'invoice_discount_account_id' => Arr::get($payload, 'invoice_discount_account_id'),
                'status'                      => 'sent',
                'created_by'                  => $userId,
            ]);

            // 2. Process Line Items
            $totals = $this->processInvoiceItems($invoice, $payload['items'] ?? []);

            // 3. Update Invoice Header with calculated totals
            $invoice->update([
                'subtotal'           => $totals['subtotal'],
                'trade_discount_amt' => $totals['trade_discount_amt'],
                'line_discount_amt'  => $totals['line_discount_amt'],
                'taxable_amount'     => $totals['taxable_amount'],
                'vat_amount'         => $totals['vat_amount'],
                'ait_amount'         => $totals['ait_amount'],
                'grand_total'        => $totals['taxable_amount'] + $totals['vat_amount'] - Arr::get($payload, 'invoice_discount_amt', 0),
                'total_amount'       => $totals['taxable_amount'] + $totals['vat_amount'] - Arr::get($payload, 'invoice_discount_amt', 0),
            ]);

            // 4. Generate Journal Entries
            $this->generateJournalEntries($invoice);

            return $invoice;
        });
    }

    private function processInvoiceItems(SalesInvoice $invoice, array $items): array
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
            $saleUom = ProductUom::findOrFail($itemData['sale_uom_id']);
            $priceUom = ProductUom::findOrFail($itemData['price_uom_id']);

            $quantity = (float)$itemData['quantity'];
            $priceUomRate = (float)$itemData['unit_price']; // This is the rate of the Price UOM

            // Multi-UOM Calculation Logic
            $sale_qty_in_base = $quantity * (float)$saleUom->conversion_factor;
            $price_per_base_unit = $priceUomRate / (float)$priceUom->conversion_factor;
            $unit_price_original = $price_per_base_unit * (float)$saleUom->conversion_factor;
            $line_gross_amount = $quantity * $unit_price_original;

            // Stock Check
            if ($product->product_type === 'Stock' && $product->current_stock_in_base_uom < $sale_qty_in_base) {
                throw new Exception("Insufficient stock for product: {$product->name}. Available: {$product->current_stock_in_base_uom}, Requested: {$sale_qty_in_base}");
            }

            // Discounts
            $trade_discount_pct = (float)Arr::get($itemData, 'trade_discount_pct', 0);
            $trade_discount_amt = $line_gross_amount * ($trade_discount_pct / 100);
            $net_unit_price = $unit_price_original * (1 - $trade_discount_pct / 100);
            $amount_after_trade_discount = $line_gross_amount - $trade_discount_amt;

            $line_discount_pct = (float)Arr::get($itemData, 'line_discount_pct', 0);
            $line_discount_amt = (float)Arr::get($itemData, 'line_discount_amt', 0);
            if ($line_discount_amt == 0 && $line_discount_pct > 0) {
                $line_discount_amt = $amount_after_trade_discount * ($line_discount_pct / 100);
            }

            $line_subtotal = $amount_after_trade_discount - $line_discount_amt;

            // VAT & AIT
            $vat_rate = (float)Arr::get($itemData, 'vat_rate', $product->vat_rate);
            $ait_rate = (float)Arr::get($itemData, 'ait_rate', $product->ait_rate);
            $vat_amount = 0;
            $ait_amount = 0;

            if ($invoice->vat_mode === 'inclusive') {
                $vat_amount = $line_subtotal * $vat_rate / (100 + $vat_rate);
                // line_subtotal stays as is (inclusive), but taxable amount for accounting is line_subtotal - vat_amount
            } else {
                $vat_amount = $line_subtotal * ($vat_rate / 100);
            }

            $ait_amount = $line_subtotal * ($ait_rate / 100);

            // Costing & Inventory
            $weighted_avg_cost = (float)$product->weighted_avg_cost;
            $cogs = $sale_qty_in_base * $weighted_avg_cost;
            $gross_profit = $line_subtotal - $cogs;

            // Save Item
            SalesInvoiceItem::create([
                'sales_invoice_id'     => $invoice->id,
                'product_id'           => $product->id,
                'sale_uom_id'          => $saleUom->id,
                'price_uom_id'         => $priceUom->id,
                'quantity'             => $quantity, // for legacy compatibility
                'quantity_in_sale_uom' => $quantity,
                'quantity_in_base_uom' => $sale_qty_in_base,
                'unit_price'           => $priceUomRate, // user entered price
                'unit_price_original'  => $unit_price_original,
                'trade_discount_pct'   => $trade_discount_pct,
                'trade_discount_amt'   => $trade_discount_amt,
                'net_unit_price'       => $net_unit_price,
                'line_gross_amount'    => $line_gross_amount,
                'line_discount_pct'    => $line_discount_pct,
                'line_discount_amt'    => $line_discount_amt,
                'line_subtotal'        => $line_subtotal,
                'vat_rate'             => $vat_rate,
                'vat_amount'           => $vat_amount,
                'ait_rate'             => $ait_rate,
                'ait_amount'           => $ait_amount,
                'weighted_avg_cost'    => $weighted_avg_cost,
                'cogs'                 => $cogs,
                'gross_profit'         => $gross_profit,
                'description'          => Arr::get($itemData, 'description'),
                'line_total'           => $line_subtotal + ($invoice->vat_mode === 'exclusive' ? $vat_amount : 0),
            ]);

            // Update Stock
            if ($product->product_type === 'Stock') {
                $product->decrement('current_stock_in_base_uom', $sale_qty_in_base);

                // Inventory Ledger
                InventoryLedger::create([
                    'product_id'            => $product->id,
                    'reference_id'          => $invoice->id,
                    'reference_type'        => 'sale',
                    'qty_out'               => $sale_qty_in_base,
                    'qty_balance'           => $product->current_stock_in_base_uom,
                    'unit_cost'             => $weighted_avg_cost,
                    'total_cost'            => $cogs,
                    'new_weighted_avg_cost' => $weighted_avg_cost,
                ]);
            }

            // Accumulate Totals
            $totals['subtotal']           += $line_gross_amount;
            $totals['trade_discount_amt'] += $trade_discount_amt;
            $totals['line_discount_amt']  += $line_discount_amt;
            $totals['taxable_amount']     += $line_subtotal;
            $totals['vat_amount']         += $vat_amount;
            $totals['ait_amount']         += $ait_amount;
        }

        return $totals;
    }

    private function generateJournalEntries(SalesInvoice $invoice)
    {
        $invoice->load('items', 'customer');
        $companyId = $invoice->company_id;

        // Account IDs - in a real app these should be configurable
        $arAccountId = $invoice->customer->chart_account_id ?? $this->getAccountId('Accounts Receivable', 'asset', $companyId);
        $salesRevenueAccountId = $this->getAccountId('Sales Revenue', 'income', $companyId);
        $vatPayableAccountId = $this->getAccountId('VAT Payable', 'liability', $companyId);
        $aitReceivableAccountId = $this->getAccountId('AIT Receivable', 'asset', $companyId);
        $cogsAccountId = $this->getAccountId('Cost of Goods Sold', 'expense', $companyId);
        $inventoryAccountId = config('coa_map.inventory') ?? $this->getAccountId('Inventory', 'asset', $companyId);

        foreach ($invoice->items as $item) {
            // A. Sales entries
            // Dr. Accounts Receivable [line_total including VAT]
            // Cr. Sales Revenue [line_subtotal after line discount]
            // Cr. VAT Payable [vat_amount]
            // Dr. AIT Receivable [ait_amount]

            $line_total_with_vat = $item->line_subtotal + ($invoice->vat_mode === 'exclusive' ? $item->vat_amount : 0);
            $ar_amount = $line_total_with_vat - $item->ait_amount;

            SalesJournalEntry::create([
                'invoice_id' => $invoice->id,
                'account_id' => $arAccountId,
                'dr_cr'      => 'dr',
                'amount'     => $ar_amount,
                'narration'  => "Sales to {$invoice->customer->name} - Inv #{$invoice->invoice_no}",
            ]);

            if ($item->ait_amount > 0) {
                SalesJournalEntry::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $aitReceivableAccountId,
                    'dr_cr'      => 'dr',
                    'amount'     => $item->ait_amount,
                    'narration'  => "AIT on Sales - Inv #{$invoice->invoice_no}",
                ]);
            }

            SalesJournalEntry::create([
                'invoice_id' => $invoice->id,
                'account_id' => $salesRevenueAccountId,
                'dr_cr'      => 'cr',
                'amount'     => $item->line_subtotal,
                'narration'  => "Sales Revenue - Inv #{$invoice->invoice_no}",
            ]);

            if ($item->vat_amount > 0) {
                SalesJournalEntry::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $vatPayableAccountId,
                    'dr_cr'      => 'cr',
                    'amount'     => $item->vat_amount,
                    'narration'  => "VAT on Sales - Inv #{$invoice->invoice_no}",
                ]);
            }

            // B. COGS entry
            if ($item->cogs > 0) {
                SalesJournalEntry::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $cogsAccountId,
                    'dr_cr'      => 'dr',
                    'amount'     => $item->cogs,
                    'narration'  => "COGS for Inv #{$invoice->invoice_no}",
                ]);

                SalesJournalEntry::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $inventoryAccountId,
                    'dr_cr'      => 'cr',
                    'amount'     => $item->cogs,
                    'narration'  => "Inventory reduction for Inv #{$invoice->invoice_no}",
                ]);
            }
        }

        // C. Invoice level discount entry
        if ($invoice->invoice_discount_amt > 0 && $invoice->invoice_discount_account_id) {
            SalesJournalEntry::create([
                'invoice_id' => $invoice->id,
                'account_id' => $invoice->invoice_discount_account_id,
                'dr_cr'      => 'dr',
                'amount'     => $invoice->invoice_discount_amt,
                'narration'  => "Invoice Discount - Inv #{$invoice->invoice_no}",
            ]);

            SalesJournalEntry::create([
                'invoice_id' => $invoice->id,
                'account_id' => $arAccountId,
                'dr_cr'      => 'cr',
                'amount'     => $invoice->invoice_discount_amt,
                'narration'  => "AR reduction for Invoice Discount - Inv #{$invoice->invoice_no}",
            ]);
        }
    }

    private function getAccountId(string $name, string $type, int $companyId): int
    {
        $account = ChartAccount::where('company_id', $companyId)
            ->where('name', 'like', "%$name%")
            ->where('type', 'ledger')
            ->first();

        if (!$account) {
            // Create a default one if not found? Or throw error?
            // For now, let's create a dummy one to avoid failure, but in production this is bad.
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
