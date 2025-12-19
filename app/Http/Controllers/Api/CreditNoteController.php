<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Customer;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
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
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
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

        if (empty($validated['customer_id'])) {
            return response()->json(['message' => 'Customer not found'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['credit_note_number'] = $validated['credit_note_number'] ?? ('CRN-' . time());

        $creditNote = CreditNote::create($validated);

        return response()->json($creditNote->load('customer'), 201);
    }

    public function show(CreditNote $creditNote)
    {
        $this->ensureCompanyAccess($creditNote->company_id);
        return response()->json($creditNote->load('customer'));
    }

    public function update(Request $request, CreditNote $creditNote)
    {
        $this->ensureCompanyAccess($creditNote->company_id);

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
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

        if (empty($validated['customer_id'])) {
            return response()->json(['message' => 'Customer not found'], 422);
        }

        $creditNote->update($validated);

        return response()->json($creditNote->load('customer'));
    }

    public function destroy(CreditNote $creditNote)
    {
        $this->ensureCompanyAccess($creditNote->company_id);
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

    private function ensureCompanyAccess(?int $companyId): void
    {
        if ($companyId !== auth()->user()->company_id) {
            abort(404, 'Not found');
        }
    }
}
