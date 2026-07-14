<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\Product;
use App\Models\ProductUom;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesJournalEntry;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function createInvoice(array $payload, int $userId): SalesInvoice
    {
        $companyId = $this->currentCompanyId();

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            $invoice = SalesInvoice::create([
                'company_id' => $companyId,
                'customer_id' => $payload['customer_id'],
                'invoice_no' => $payload['invoice_no'] ?? 'INV-' . now()->timestamp,
                'invoice_date' => $payload['invoice_date'],
                'due_date' => Arr::get($payload, 'due_date'),
                'warehouse_id' => Arr::get($payload, 'warehouse_id'),
                'notes' => Arr::get($payload, 'notes'),
                'vat_mode' => $payload['vat_mode'],
                'invoice_discount_amt' => Arr::get($payload, 'invoice_discount_amt', 0),
                'invoice_discount_account_id' => Arr::get($payload, 'invoice_discount_account_id'),
                'status' => 'sent',
                'created_by' => $userId,
            ]);

            $totals = $this->processInvoiceItems($invoice, $payload['items'] ?? []);
            $this->applyTotals($invoice, $totals);
            $this->postInvoiceJournal($invoice, $userId);

            return $invoice->load(['customer', 'items.product', 'payments']);
        });
    }

    public function updateInvoice(SalesInvoice $invoice, array $payload, int $userId): SalesInvoice
    {
        $this->assertSameCompany($invoice);

        return DB::transaction(function () use ($invoice, $payload, $userId) {
            $this->reverseInvoiceImpact($invoice);

            $invoice->update([
                'customer_id' => $payload['customer_id'],
                'invoice_no' => $payload['invoice_no'] ?? $invoice->invoice_no,
                'invoice_date' => $payload['invoice_date'],
                'due_date' => Arr::get($payload, 'due_date'),
                'warehouse_id' => Arr::get($payload, 'warehouse_id'),
                'notes' => Arr::get($payload, 'notes'),
                'vat_mode' => $payload['vat_mode'],
                'invoice_discount_amt' => Arr::get($payload, 'invoice_discount_amt', 0),
                'invoice_discount_account_id' => Arr::get($payload, 'invoice_discount_account_id'),
                'status' => 'sent',
            ]);

            $totals = $this->processInvoiceItems($invoice, $payload['items'] ?? []);
            $this->applyTotals($invoice, $totals);
            $this->postInvoiceJournal($invoice, $userId);

            return $invoice->refresh()->load(['customer', 'items.product', 'payments']);
        });
    }

    public function deleteInvoice(SalesInvoice $invoice): void
    {
        $this->assertSameCompany($invoice);

        DB::transaction(function () use ($invoice) {
            $this->reverseInvoiceImpact($invoice);
            $invoice->delete();
        });
    }

    public function createReturn(SalesInvoice $invoice, array $payload): SalesReturn
    {
        $this->assertSameCompany($invoice);

        return DB::transaction(function () use ($invoice, $payload) {
            $userId = auth('sanctum')->id() ?? Auth::id();

            $return = SalesReturn::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'sales_invoice_id' => $invoice->id,
                'return_no' => Arr::get($payload, 'return_no', 'RET-' . now()->timestamp),
                'return_date' => Arr::get($payload, 'return_date', now()->toDateString()),
                'reason' => Arr::get($payload, 'reason'),
                'notes' => Arr::get($payload, 'notes'),
                'created_by' => $userId,
            ]);

            $totals = $this->attachReturnItems($return, $payload['items'] ?? []);

            $return->update([
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
            ]);

            return $return->load(['customer', 'items.product']);
        });
    }

    public function recordPayment(SalesInvoice $invoice, array $payload): SalesPayment
    {
        $this->assertSameCompany($invoice);

        return DB::transaction(function () use ($invoice, $payload) {
            $amount = round((float) $payload['amount'], 2);
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }

            $invoice->refresh();
            $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
            if ($amount > $remaining) {
                throw new Exception('Payment amount cannot exceed the invoice due amount.');
            }

            $payment = SalesPayment::create([
                'company_id' => $invoice->company_id,
                'sales_invoice_id' => $invoice->id,
                'payment_no' => Arr::get($payload, 'payment_no', 'PAY-' . now()->timestamp),
                'payment_date' => Arr::get($payload, 'payment_date', now()->toDateString()),
                'amount' => $amount,
                'payment_method' => Arr::get($payload, 'payment_method'),
                'reference_no' => Arr::get($payload, 'reference_no'),
                'notes' => Arr::get($payload, 'notes'),
                'status' => 'completed',
                'created_by' => auth('sanctum')->id() ?? Auth::id(),
            ]);

            $this->postPaymentJournal($payment, $invoice);
            $this->refreshInvoicePaymentStatus($invoice);

            return $payment;
        });
    }

    private function processInvoiceItems(SalesInvoice $invoice, array $items): array
    {
        $totals = [
            'subtotal' => 0.0,
            'trade_discount_amt' => 0.0,
            'line_discount_amt' => 0.0,
            'taxable_amount' => 0.0,
            'vat_amount' => 0.0,
            'ait_amount' => 0.0,
        ];

        foreach ($items as $itemData) {
            $product = Product::query()
                ->where('company_id', $invoice->company_id)
                ->lockForUpdate()
                ->findOrFail($itemData['product_id']);
            $saleUom = ProductUom::query()
                ->where('product_id', $product->id)
                ->findOrFail($itemData['sale_uom_id']);
            $priceUom = ProductUom::query()
                ->where('product_id', $product->id)
                ->findOrFail($itemData['price_uom_id']);

            $quantity = (float) $itemData['quantity'];
            $priceUomRate = (float) $itemData['unit_price'];

            $saleQtyInBase = round($quantity * (float) $saleUom->conversion_factor, 6);
            $pricePerBaseUnit = $priceUomRate / max((float) $priceUom->conversion_factor, 0.000001);
            $unitPriceOriginal = round($pricePerBaseUnit * (float) $saleUom->conversion_factor, 6);
            $lineGrossAmount = round($quantity * $unitPriceOriginal, 4);

            if ($this->isStockTracked($product) && (float) $product->current_stock_in_base_uom < $saleQtyInBase) {
                throw new Exception("Insufficient stock for product: {$product->name}.");
            }

            $tradeDiscountPct = (float) Arr::get($itemData, 'trade_discount_pct', 0);
            $tradeDiscountAmt = round($lineGrossAmount * ($tradeDiscountPct / 100), 4);
            $netUnitPrice = round($unitPriceOriginal * (1 - $tradeDiscountPct / 100), 4);
            $amountAfterTradeDiscount = $lineGrossAmount - $tradeDiscountAmt;

            $lineDiscountPct = (float) Arr::get($itemData, 'line_discount_pct', 0);
            $lineDiscountAmt = (float) Arr::get($itemData, 'line_discount_amt', 0);
            if ($lineDiscountAmt == 0.0 && $lineDiscountPct > 0) {
                $lineDiscountAmt = round($amountAfterTradeDiscount * ($lineDiscountPct / 100), 4);
            }

            $lineSubtotal = round($amountAfterTradeDiscount - $lineDiscountAmt, 4);
            $vatRate = (float) Arr::get($itemData, 'vat_rate', $product->vat_rate);
            $aitRate = (float) Arr::get($itemData, 'ait_rate', $product->ait_rate);

            $vatAmount = $invoice->vat_mode === 'inclusive'
                ? round($lineSubtotal * $vatRate / (100 + $vatRate), 4)
                : round($lineSubtotal * ($vatRate / 100), 4);
            $aitAmount = round($lineSubtotal * ($aitRate / 100), 4);

            $weightedAvgCost = (float) $product->weighted_avg_cost;
            $cogs = round($saleQtyInBase * $weightedAvgCost, 4);
            $grossProfit = round($lineSubtotal - $cogs, 4);

            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'sale_uom_id' => $saleUom->id,
                'price_uom_id' => $priceUom->id,
                'quantity' => $quantity,
                'quantity_in_sale_uom' => $quantity,
                'quantity_in_base_uom' => $saleQtyInBase,
                'unit_price' => $priceUomRate,
                'unit_price_original' => $unitPriceOriginal,
                'trade_discount_pct' => $tradeDiscountPct,
                'trade_discount_amt' => $tradeDiscountAmt,
                'net_unit_price' => $netUnitPrice,
                'line_gross_amount' => $lineGrossAmount,
                'line_discount_pct' => $lineDiscountPct,
                'line_discount_amt' => $lineDiscountAmt,
                'line_subtotal' => $lineSubtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'ait_rate' => $aitRate,
                'ait_amount' => $aitAmount,
                'weighted_avg_cost' => $weightedAvgCost,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'description' => Arr::get($itemData, 'description'),
                'line_total' => $lineSubtotal + ($invoice->vat_mode === 'exclusive' ? $vatAmount : 0),
            ]);

            if ($this->isStockTracked($product)) {
                $newStock = round((float) $product->current_stock_in_base_uom - $saleQtyInBase, 4);
                $product->update(['current_stock_in_base_uom' => $newStock]);

                InventoryLedger::create([
                    'product_id' => $product->id,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'sale',
                    'qty_in' => 0,
                    'qty_out' => $saleQtyInBase,
                    'qty_balance' => $newStock,
                    'unit_cost' => $weightedAvgCost,
                    'total_cost' => $cogs,
                    'new_weighted_avg_cost' => $weightedAvgCost,
                ]);
            }

            $totals['subtotal'] += $lineGrossAmount;
            $totals['trade_discount_amt'] += $tradeDiscountAmt;
            $totals['line_discount_amt'] += $lineDiscountAmt;
            $totals['taxable_amount'] += $lineSubtotal;
            $totals['vat_amount'] += $vatAmount;
            $totals['ait_amount'] += $aitAmount;
        }

        return array_map(fn ($value) => round($value, 4), $totals);
    }

    private function applyTotals(SalesInvoice $invoice, array $totals): void
    {
        $totalAmount = $totals['taxable_amount']
            + ($invoice->vat_mode === 'exclusive' ? $totals['vat_amount'] : 0)
            - $totals['ait_amount']
            - (float) $invoice->invoice_discount_amt;

        $invoice->update([
            'subtotal' => $totals['subtotal'],
            'trade_discount_amt' => $totals['trade_discount_amt'],
            'line_discount_amt' => $totals['line_discount_amt'],
            'taxable_amount' => $totals['taxable_amount'],
            'vat_amount' => $totals['vat_amount'],
            'ait_amount' => $totals['ait_amount'],
            'grand_total' => round($totalAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'discount_total' => round($totals['trade_discount_amt'] + $totals['line_discount_amt'] + (float) $invoice->invoice_discount_amt, 2),
            'tax_amount' => round($totals['vat_amount'], 2),
        ]);
    }

    private function postInvoiceJournal(SalesInvoice $invoice, int $userId): void
    {
        $invoice->loadMissing(['items.product', 'customer']);

        $revenueCredit = 0.0;
        $vatCredit = 0.0;
        $aitDebit = 0.0;
        $cogsDebit = 0.0;

        foreach ($invoice->items as $item) {
            $lineSubtotal = (float) $item->line_subtotal;
            $vatAmount = (float) $item->vat_amount;

            $revenueCredit += $invoice->vat_mode === 'inclusive'
                ? max(0, $lineSubtotal - $vatAmount)
                : $lineSubtotal;
            $vatCredit += $vatAmount;
            $aitDebit += (float) $item->ait_amount;
            $cogsDebit += (float) $item->cogs;
        }

        $receivableLine = [
            'debit' => round((float) $invoice->total_amount, 2),
            'credit' => 0,
            'narration' => "Sales invoice {$invoice->invoice_no}",
        ];

        if ($invoice->customer?->chart_account_id) {
            $receivableLine['account_id'] = $invoice->customer->chart_account_id;
        } else {
            $receivableLine['key'] = 'accounts_receivable';
        }

        $lines = [$receivableLine];

        if (round($aitDebit, 2) > 0) {
            $lines[] = [
                'key' => 'ait_receivable',
                'debit' => round($aitDebit, 2),
                'credit' => 0,
                'narration' => "AIT receivable {$invoice->invoice_no}",
            ];
        }

        if ((float) $invoice->invoice_discount_amt > 0) {
            $discountLine = [
                'debit' => round((float) $invoice->invoice_discount_amt, 2),
                'credit' => 0,
                'narration' => "Invoice discount {$invoice->invoice_no}",
            ];

            if ($invoice->invoice_discount_account_id) {
                $discountLine['account_id'] = $invoice->invoice_discount_account_id;
            } else {
                $discountLine['key'] = 'discount_allowed';
            }

            $lines[] = $discountLine;
        }

        $lines[] = [
            'key' => 'sales_revenue',
            'debit' => 0,
            'credit' => round($revenueCredit, 2),
            'narration' => "Sales revenue {$invoice->invoice_no}",
        ];

        if (round($vatCredit, 2) > 0) {
            $lines[] = [
                'key' => 'tax_payable',
                'debit' => 0,
                'credit' => round($vatCredit, 2),
                'narration' => "VAT payable {$invoice->invoice_no}",
            ];
        }

        if (round($cogsDebit, 2) > 0) {
            $lines[] = [
                'key' => 'cogs',
                'debit' => round($cogsDebit, 2),
                'credit' => 0,
                'narration' => "COGS {$invoice->invoice_no}",
            ];
            $lines[] = [
                'key' => 'inventory',
                'debit' => 0,
                'credit' => round($cogsDebit, 2),
                'narration' => "Inventory reduction {$invoice->invoice_no}",
            ];
        }

        $this->postingService->post([
            'company_id' => $invoice->company_id,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'entry_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
            'description' => "Sales Invoice #{$invoice->invoice_no}",
            'created_by' => $userId,
            'lines' => $lines,
        ]);
    }

    private function reverseInvoiceImpact(SalesInvoice $invoice): void
    {
        $invoice->loadMissing('items.product');

        foreach ($invoice->items as $item) {
            $product = Product::query()
                ->where('company_id', $invoice->company_id)
                ->lockForUpdate()
                ->find($item->product_id);

            if (! $product || ! $this->isStockTracked($product)) {
                continue;
            }

            $restoredQty = (float) $item->quantity_in_base_uom;
            $newStock = round((float) $product->current_stock_in_base_uom + $restoredQty, 4);
            $product->update(['current_stock_in_base_uom' => $newStock]);

            InventoryLedger::create([
                'product_id' => $product->id,
                'reference_id' => $invoice->id,
                'reference_type' => 'sale_reversal',
                'qty_in' => $restoredQty,
                'qty_out' => 0,
                'qty_balance' => $newStock,
                'unit_cost' => (float) $item->weighted_avg_cost,
                'total_cost' => (float) $item->cogs,
                'new_weighted_avg_cost' => (float) $product->weighted_avg_cost,
            ]);
        }

        $invoice->items()->delete();
        SalesJournalEntry::query()->where('invoice_id', $invoice->id)->delete();
        $this->postingService->deleteForReference($invoice->company_id, SalesInvoice::class, $invoice->id);
    }

    private function attachReturnItems(SalesReturn $return, array $items): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $discountAmount = (float) Arr::get($item, 'discount_amount', 0);
            $taxAmount = (float) Arr::get($item, 'tax_amount', 0);

            SalesReturnItem::create([
                'sales_return_id' => $return->id,
                'sales_invoice_item_id' => Arr::get($item, 'sales_invoice_item_id'),
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal - $discountAmount + $taxAmount,
            ]);

            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;
            $taxTotal += $taxAmount;
        }

        $totalAmount = $subtotal - $discountTotal + $taxTotal;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_amount' => round($taxTotal, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    private function postPaymentJournal(SalesPayment $payment, SalesInvoice $invoice): void
    {
        $this->postingService->post([
            'company_id' => $payment->company_id,
            'reference_type' => SalesPayment::class,
            'reference_id' => $payment->id,
            'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'description' => "Sales Payment #{$payment->payment_no}",
            'created_by' => $payment->created_by,
            'lines' => [
                [
                    'key' => $this->paymentMethodKey($payment->payment_method),
                    'debit' => (float) $payment->amount,
                    'credit' => 0,
                    'narration' => "Payment received for {$invoice->invoice_no}",
                ],
                [
                    'key' => 'accounts_receivable',
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                    'narration' => "Receivable cleared for {$invoice->invoice_no}",
                ],
            ],
        ]);
    }

    private function refreshInvoicePaymentStatus(SalesInvoice $invoice): void
    {
        $invoice->refresh();
        $paidAmount = round((float) $invoice->payments()->sum('amount'), 2);
        $totalAmount = round((float) $invoice->total_amount, 2);

        $status = 'unpaid';
        if ($paidAmount > 0 && $paidAmount < $totalAmount) {
            $status = 'partially_paid';
        } elseif ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $status = 'paid';
        }

        $invoice->update([
            'paid_amount' => $paidAmount,
            'status' => $status,
        ]);
    }

    private function paymentMethodKey(?string $paymentMethod): string
    {
        $method = strtolower((string) $paymentMethod);

        return str_contains($method, 'bank')
            || str_contains($method, 'cheque')
            || str_contains($method, 'check')
            || str_contains($method, 'transfer')
            || str_contains($method, 'online')
                ? 'bank'
                : 'cash';
    }

    private function currentCompanyId(): int
    {
        $companyId = Auth::guard('sanctum')->user()?->company_id ?? Auth::user()?->company_id;

        if (! $companyId) {
            throw new Exception('Authenticated company context is required.');
        }

        return (int) $companyId;
    }

    private function assertSameCompany(SalesInvoice $invoice): void
    {
        if ((int) $invoice->company_id !== $this->currentCompanyId()) {
            throw new Exception('The requested invoice does not belong to the authenticated company.');
        }
    }

    private function isStockTracked(Product $product): bool
    {
        return in_array($product->product_type, ['Stock', 'Combo'], true);
    }
}
