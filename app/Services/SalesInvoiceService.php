<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesInvoiceService
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function createInvoice(array $payload, int $userId): SalesInvoice
    {
        $companyId = Auth::guard('sanctum')->user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            // Create Invoice
            $invoice = SalesInvoice::create([
                'company_id'      => $companyId,
                'customer_id'     => $payload['customer_id'],
                'sales_order_id'  => Arr::get($payload, 'sales_order_id'),
                'invoice_no'      => $payload['invoice_no'] ?? 'INV-' . now()->timestamp,
                'invoice_date'    => $payload['invoice_date'],
                'due_date'        => Arr::get($payload, 'due_date'),
                'warehouse_id'    => Arr::get($payload, 'warehouse_id'),
                'notes'           => Arr::get($payload, 'notes'),
                'status'          => 'sent',
                'created_by'      => $userId,
            ]);

            // Add Items
            $totals = $this->attachInvoiceItems($invoice, $payload['items'] ?? []);

            // Update totals
            $invoice->update([
                'subtotal'        => $totals['subtotal'],
                'discount_total'  => $totals['discount_total'],
                'tax_amount'      => $totals['tax_amount'],
                'total_amount'    => $totals['total_amount'],
            ]);

            $this->postInvoiceJournal($invoice, $userId);

            return $invoice;
        });
    }

    public function updateInvoice(SalesInvoice $invoice, array $payload, int $userId): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $payload, $userId) {
            // Delete existing items
            $invoice->items()->delete();

            // Update invoice
            $invoice->update([
                'customer_id'     => $payload['customer_id'],
                'invoice_date'    => $payload['invoice_date'],
                'due_date'        => Arr::get($payload, 'due_date'),
                'notes'           => Arr::get($payload, 'notes'),
                'updated_by'      => $userId,
            ]);

            // Add new items
            $totals = $this->attachInvoiceItems($invoice, $payload['items'] ?? []);

            // Update totals
            $invoice->update([
                'subtotal'        => $totals['subtotal'],
                'discount_total'  => $totals['discount_total'],
                'tax_amount'      => $totals['tax_amount'],
                'total_amount'    => $totals['total_amount'],
            ]);

            $this->postInvoiceJournal($invoice, $userId);

            return $invoice;
        });
    }

    public function createReturn(SalesInvoice $invoice, array $payload): SalesReturn
    {
        return DB::transaction(function () use ($invoice, $payload) {
            $companyId = $invoice->company_id;
            $userId = auth('sanctum')->user()->id;

            // Create Return
            $return = SalesReturn::create([
                'company_id'       => $companyId,
                'customer_id'      => $invoice->customer_id,
                'sales_invoice_id' => $invoice->id,
                'return_no'        => 'RET-' . now()->timestamp,
                'return_date'      => now()->toDateString(),
                'reason'           => Arr::get($payload, 'reason'),
                'notes'            => Arr::get($payload, 'notes'),
                'created_by'       => $userId,
            ]);

            // Add returned items
            $totals = $this->attachReturnItems($return, $payload['items'] ?? []);

            // Update return totals
            $return->update([
                'subtotal'        => $totals['subtotal'],
                'discount_total'  => $totals['discount_total'],
                'tax_amount'      => $totals['tax_amount'],
                'total_amount'    => $totals['total_amount'],
            ]);

            return $return;
        });
    }

    public function recordPayment(SalesInvoice $invoice, array $payload): SalesPayment
    {
        return DB::transaction(function () use ($invoice, $payload) {
            $companyId = $invoice->company_id;
            $userId = auth('sanctum')->user()->id;

            // Create Payment
            $payment = SalesPayment::create([
                'company_id'         => $companyId,
                'sales_invoice_id'   => $invoice->id,
                'payment_no'         => 'PAY-' . now()->timestamp,
                'payment_date'       => $payload['payment_date'] ?? now()->toDateString(),
                'amount'             => $payload['amount'],
                'payment_method'     => Arr::get($payload, 'payment_method'),
                'reference_no'       => Arr::get($payload, 'reference_no'),
                'notes'              => Arr::get($payload, 'notes'),
                'status'             => 'completed',
                'created_by'         => $userId,
            ]);

            $this->postPaymentJournal($payment, $invoice, $userId);
            $this->refreshInvoicePaymentStatus($invoice);

            return $payment;
        });
    }

    private function attachInvoiceItems(SalesInvoice $invoice, array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $lineTotal = (int)Arr::get($item, 'quantity', 0) * (float)Arr::get($item, 'unit_price', 0);
            $discountAmount = (float)Arr::get($item, 'discount_amount', 0);
            $taxAmount = (float)Arr::get($item, 'tax_amount', 0);

            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'product_id'       => Arr::get($item, 'product_id'),
                'quantity'         => Arr::get($item, 'quantity', 0),
                'unit_price'       => Arr::get($item, 'unit_price', 0),
                'discount_amount'  => $discountAmount,
                'tax_amount'       => $taxAmount,
                'line_total'       => $lineTotal - $discountAmount + $taxAmount,
                'description'      => Arr::get($item, 'description'),
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

    private function attachReturnItems(SalesReturn $return, array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $lineTotal = (int)$item['quantity'] * (float)$item['unit_price'];
            $discountAmount = (float)Arr::get($item, 'discount_amount', 0);
            $taxAmount = (float)Arr::get($item, 'tax_amount', 0);

            SalesReturnItem::create([
                'sales_return_id'      => $return->id,
                'sales_invoice_item_id' => Arr::get($item, 'sales_invoice_item_id'),
                'product_id'           => $item['product_id'],
                'quantity'             => $item['quantity'],
                'unit_price'           => $item['unit_price'],
                'discount_amount'      => $discountAmount,
                'tax_amount'           => $taxAmount,
                'line_total'           => $lineTotal - $discountAmount + $taxAmount,
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

    private function postInvoiceJournal(SalesInvoice $invoice, int $userId): void
    {
        $lines = [
            [
                'key' => 'accounts_receivable',
                'debit' => (float) $invoice->total_amount,
                'credit' => 0,
                'narration' => "Sales invoice {$invoice->invoice_no}",
            ],
            [
                'key' => 'sales_revenue',
                'debit' => 0,
                'credit' => (float) $invoice->subtotal,
                'narration' => "Sales revenue {$invoice->invoice_no}",
            ],
        ];

        if ((float) $invoice->discount_total > 0) {
            $lines[] = [
                'key' => 'discount_allowed',
                'debit' => (float) $invoice->discount_total,
                'credit' => 0,
                'narration' => "Discount on {$invoice->invoice_no}",
            ];
        }

        if ((float) $invoice->tax_amount > 0) {
            $lines[] = [
                'key' => 'tax_payable',
                'debit' => 0,
                'credit' => (float) $invoice->tax_amount,
                'narration' => "Tax on {$invoice->invoice_no}",
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

    private function postPaymentJournal(SalesPayment $payment, SalesInvoice $invoice, int $userId): void
    {
        $assetKey = $this->paymentMethodKey($payment->payment_method);

        $this->postingService->post([
            'company_id' => $payment->company_id,
            'reference_type' => SalesPayment::class,
            'reference_id' => $payment->id,
            'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'description' => "Sales Payment #{$payment->payment_no}",
            'created_by' => $userId,
            'lines' => [
                [
                    'key' => $assetKey,
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

        $paidAmount = (float) $invoice->payments()->sum('amount');
        $status = 'unpaid';

        if ($paidAmount > 0 && $paidAmount < (float) $invoice->total_amount) {
            $status = 'partially_paid';
        } elseif ($paidAmount >= (float) $invoice->total_amount && (float) $invoice->total_amount > 0) {
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
}
