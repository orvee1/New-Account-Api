<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class CreditNoteController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = CreditNote::query()
            ->where('company_id', $companyId)
            ->with('customer')
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('credit_note_number', 'like', $keyword)
                    ->orWhere('invoice_reference', 'like', $keyword);
            })
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->integer('customer_id')))
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
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
            'note_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'invoice_reference' => 'nullable|string',
            'reason' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'credit_note_number' => 'nullable|string',
            ]);

        if (empty($validated['customer_id']) && !empty($validated['customer_name'])) {
            $validated['customer_id'] = $this->resolveCustomerId($validated['customer_name']);
        }

            if (empty($validated['customer_id']) && empty($validated['debit_account_id'])) {
                return response()->json(['message' => 'Customer or debit account is required'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['credit_note_number'] = $validated['credit_note_number'] ?? ('CRN-' . time());

            $creditNote = CreditNote::create($validated);
            $this->postCreditNoteJournal($creditNote);

            return response()->json($creditNote->load(['customer', 'debitAccount']), 201);
        });
    }

    public function show(CreditNote $creditNote)
    {
        $this->ensureModelCompany($creditNote);
        return response()->json($creditNote->load(['customer', 'debitAccount']));
    }

    public function update(Request $request, CreditNote $creditNote)
    {
        $this->ensureModelCompany($creditNote);

        return DB::transaction(function () use ($request, $creditNote) {
            $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
            'note_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'invoice_reference' => 'nullable|string',
            'reason' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'credit_note_number' => 'nullable|string',
            ]);

        if (empty($validated['customer_id']) && !empty($validated['customer_name'])) {
            $validated['customer_id'] = $this->resolveCustomerId($validated['customer_name']);
        }

            if (empty($validated['customer_id']) && empty($validated['debit_account_id'])) {
                return response()->json(['message' => 'Customer or debit account is required'], 422);
        }

            $creditNote->update($validated);
            $this->postCreditNoteJournal($creditNote);

            return response()->json($creditNote->load(['customer', 'debitAccount']));
        });
    }

    public function destroy(CreditNote $creditNote)
    {
        $this->ensureModelCompany($creditNote);
        $this->postingService->deleteForReference($creditNote->company_id, CreditNote::class, $creditNote->id);
        $creditNote->delete();

        return response()->json(['message' => 'Credit note deleted']);
    }

    private function resolveCustomerId(string $name): ?int
    {
        return Customer::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', $name)
            ->value('id');
    }

    private function postCreditNoteJournal(CreditNote $creditNote): void
    {
        $debitLine = ! empty($creditNote->debit_account_id)
            ? ['account_id' => $creditNote->debit_account_id, 'debit' => (float) $creditNote->amount, 'credit' => 0]
            : ['key' => 'sales_revenue', 'debit' => (float) $creditNote->amount, 'credit' => 0];

        $this->postingService->post([
            'company_id' => $creditNote->company_id,
            'reference_type' => CreditNote::class,
            'reference_id' => $creditNote->id,
            'entry_date' => $creditNote->note_date?->toDateString() ?? now()->toDateString(),
            'description' => "Credit Note #{$creditNote->credit_note_number}",
            'created_by' => $creditNote->recorded_by,
            'lines' => [
                $debitLine + ['narration' => $creditNote->description ?: 'Credit note adjustment'],
                [
                    'key' => 'accounts_receivable',
                    'debit' => 0,
                    'credit' => (float) $creditNote->amount,
                    'narration' => $creditNote->description ?: 'Receivable reduction',
                ],
            ],
        ]);
    }
}
