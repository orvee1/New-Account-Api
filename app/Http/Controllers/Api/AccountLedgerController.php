<?php

namespace App\Http\Controllers\Api;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class AccountLedgerController extends Controller
{
    /**
     * Get account details and ledger transactions
     * GET /api/companies/{company}/accounts/{account}/ledger
     */
    public function show(Request $request, Company $company, ChartAccount $account)
    {
        // Account এই company-এর কিনা verify করুন
        if ($account->company_id !== $company->id) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        // Query parameters থেকে date range নিন
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Default: last 30 days
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30)->toDateString();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->toDateString();
        }

        // এই account এর সকল transactions ফেচ করুন
        $transactions = JournalLine::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->with('journalEntry')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        // Running balance calculate করুন
        $runningBalance = 0;
        $transactionData = $transactions->map(function ($line) use (&$runningBalance) {
            $debit = (float) $line->debit ?? 0;
            $credit = (float) $line->credit ?? 0;
            $runningBalance += ($debit - $credit);

            return [
                'id' => $line->id,
                'date' => $line->journalEntry->entry_date ?? $line->created_at->toDateString(),
                'description' => $line->journalEntry->description ?? 'Journal Entry',
                'reference_id' => $line->journalEntry->reference_id,
                'reference_type' => $line->journalEntry->reference_type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($runningBalance, 2),
                'memo' => $line->memo,
            ];
        });

        // Opening balance (সকল লেনদেনের আগের ব্যালেন্স)
        $openingBalance = $transactions->isEmpty() ? 0 : -$transactions->sum(function ($line) {
            return ($line->debit ?? 0) - ($line->credit ?? 0);
        });

        // Closing balance
        $closingBalance = $runningBalance;

        return response()->json([
            'success' => true,
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'balances' => [
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => round($closingBalance, 2),
                'total_debit' => round($transactions->sum('debit'), 2),
                'total_credit' => round($transactions->sum('credit'), 2),
            ],
            'transactions' => $transactionData,
            'transaction_count' => $transactionData->count(),
        ]);
    }
}
