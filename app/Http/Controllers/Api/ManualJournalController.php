<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\ManualJournal;
use App\Services\AccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $validated = $request->validate([
            'journal_date' => 'required|date',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'narration' => 'nullable|string',
            'status' => 'nullable|string',
            'entry_type' => 'nullable|in:normal,adjusting,opening,closing,reversal',
            'journal_number' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'debit_account_name' => 'nullable|string',
            'credit_account_name' => 'nullable|string',
            'entries' => 'nullable|array|min:2',
            'entries.*.account_id' => 'required_with:entries|exists:chart_accounts,id',
            'entries.*.type' => 'required_with:entries|in:debit,credit',
            'entries.*.amount' => 'required_with:entries|numeric|min:0.01',
        ]);

        $validated = $this->normalizeManualJournalPayload($validated);

        if (empty($validated['debit_account_id']) || empty($validated['credit_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        $this->validateBalancedAmounts($validated);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'posted';
        $validated['entry_type'] = $validated['entry_type'] ?? 'normal';
        $validated['journal_number'] = $validated['journal_number'] ?? ('JNL-' . time());

        $journal = DB::transaction(function () use ($validated) {
            $journal = ManualJournal::create($validated);
            $this->syncPostedJournalEntry($journal);

            return $journal;
        });

        return response()->json($journal->load(['debitAccount', 'creditAccount', 'journalEntry.lines']), 201);
    }

    public function show(ManualJournal $manualJournal)
    {
        $this->ensureCompanyAccess($manualJournal->company_id);
        return response()->json($manualJournal->load(['debitAccount', 'creditAccount', 'journalEntry.lines']));
    }

    public function update(Request $request, ManualJournal $manualJournal)
    {
        $this->ensureCompanyAccess($manualJournal->company_id);
        $this->assertEditable($manualJournal);

        $validated = $request->validate([
            'journal_date' => 'required|date',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'narration' => 'nullable|string',
            'status' => 'nullable|string',
            'entry_type' => 'nullable|in:normal,adjusting,opening,closing,reversal',
            'journal_number' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'debit_account_name' => 'nullable|string',
            'credit_account_name' => 'nullable|string',
            'entries' => 'nullable|array|min:2',
            'entries.*.account_id' => 'required_with:entries|exists:chart_accounts,id',
            'entries.*.type' => 'required_with:entries|in:debit,credit',
            'entries.*.amount' => 'required_with:entries|numeric|min:0.01',
        ]);

        $validated = $this->normalizeManualJournalPayload($validated);

        if (empty($validated['debit_account_id']) || empty($validated['credit_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        $this->validateBalancedAmounts($validated);

        DB::transaction(function () use ($manualJournal, $validated) {
            $manualJournal->update($validated);
            $this->syncPostedJournalEntry($manualJournal->refresh());
        });

        return response()->json($manualJournal->refresh()->load(['debitAccount', 'creditAccount', 'journalEntry.lines']));
    }

    public function destroy(ManualJournal $manualJournal)
    {
        $this->ensureCompanyAccess($manualJournal->company_id);
        $this->assertEditable($manualJournal);

        DB::transaction(function () use ($manualJournal) {
            $this->postingService->deleteForReference($manualJournal->company_id, ManualJournal::class, $manualJournal->id);
            $manualJournal->delete();
        });

        return response()->json(['message' => 'Manual journal deleted']);
    }

    public function reverse(Request $request, ManualJournal $manualJournal)
    {
        $this->ensureCompanyAccess($manualJournal->company_id);

        $journalEntry = $manualJournal->journalEntry;
        if (! $journalEntry || $journalEntry->entry_type === 'reversal' || $journalEntry->reversal_entry_id) {
            return response()->json(['message' => 'Manual journal has no active posted journal entry to reverse.'], 422);
        }

        if ($journalEntry->status !== 'posted') {
            return response()->json(['message' => 'Only posted manual journals can be reversed.'], 422);
        }

        $reversalDate = $request->input('reversal_date');
        if ($reversalDate) {
            $request->validate(['reversal_date' => 'date']);
        }

        $reversal = DB::transaction(function () use ($manualJournal, $journalEntry, $reversalDate) {
            $reversal = $this->postingService->reverseEntry($journalEntry, $reversalDate);

            $manualJournal->forceFill([
                'status' => 'reversed',
            ])->saveQuietly();

            return $reversal;
        });

        return response()->json([
            'message' => 'Manual journal reversed',
            'manual_journal' => $manualJournal->refresh()->load(['debitAccount', 'creditAccount', 'journalEntry.lines']),
            'reversal_entry' => $reversal,
        ]);
    }

    private function assertEditable(ManualJournal $manualJournal): void
    {
        $manualJournal->loadMissing('journalEntry');

        if (in_array($manualJournal->status, ['posted', 'completed', 'reversed'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Posted manual journals cannot be edited or deleted. Reverse the journal and create a correction entry.'],
            ]);
        }

        if ($manualJournal->journalEntry && in_array($manualJournal->journalEntry->status, ['posted', 'reversed'], true)) {
            throw ValidationException::withMessages([
                'status' => ['This manual journal already has posted ledger impact. Reverse it before making corrections.'],
            ]);
        }
    }

    private function validateBalancedAmounts(array $validated): void
    {
        $debit = round((float) ($validated['debit_amount'] ?? 0), 2);
        $credit = round((float) ($validated['credit_amount'] ?? 0), 2);

        if ($debit <= 0 || $credit <= 0) {
            throw ValidationException::withMessages([
                'debit_amount' => ['Debit and credit amounts must be greater than zero.'],
            ]);
        }

        if ($debit !== $credit) {
            throw ValidationException::withMessages([
                'credit_amount' => ['Manual journal is not balanced. Debit amount must equal credit amount.'],
            ]);
        }
    }

    private function normalizeManualJournalPayload(array $validated): array
    {
        if (! empty($validated['entries'])) {
            $totalDebit = 0.0;
            $totalCredit = 0.0;
            $debitAccountId = null;
            $creditAccountId = null;

            foreach ($validated['entries'] as $entry) {
                $amount = round((float) ($entry['amount'] ?? 0), 2);

                if (($entry['type'] ?? null) === 'debit') {
                    $totalDebit += $amount;
                    $debitAccountId ??= $entry['account_id'] ?? null;
                }

                if (($entry['type'] ?? null) === 'credit') {
                    $totalCredit += $amount;
                    $creditAccountId ??= $entry['account_id'] ?? null;
                }
            }

            $validated['debit_amount'] = $totalDebit;
            $validated['credit_amount'] = $totalCredit;
            $validated['debit_account_id'] = $validated['debit_account_id'] ?? $debitAccountId;
            $validated['credit_account_id'] = $validated['credit_account_id'] ?? $creditAccountId;
        }

        $validated['debit_account_id'] = $validated['debit_account_id'] ?? $this->resolveAccountId($validated['debit_account_name'] ?? null);
        $validated['credit_account_id'] = $validated['credit_account_id'] ?? $this->resolveAccountId($validated['credit_account_name'] ?? null);

        return $validated;
    }

    private function syncPostedJournalEntry(ManualJournal $journal): void
    {
        if (! in_array($journal->status, ['posted', 'completed'], true)) {
            $this->postingService->deleteForReference($journal->company_id, ManualJournal::class, $journal->id);
            $journal->forceFill(['journal_entry_id' => null])->saveQuietly();

            return;
        }

        $lines = ! empty($journal->entries)
            ? collect($journal->entries)->map(function ($line) use ($journal) {
                $amount = round((float) ($line['amount'] ?? 0), 2);

                return [
                    'account_id' => $line['account_id'] ?? null,
                    'debit' => ($line['type'] ?? null) === 'debit' ? $amount : 0,
                    'credit' => ($line['type'] ?? null) === 'credit' ? $amount : 0,
                    'narration' => $journal->narration,
                ];
            })->all()
            : [
                [
                    'account_id' => $journal->debit_account_id,
                    'debit' => $journal->debit_amount,
                    'credit' => 0,
                    'narration' => $journal->narration,
                ],
                [
                    'account_id' => $journal->credit_account_id,
                    'debit' => 0,
                    'credit' => $journal->credit_amount,
                    'narration' => $journal->narration,
                ],
            ];

        $entry = $this->postingService->post([
            'company_id' => $journal->company_id,
            'reference_type' => ManualJournal::class,
            'reference_id' => $journal->id,
            'entry_date' => $journal->journal_date?->toDateString() ?? now()->toDateString(),
            'description' => $journal->description ?: "Manual Journal #{$journal->journal_number}",
            'created_by' => auth()->id(),
            'status' => 'posted',
            'entry_type' => $journal->entry_type ?: 'normal',
            'lines' => $lines,
        ]);

        $journal->forceFill([
            'journal_entry_id' => $entry->id,
            'posted_by' => auth()->id(),
            'posted_date' => now()->toDateString(),
        ])->saveQuietly();
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
