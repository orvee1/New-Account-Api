<?php

namespace App\Services;

use App\Models\SalesPayment;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesPaymentService
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function recordPayment(array $payload, int $userId): SalesPayment
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()
                ->where('company_id', $companyId)
                ->findOrFail($payload['sales_invoice_id']);

            // Create Payment
            $payment = SalesPayment::create([
                'company_id'       => $companyId,
                'sales_invoice_id' => $invoice->id,
                'payment_no'       => $payload['payment_no'] ?? 'PAY-' . now()->timestamp,
                'payment_date'     => $payload['payment_date'],
                'amount'           => $payload['amount'],
                'payment_method'   => Arr::get($payload, 'payment_method'),
                'reference_no'     => Arr::get($payload, 'reference_no'),
                'notes'            => Arr::get($payload, 'notes'),
                'status'           => 'completed',
                'created_by'       => $userId,
            ]);

            $assetKey = $this->paymentMethodKey($payment->payment_method);
            $this->postingService->post([
                'company_id' => $companyId,
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

            $paidAmount = (float) $invoice->payments()->sum('amount');
            $status = 'unpaid';
            if ($paidAmount > 0 && $paidAmount < (float) $invoice->total_amount) {
                $status = 'partially_paid';
            } elseif ($paidAmount >= (float) $invoice->total_amount && (float) $invoice->total_amount > 0) {
                $status = 'paid';
            }
            $invoice->update([
                'paid_amount' => $paidAmount,
                'status'      => $status,
            ]);

            return $payment;
        });
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
