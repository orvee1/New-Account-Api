<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountReconciliation;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountReconciliationController extends Controller
{
    /**
     * Get unreconciled transactions for an account
     * GET /api/companies/{company}/accounts/{account}/transactions-to-reconcile
     */
    public function getTransactionsToReconcile(Request $request, Company $company, ChartAccount $account)
    {
        // Verify account belongs to company
        if ($account->company_id !== $company->id) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Default: last 7 days
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(7)->toDateString();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->toDateString();
        }

        // Get all transactions for the account
        $transactions = JournalLine::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->with('journalEntry')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate running balance
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
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'balance' => round($runningBalance, 2),
                'memo' => $line->memo,
                'is_reconciled' => $line->is_reconciled ?? false,
            ];
        });

        // Opening balance
        $openingBalance = $transactions->isEmpty() ? 0 : -$transactions->sum(function ($line) {
            return ($line->debit ?? 0) - ($line->credit ?? 0);
        });

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
                'closing_balance' => round($runningBalance, 2),
            ],
            'transactions' => $transactionData,
        ]);
    }

    /**
     * Submit reconciliation
     * POST /api/companies/{company}/accounts/{account}/reconcile
     */
    public function submitReconciliation(Request $request, Company $company, ChartAccount $account)
    {
        if ($account->company_id !== $company->id) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $validated = $request->validate([
            'beginning_balance' => 'required|numeric',
            'ending_balance' => 'required|numeric',
            'cleared_transactions' => 'required|array',
            'cleared_deposits' => 'required|numeric',
            'cleared_payments' => 'required|numeric',
            'difference' => 'required|numeric',
            'notes' => 'nullable|string',
            'reconciliation_date' => 'required|date',
        ]);

        try {
            $reconciliation = AccountReconciliation::create([
                'company_id' => $company->id,
                'account_id' => $account->id,
                'user_id' => Auth::id(),
                'beginning_balance' => $validated['beginning_balance'],
                'ending_balance' => $validated['ending_balance'],
                'cleared_deposits' => $validated['cleared_deposits'],
                'cleared_payments' => $validated['cleared_payments'],
                'difference' => $validated['difference'],
                'cleared_transactions' => $validated['cleared_transactions'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'reconciliation_date' => $validated['reconciliation_date'],
            ]);

            // Mark transactions as reconciled
            if (!empty($validated['cleared_transactions'])) {
                JournalLine::whereIn('id', $validated['cleared_transactions'])
                    ->update(['is_reconciled' => true]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation completed successfully',
                'data' => $reconciliation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving reconciliation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reconciliation history
     * GET /api/companies/{company}/accounts/{account}/reconciliation-history
     */
    public function getHistory(Request $request, Company $company, ChartAccount $account)
    {
        if ($account->company_id !== $company->id) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $reconciliations = AccountReconciliation::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->with('user:id,name,email')
            ->orderBy('reconciliation_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reconciliations,
        ]);
    }

    /**
     * Display reconciliation details
     */
    public function show(Company $company, ChartAccount $account, AccountReconciliation $reconciliation)
    {
        if ($account->company_id !== $company->id || $reconciliation->company_id !== $company->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $reconciliation->load('user:id,name,email', 'account:id,code,name');

        return response()->json([
            'success' => true,
            'data' => $reconciliation,
        ]);
    }

    /**
     * Delete reconciliation (revert)
     */
    public function destroy(Company $company, ChartAccount $account, AccountReconciliation $reconciliation)
    {
        if ($account->company_id !== $company->id || $reconciliation->company_id !== $company->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            // Mark transactions as unreconciled
            if ($reconciliation->cleared_transactions) {
                JournalLine::whereIn('id', $reconciliation->cleared_transactions)
                    ->update(['is_reconciled' => false]);
            }

            $reconciliation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation reverted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reverting reconciliation: ' . $e->getMessage(),
            ], 500);
        }
    }
}
