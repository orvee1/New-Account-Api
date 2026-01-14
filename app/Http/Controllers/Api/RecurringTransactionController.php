<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\RecurringTransaction;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecurringTransactionController extends Controller
{
    public function __construct(private JournalPostingService $posting) {}

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = RecurringTransaction::query()
            ->where('company_id', $companyId)
            ->with(['fromAccount', 'toAccount'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('transaction_number', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword);
            })
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_number' => 'nullable|string',
            'type' => 'required|string',
            'frequency' => 'nullable|string',
            'from_account_id' => 'nullable|exists:chart_accounts,id',
            'to_account_id' => 'nullable|exists:chart_accounts,id',
            'from_account_name' => 'nullable|string',
            'to_account_name' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'next_date' => 'nullable|date',
            'status' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['from_account_id'] = $validated['from_account_id'] ?? $this->resolveAccountId($validated['from_account_name'] ?? null);
        $validated['to_account_id'] = $validated['to_account_id'] ?? $this->resolveAccountId($validated['to_account_name'] ?? null);

        if (empty($validated['from_account_id']) || empty($validated['to_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['transaction_number'] = $validated['transaction_number'] ?? ('REC-' . time());
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $transaction = DB::transaction(function () use ($validated) {
            $transaction = RecurringTransaction::create($validated);
            $this->postRecurringJournal($transaction);
            return $transaction;
        });

        return response()->json($transaction->load(['fromAccount', 'toAccount']), 201);
    }

    public function show(RecurringTransaction $recurringTransaction)
    {
        $this->ensureCompanyAccess($recurringTransaction->company_id);
        return response()->json($recurringTransaction->load(['fromAccount', 'toAccount']));
    }

    public function update(Request $request, RecurringTransaction $recurringTransaction)
    {
        $this->ensureCompanyAccess($recurringTransaction->company_id);

        $validated = $request->validate([
            'transaction_number' => 'nullable|string',
            'type' => 'required|string',
            'frequency' => 'nullable|string',
            'from_account_id' => 'nullable|exists:chart_accounts,id',
            'to_account_id' => 'nullable|exists:chart_accounts,id',
            'from_account_name' => 'nullable|string',
            'to_account_name' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'next_date' => 'nullable|date',
            'status' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['from_account_id'] = $validated['from_account_id'] ?? $this->resolveAccountId($validated['from_account_name'] ?? null);
        $validated['to_account_id'] = $validated['to_account_id'] ?? $this->resolveAccountId($validated['to_account_name'] ?? null);

        if (empty($validated['from_account_id']) || empty($validated['to_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        DB::transaction(function () use ($recurringTransaction, $validated) {
            $recurringTransaction->update($validated);
            $this->postRecurringJournal($recurringTransaction);
        });

        return response()->json($recurringTransaction->load(['fromAccount', 'toAccount']));
    }

    public function destroy(RecurringTransaction $recurringTransaction)
    {
        $this->ensureCompanyAccess($recurringTransaction->company_id);
        DB::transaction(function () use ($recurringTransaction) {
            $this->posting->deleteEntries($recurringTransaction->company_id, RecurringTransaction::class, $recurringTransaction->id);
            $recurringTransaction->delete();
        });

        return response()->json(['message' => 'Recurring transaction deleted']);
    }

    private function postRecurringJournal(RecurringTransaction $transaction): void
    {
        $companyId = $transaction->company_id;
        $amount = (float) $transaction->amount;
        $entryDate = $transaction->next_date
            ?? $transaction->start_date
            ?? Carbon::today()->toDateString();

        $this->posting->deleteEntries($companyId, RecurringTransaction::class, $transaction->id);

        $this->posting->postEntry(
            companyId: $companyId,
            entryDate: $entryDate,
            description: "Recurring Transaction #{$transaction->transaction_number}",
            referenceType: RecurringTransaction::class,
            referenceId: $transaction->id,
            createdBy: $transaction->recorded_by,
            lines: [
                [
                    'account_id' => $transaction->to_account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => $transaction->description,
                ],
                [
                    'account_id' => $transaction->from_account_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => $transaction->description,
                ],
            ]
        );
    }

    private function resolveAccountId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return ChartAccount::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', $name)
            ->value('id');
    }

    private function ensureCompanyAccess(?int $companyId): void
    {
        if ($companyId !== auth()->user()->company_id) {
            abort(404, 'Not found');
        }
    }
}
