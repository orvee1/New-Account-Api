<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'opening_balance' => ['nullable', 'numeric'],
            'opening_date' => ['nullable', 'date', 'before_or_equal:today'],
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
        }

        return DB::transaction(function () use ($data, $company) {
            $chart = new ChartAccount();
            $chart->company_id = $company->id;
            $chart->parent_id  = $data['parent_id'] ?? null;
            $chart->name       = $data['name'];
            $chart->type       = $data['type'];
            $chart->slug       = Str::slug($chart->name);
            $chart->code       = $data['code'] ?? $data['account_no'] ?? null;
            // code, sort_order, path, depth, created_by ?? model booted() ? handle ???
            $chart->save();

            $openingBalance = (float) ($data['opening_balance'] ?? 0);
            if ($chart->type === 'ledger' && abs($openingBalance) > 0) {
                $this->createOpeningBalanceEntry(
                    companyId: $company->id,
                    account: $chart,
                    openingBalance: $openingBalance,
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

        // চাইলে soft delete করতে পারো; এখন simple delete
        $chartAccount->delete();

        return response()->json(['message' => 'Deleted successfully']);
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
            ->get(['id', 'code'])
            ->keyBy('id');

        $balances = [];
        foreach ($totals as $row) {
            $accountId = (int) $row->account_id;
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $code = $accounts->get($accountId)?->code ?? '';
            $balances[$accountId] = $this->isDebitNormal($code)
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

        $isDebitNormal = $this->isDebitNormal($account->code ?? '');
        $positive = $amount > 0;
        $abs = abs($amount);

        if ($isDebitNormal) {
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

    private function isDebitNormal(string $code): bool
    {
        return str_starts_with($code, '1') || str_starts_with($code, '5');
    }

}
