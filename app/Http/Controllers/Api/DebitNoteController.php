<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DebitNote;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class DebitNoteController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = DebitNote::query()
            ->where('company_id', $companyId)
            ->with('vendor')
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('debit_note_number', 'like', $keyword)
                    ->orWhere('invoice_reference', 'like', $keyword);
            })
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('note_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('note_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'note_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'invoice_reference' => 'nullable|string',
            'reason' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'debit_note_number' => 'nullable|string',
            ]);

        if (empty($validated['vendor_id']) && !empty($validated['vendor_name'])) {
            $validated['vendor_id'] = $this->resolveVendorId($validated['vendor_name']);
        }

        if (empty($validated['vendor_id']) && empty($validated['credit_account_id'])) {
            return response()->json(['message' => 'Vendor or credit account is required'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['debit_note_number'] = $validated['debit_note_number'] ?? ('DBN-' . time());

            $debitNote = DebitNote::create($validated);
            $this->postDebitNoteJournal($debitNote);

            return response()->json($debitNote->load(['vendor', 'creditAccount']), 201);
        });
    }

    public function show(DebitNote $debitNote)
    {
        $this->ensureModelCompany($debitNote);
        return response()->json($debitNote->load(['vendor', 'creditAccount']));
    }

    public function update(Request $request, DebitNote $debitNote)
    {
        $this->ensureModelCompany($debitNote);

        return DB::transaction(function () use ($request, $debitNote) {
            $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'note_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'invoice_reference' => 'nullable|string',
            'reason' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'debit_note_number' => 'nullable|string',
            ]);

        if (empty($validated['vendor_id']) && !empty($validated['vendor_name'])) {
            $validated['vendor_id'] = $this->resolveVendorId($validated['vendor_name']);
        }

            if (empty($validated['vendor_id']) && empty($validated['credit_account_id'])) {
                return response()->json(['message' => 'Vendor or credit account is required'], 422);
        }

            $debitNote->update($validated);
            $this->postDebitNoteJournal($debitNote);

            return response()->json($debitNote->load(['vendor', 'creditAccount']));
        });
    }

    public function destroy(DebitNote $debitNote)
    {
        $this->ensureModelCompany($debitNote);
        $this->postingService->deleteForReference($debitNote->company_id, DebitNote::class, $debitNote->id);
        $debitNote->delete();

        return response()->json(['message' => 'Debit note deleted']);
    }

    private function resolveVendorId(string $name): ?int
    {
        return Vendor::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', $name)
            ->value('id');
    }

    private function postDebitNoteJournal(DebitNote $debitNote): void
    {
        $creditLine = ! empty($debitNote->credit_account_id)
            ? ['account_id' => $debitNote->credit_account_id, 'debit' => 0, 'credit' => (float) $debitNote->amount]
            : ['key' => 'purchase_account', 'debit' => 0, 'credit' => (float) $debitNote->amount];

        $this->postingService->post([
            'company_id' => $debitNote->company_id,
            'reference_type' => DebitNote::class,
            'reference_id' => $debitNote->id,
            'entry_date' => $debitNote->note_date?->toDateString() ?? now()->toDateString(),
            'description' => "Debit Note #{$debitNote->debit_note_number}",
            'created_by' => $debitNote->recorded_by,
            'lines' => [
                [
                    'key' => 'accounts_payable',
                    'debit' => (float) $debitNote->amount,
                    'credit' => 0,
                    'narration' => $debitNote->description ?: 'Debit note payable reduction',
                ],
                $creditLine + ['narration' => $debitNote->description ?: 'Debit note offset'],
            ],
        ]);
    }
}
