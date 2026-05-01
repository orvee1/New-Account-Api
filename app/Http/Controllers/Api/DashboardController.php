<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\PurchaseBill;
use App\Models\Receipt;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        $companyId = auth()->user()->company_id;

        $totalSales = (float) SalesInvoice::query()->where('company_id', $companyId)->sum('total_amount');
        $totalPurchases = (float) PurchaseBill::query()->where('company_id', $companyId)->sum('total_amount');

        $income = $this->sumByAccountPrefix($companyId, '4', false);
        $expense = $this->sumByAccountPrefix($companyId, '5', true);

        return response()->json([
            'total_sales' => round($totalSales, 2),
            'total_purchases' => round($totalPurchases, 2),
            'total_receivables' => round($this->balanceForPrefix($companyId, '1.1.4'), 2),
            'total_payables' => round($this->balanceForPrefix($companyId, '2.1.1', false), 2),
            'cash_bank_balance' => round($this->cashBankBalance($companyId), 2),
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'net_profit' => round($income - $expense, 2),
            'recent_invoices' => SalesInvoice::query()
                ->where('company_id', $companyId)
                ->latest('invoice_date')
                ->limit(5)
                ->get(['id', 'invoice_no', 'invoice_date', 'status', 'total_amount', 'customer_id']),
            'recent_bills' => PurchaseBill::query()
                ->where('company_id', $companyId)
                ->latest('bill_date')
                ->limit(5)
                ->get(['id', 'bill_no', 'bill_date', 'total_amount', 'vendor_id']),
            'recent_payments' => [
                'receipts' => Receipt::query()->where('company_id', $companyId)->latest('receipt_date')->limit(5)->get(),
                'payments' => Payment::query()->where('company_id', $companyId)->latest('payment_date')->limit(5)->get(),
                'sales_payments' => SalesPayment::query()->where('company_id', $companyId)->latest('payment_date')->limit(5)->get(),
            ],
        ]);
    }

    private function sumByAccountPrefix(int $companyId, string $prefix, bool $debitNormal): float
    {
        $totals = JournalLine::query()
            ->join('chart_accounts', 'chart_accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.company_id', $companyId)
            ->where('chart_accounts.code', 'like', $prefix.'%')
            ->selectRaw('SUM(journal_lines.debit) as debit_total, SUM(journal_lines.credit) as credit_total')
            ->first();

        $debit = (float) ($totals->debit_total ?? 0);
        $credit = (float) ($totals->credit_total ?? 0);

        return $debitNormal ? ($debit - $credit) : ($credit - $debit);
    }

    private function balanceForPrefix(int $companyId, string $prefix, bool $debitNormal = true): float
    {
        return $this->sumByAccountPrefix($companyId, $prefix, $debitNormal);
    }

    private function cashBankBalance(int $companyId): float
    {
        $totals = JournalLine::query()
            ->join('chart_accounts', 'chart_accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.company_id', $companyId)
            ->where(function ($q) {
                $q->where('chart_accounts.code', 'like', '1.1.1%')
                    ->orWhere('chart_accounts.code', 'like', '1.1.2%');
            })
            ->selectRaw('SUM(journal_lines.debit - journal_lines.credit) as balance')
            ->first();

        return (float) ($totals->balance ?? 0);
    }
}
