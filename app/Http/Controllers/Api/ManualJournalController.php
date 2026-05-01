<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\ManualJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class ManualJournalController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = ManualJournal::query()
            ->where('company_id', $companyId)
            ->with(['debitAccount', 'creditAccount'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('journal_number', 'like', $keyword)
                    ->orWhere('reference_number', 'like', $keyword);
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('journal_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('journal_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
            'journal_date' => 'required|date',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'narration' => 'nullable|string',
            'status' => 'nullable|string',
            'journal_number' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'debit_account_name' => 'nullable|string',
            'credit_account_name' => 'nullable|string',
            ]);

            if ((float) $validated['debit_amount'] !== (float) $validated['credit_amount']) {
                return response()->json(['message' => 'Manual journal must be balanced'], 422);
            }

        $validated['debit_account_id'] = $validated['debit_account_id'] ?? $this->resolveAccountId($validated['debit_account_name'] ?? null);
        $validated['credit_account_id'] = $validated['credit_account_id'] ?? $this->resolveAccountId($validated['credit_account_name'] ?? null);

        if (empty($validated['debit_account_id']) || empty($validated['credit_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'posted';
        $validated['journal_number'] = $validated['journal_number'] ?? ('JNL-' . time());

            $journal = ManualJournal::create($validated);
            $this->postManualJournal($journal);

            return response()->json($journal->load(['debitAccount', 'creditAccount']), 201);
        });
    }

    public function show(ManualJournal $manualJournal)
    {
        $this->ensureModelCompany($manualJournal);
        return response()->json($manualJournal->load(['debitAccount', 'creditAccount']));
    }

    public function update(Request $request, ManualJournal $manualJournal)
    {
        $this->ensureModelCompany($manualJournal);

        return DB::transaction(function () use ($request, $manualJournal) {
            $validated = $request->validate([
            'journal_date' => 'required|date',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'narration' => 'nullable|string',
            'status' => 'nullable|string',
            'journal_number' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'debit_account_name' => 'nullable|string',
            'credit_account_name' => 'nullable|string',
            ]);

            if ((float) $validated['debit_amount'] !== (float) $validated['credit_amount']) {
                return response()->json(['message' => 'Manual journal must be balanced'], 422);
            }

        $validated['debit_account_id'] = $validated['debit_account_id'] ?? $this->resolveAccountId($validated['debit_account_name'] ?? null);
        $validated['credit_account_id'] = $validated['credit_account_id'] ?? $this->resolveAccountId($validated['credit_account_name'] ?? null);

        if (empty($validated['debit_account_id']) || empty($validated['credit_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

            $manualJournal->update($validated);
            $this->postManualJournal($manualJournal);

            return response()->json($manualJournal->load(['debitAccount', 'creditAccount']));
        });
    }

    public function destroy(ManualJournal $manualJournal)
    {
        $this->ensureModelCompany($manualJournal);
        $this->postingService->deleteForReference($manualJournal->company_id, ManualJournal::class, $manualJournal->id);
        $manualJournal->delete();

        return response()->json(['message' => 'Manual journal deleted']);
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

    private function postManualJournal(ManualJournal $manualJournal): void
    {
        $this->postingService->post([
            'company_id' => $manualJournal->company_id,
            'reference_type' => ManualJournal::class,
            'reference_id' => $manualJournal->id,
            'entry_date' => $manualJournal->journal_date?->toDateString() ?? now()->toDateString(),
            'description' => "Manual Journal #{$manualJournal->journal_number}",
            'created_by' => $manualJournal->recorded_by,
            'lines' => [
                [
                    'account_id' => $manualJournal->debit_account_id,
                    'debit' => (float) $manualJournal->debit_amount,
                    'credit' => 0,
                    'narration' => $manualJournal->narration ?: $manualJournal->description,
                ],
                [
                    'account_id' => $manualJournal->credit_account_id,
                    'debit' => 0,
                    'credit' => (float) $manualJournal->credit_amount,
                    'narration' => $manualJournal->narration ?: $manualJournal->description,
                ],
            ],
        ]);
    }
}
