<?php

namespace App\Services;

use App\Models\SalesPayment;
use App\Services\AccountMappingService;
use App\Services\JournalPostingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesPaymentService
{
    public function __construct(
        private AccountMappingService $accountMapping,
        private JournalPostingService $posting
    ) {}

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

            $this->postSalesPaymentJournal($payment, $userId);

            return $payment;
        });
    }

    private function postSalesPaymentJournal(SalesPayment $payment, int $userId): void
    {
        $companyId = $payment->company_id;
        $accountsReceivable = $this->accountMapping->accountsReceivable($companyId);
        $cashAccount = $this->accountMapping->cash($companyId);
        $bankAccount = $this->accountMapping->bank($companyId);

        if (!$accountsReceivable || !$cashAccount || !$bankAccount) {
            throw new \Exception('Required chart accounts are missing. Run ChartAccountSeeder.');
        }

        $method = strtolower((string) $payment->payment_method);
        $debitAccount = in_array($method, ['bank', 'cheque', 'online'], true)
            ? $bankAccount
            : $cashAccount;

        $amount = (float) $payment->amount;

        $this->posting->postEntry(
            companyId: $companyId,
            entryDate: $payment->payment_date,
            description: "Sales Payment #{$payment->payment_no}",
            referenceType: SalesPayment::class,
            referenceId: $payment->id,
            createdBy: $userId,
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Customer Payment',
                ],
                [
                    'account_id' => $accountsReceivable->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Accounts Receivable',
                ],
            ]
        );
    }
}
