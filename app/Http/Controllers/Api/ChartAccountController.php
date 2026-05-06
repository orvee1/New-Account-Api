<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MergeAccountRequest;
use App\Http\Controllers\Controller;
use App\Exports\ChartAccountExport;
use App\Imports\ChartAccountImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChartAccountController extends Controller
{
    // GET /api/companies/{company}/chart-accounts
    public function index(Company $company)
    {
        $roots = ChartAccount::query()
            ->where('company_id', $company->id)
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $balancesByAccount = $this->balancesByAccount($company->id);
        $roots->each(function ($node) use ($balancesByAccount) {
            $this->applyBalanceRecursive($node, $balancesByAccount);
        });

        // সরাসরি array রিটার্ন করছি → তোমার পেস্ট করা JSON এর মতো
        return response()->json($roots);
    }

    // POST /api/companies/{company}/chart-accounts
    public function store(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'in:group,ledger'],
            'parent_id' => ['nullable', 'exists:chart_accounts,id'],
            'code'      => ['nullable', 'string', 'max:50'],
            'account_no'=> ['nullable', 'string', 'max:50'],
            'base_type' => ['nullable', 'in:asset,liability,equity,income,expense'],
            'normal_balance' => ['nullable', 'in:debit,credit'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_type' => ['nullable', 'in:debit,credit'],
            'opening_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // parent এই company-এর কিনা check
        $parent = null;
        if (!empty($data['parent_id'])) {
            $parent = ChartAccount::where('company_id', $company->id)
                ->where('id', $data['parent_id'])
                ->firstOrFail();

            // Ledger এর নিচে আর ledger/group allow না
            if ($parent->type === 'ledger') {
                return response()->json([
                    'message' => 'Ledger এর নিচে আর group/ledger তৈরি করা যাবে না।'
                ], 422);
            }

            // Ensure base_type matches parent if provided
            if (isset($data['base_type']) && $parent->base_type && $data['base_type'] !== $parent->base_type) {
                return response()->json([
                    'message' => "Base type must match parent's base type ({$parent->base_type})."
                ], 422);
            }
        }

        return DB::transaction(function () use ($data, $company) {
            $chart = new ChartAccount();
            $chart->company_id = $company->id;
            $chart->parent_id  = $data['parent_id'] ?? null;
            $chart->name       = $data['name'];
            $chart->type       = $data['type'];
            $chart->slug       = Str::slug($chart->name);
            $chart->code       = $data['code'] ?? $data['account_no'] ?? null;
            $chart->base_type  = $data['base_type'] ?? null;
            $chart->normal_balance = $data['normal_balance'] ?? null;
            $chart->opening_balance = $data['opening_balance'] ?? 0;
            $chart->opening_balance_type = $data['opening_balance_type'] ?? null;
            $chart->opening_date = $data['opening_date'] ?? null;
            $chart->is_active = $data['is_active'] ?? true;
            
            $chart->save();

            $openingBalance = (float) ($data['opening_balance'] ?? 0);
            if ($chart->type === 'ledger' && abs($openingBalance) > 0) {
                $this->createOpeningBalanceEntry(
                    companyId: $company->id,
                    account: $chart,
                    openingBalance: $openingBalance,
                    openingBalanceType: $data['opening_balance_type'] ?? null,
                    openingDate: $data['opening_date'] ?? null
                );
            }

            return response()->json($chart, 201);
        });
    }

    // GET /api/companies/{company}/chart-accounts/{chartAccount}
    public function show(Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        $chartAccount->load('parent', 'children');
        return response()->json($chartAccount);
    }

    // PUT /api/companies/{company}/chart-accounts/{chartAccount}
    public function update(Request $request, Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'code'       => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active'  => ['sometimes', 'boolean'],
            'base_type'  => ['sometimes', 'in:asset,liability,equity,income,expense'],
            'normal_balance' => ['sometimes', 'in:debit,credit'],
        ]);

        if (array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['updated_by'] = Auth::id();
        $chartAccount->update($data);

        return response()->json($chartAccount);
    }

    // DELETE /api/companies/{company}/chart-accounts/{chartAccount}
    public function destroy(Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        // Check if has children
        if ($chartAccount->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete an account that has sub-accounts.'
            ], 422);
        }

        // Check if has transactions
        if (JournalLine::where('account_id', $chartAccount->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete an account that has transactions. Deactivate it instead.'
            ], 422);
        }

        $chartAccount->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    // POST /api/companies/{company}/chart-accounts/merge
    public function merge(MergeAccountRequest $request, Company $company)
    {
        $source = ChartAccount::where('company_id', $company->id)->findOrFail($request->source_account_id);
        $target = ChartAccount::where('company_id', $company->id)->findOrFail($request->target_account_id);

        if ($source->type !== 'ledger' || $target->type !== 'ledger') {
            return response()->json(['message' => 'Both accounts must be ledger accounts to merge.'], 422);
        }

        if ($source->base_type !== $target->base_type) {
            return response()->json(['message' => 'Accounts must have the same base type to merge.'], 422);
        }

        DB::transaction(function () use ($source, $target) {
            // Move all journal lines
            JournalLine::where('account_id', $source->id)
                ->update(['account_id' => $target->id]);
            
            // Delete source account
            $source->delete();
        });

        return response()->json(['message' => 'Accounts merged successfully.']);
    }

    // GET /api/companies/{company}/chart-accounts/export
    public function export(Company $company)
    {
        return Excel::download(new ChartAccountExport($company->id), 'chart_of_accounts.xlsx');
    }

    // POST /api/companies/{company}/chart-accounts/import
    public function import(Request $request, Company $company)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new ChartAccountImport($company->id), $request->file('file'));

        return response()->json(['message' => 'Accounts imported successfully.']);
    }

    /**
     * Get a standard template for Chart of Accounts
     */
    public function getTemplate()
    {
        return response()->json([
            [
                'name' => 'Assets',
                'type' => 'group',
                'base_type' => 'asset',
                'normal_balance' => 'debit',
                'children' => [
                    ['name' => 'Current Assets', 'type' => 'group', 'children' => [
                        ['name' => 'Cash and Bank', 'type' => 'group', 'children' => [
                            ['name' => 'Petty Cash', 'type' => 'ledger'],
                            ['name' => 'Main Bank Account', 'type' => 'ledger'],
                        ]],
                        ['name' => 'Accounts Receivable', 'type' => 'ledger'],
                        ['name' => 'Inventory', 'type' => 'ledger'],
                    ]],
                    ['name' => 'Fixed Assets', 'type' => 'group', 'children' => [
                        ['name' => 'Furniture & Fixtures', 'type' => 'ledger'],
                        ['name' => 'Office Equipment', 'type' => 'ledger'],
                    ]],
                ]
            ],
            [
                'name' => 'Liabilities',
                'type' => 'group',
                'base_type' => 'liability',
                'normal_balance' => 'credit',
                'children' => [
                    ['name' => 'Current Liabilities', 'type' => 'group', 'children' => [
                        ['name' => 'Accounts Payable', 'type' => 'ledger'],
                        ['name' => 'Accrued Expenses', 'type' => 'ledger'],
                    ]],
                    ['name' => 'Long-term Liabilities', 'type' => 'group', 'children' => [
                        ['name' => 'Bank Loan', 'type' => 'ledger'],
                    ]],
                ]
            ],
            [
                'name' => 'Equity',
                'type' => 'group',
                'base_type' => 'equity',
                'normal_balance' => 'credit',
                'children' => [
                    ['name' => 'Owner\'s Capital', 'type' => 'ledger'],
                    ['name' => 'Retained Earnings', 'type' => 'ledger'],
                ]
            ],
            [
                'name' => 'Income',
                'type' => 'group',
                'base_type' => 'income',
                'normal_balance' => 'credit',
                'children' => [
                    ['name' => 'Sales Revenue', 'type' => 'ledger'],
                    ['name' => 'Service Income', 'type' => 'ledger'],
                    ['name' => 'Other Income', 'type' => 'ledger'],
                ]
            ],
            [
                'name' => 'Expenses',
                'type' => 'group',
                'base_type' => 'expense',
                'normal_balance' => 'debit',
                'children' => [
                    ['name' => 'Operating Expenses', 'type' => 'group', 'children' => [
                        ['name' => 'Salaries & Wages', 'type' => 'ledger'],
                        ['name' => 'Rent Expense', 'type' => 'ledger'],
                        ['name' => 'Utilities Expense', 'type' => 'ledger'],
                        ['name' => 'Office Supplies', 'type' => 'ledger'],
                    ]],
                    ['name' => 'Cost of Goods Sold', 'type' => 'ledger'],
                ]
            ]
        ]);
    }

    public function options(Request $request)
    {
        $companyId = $request->user()->company_id;
        $accounts = ChartAccount::where('company_id', $companyId)
            ->where('type', 'ledger')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($accounts);
    }


    private function balancesByAccount(int $companyId): array
    {
        $totals = JournalLine::query()
            ->select([
                'account_id',
                DB::raw('SUM(debit) as debit'),
                DB::raw('SUM(credit) as credit'),
            ])
            ->where('company_id', $companyId)
            ->groupBy('account_id')
            ->get();

        $accounts = ChartAccount::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $totals->pluck('account_id'))
            ->get(['id', 'code', 'normal_balance'])
            ->keyBy('id');

        $balances = [];
        foreach ($totals as $row) {
            $accountId = (int) $row->account_id;
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $account = $accounts->get($accountId);
            $balances[$accountId] = $this->isDebitNormal($account)
                ? ($debit - $credit)
                : ($credit - $debit);
        }

        return $balances;
    }

    private function applyBalanceRecursive(ChartAccount $node, array $balancesByAccount): float
    {
        $children = $node->childrenRecursive ?? collect();
        $childrenTotal = 0.0;

        foreach ($children as $child) {
            $childrenTotal += $this->applyBalanceRecursive($child, $balancesByAccount);
        }

        if ($node->type === 'ledger') {
            $balance = $balancesByAccount[$node->id] ?? 0.0;
        } else {
            $balance = $childrenTotal;
        }

        $node->setAttribute('balance', round($balance, 2));
        return $balance;
    }

    private function createOpeningBalanceEntry(
        int $companyId,
        ChartAccount $account,
        float $openingBalance,
        ?string $openingBalanceType = null,
        ?string $openingDate = null
    ): void {
        $amount = round($openingBalance, 2);
        if ($amount === 0.0) {
            return;
        }

        $openingAccount = $this->getOpeningBalanceAccount($companyId);
        if (!$openingAccount) {
            throw new \Exception('Opening balance account not found. Run ChartAccountSeeder or seed Opening Balances.');
        }

        $entryDate = $openingDate ? Carbon::parse($openingDate) : Carbon::today();

        $entry = JournalEntry::create([
            'company_id'     => $companyId,
            'reference_id'   => $account->id,
            'reference_type' => ChartAccount::class,
            'entry_date'     => $entryDate->toDateString(),
            'description'    => 'Opening Balance - ' . $account->name,
            'created_by'     => Auth::id(),
        ]);

        // If openingBalanceType is provided, use it. Otherwise, guess from account metadata.
        if ($openingBalanceType) {
            $isDebit = ($openingBalanceType === 'debit');
        } else {
            $isDebit = $this->isDebitNormal($account);
        }

        $positive = $amount > 0;
        $abs = abs($amount);

        if ($isDebit) {
            if ($positive) {
                $this->postLine($entry->id, $companyId, $account->id, $abs, 0, 'Opening balance');
                $this->postLine($entry->id, $companyId, $openingAccount->id, 0, $abs, 'Opening balance offset');
            } else {
                $this->postLine($entry->id, $companyId, $account->id, 0, $abs, 'Opening balance');
                $this->postLine($entry->id, $companyId, $openingAccount->id, $abs, 0, 'Opening balance offset');
            }
        } else {
            if ($positive) {
                $this->postLine($entry->id, $companyId, $account->id, 0, $abs, 'Opening balance');
                $this->postLine($entry->id, $companyId, $openingAccount->id, $abs, 0, 'Opening balance offset');
            } else {
                $this->postLine($entry->id, $companyId, $account->id, $abs, 0, 'Opening balance');
                $this->postLine($entry->id, $companyId, $openingAccount->id, 0, $abs, 'Opening balance offset');
            }
        }
    }

    private function postLine(
        int $entryId,
        int $companyId,
        int $accountId,
        float $debit,
        float $credit,
        string $narration
    ): void {
        JournalLine::create([
            'journal_entry_id' => $entryId,
            'company_id' => $companyId,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'narration' => $narration,
        ]);
    }

    private function getOpeningBalanceAccount(int $companyId): ?ChartAccount
    {
        $opening = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('slug', 'opening-balances')
            ->first();

        if ($opening) {
            return $opening;
        }

        $parent = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('slug', 'owners-capital')
            ->where('type', 'group')
            ->first();

        if (!$parent) {
            return null;
        }

        $opening = ChartAccount::create([
            'company_id' => $companyId,
            'parent_id' => $parent->id,
            'type' => 'ledger',
            'name' => 'Opening Balances',
            'slug' => 'opening-balances',
        ]);

        return $opening;
    }

    private function isDebitNormal(?ChartAccount $account): bool
    {
        if (!$account) {
            return true;
        }
        
        if ($account->normal_balance) {
            return $account->normal_balance === 'debit';
        }

        // Fallback to code if metadata is missing
        $code = $account->code ?? '';
        return str_starts_with($code, '1') || str_starts_with($code, '5');
    }

}
