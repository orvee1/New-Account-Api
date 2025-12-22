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

            // Update invoice paid amount and status
            $paidAmount = $invoice->paid_amount + $payment->amount;
            $invoice->update([
                'paid_amount' => $paidAmount,
                'status'      => $paidAmount >= $invoice->total_amount ? 'paid' : 'partially_paid',
            ]);

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

        return compact('subtotal', 'discountTotal', 'taxTotal', 'totalAmount');
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

        return compact('subtotal', 'discountTotal', 'taxTotal', 'totalAmount');
    }
}
