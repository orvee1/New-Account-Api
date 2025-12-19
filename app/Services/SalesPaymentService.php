<?php

namespace App\Services;

use App\Models\SalesPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesPaymentService
{
    public function recordPayment(array $payload, int $userId): SalesPayment
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            // Create Payment
            $payment = SalesPayment::create([
                'company_id'       => $companyId,
                'sales_invoice_id' => $payload['sales_invoice_id'],
                'payment_no'       => $payload['payment_no'] ?? 'PAY-' . now()->timestamp,
                'payment_date'     => $payload['payment_date'],
                'amount'           => $payload['amount'],
                'payment_method'   => Arr::get($payload, 'payment_method'),
                'reference_no'     => Arr::get($payload, 'reference_no'),
                'notes'            => Arr::get($payload, 'notes'),
                'status'           => 'completed',
                'created_by'       => $userId,
            ]);

            // Update invoice paid amount and status
            $invoice = $payment->salesInvoice;
            $paidAmount = $invoice->paid_amount + $payment->amount;
            $invoice->update([
                'paid_amount' => $paidAmount,
                'status'      => $paidAmount >= $invoice->total_amount ? 'paid' : 'partially_paid',
            ]);

            return $payment;
        });
    }
}
