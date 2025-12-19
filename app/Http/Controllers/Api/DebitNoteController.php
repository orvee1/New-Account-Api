<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DebitNote;
use App\Models\Vendor;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
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
        $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
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

        if (empty($validated['vendor_id'])) {
            return response()->json(['message' => 'Vendor not found'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['debit_note_number'] = $validated['debit_note_number'] ?? ('DBN-' . time());

        $debitNote = DebitNote::create($validated);

        return response()->json($debitNote->load('vendor'), 201);
    }

    public function show(DebitNote $debitNote)
    {
        $this->ensureCompanyAccess($debitNote->company_id);
        return response()->json($debitNote->load('vendor'));
    }

    public function update(Request $request, DebitNote $debitNote)
    {
        $this->ensureCompanyAccess($debitNote->company_id);

        $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
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

        if (empty($validated['vendor_id'])) {
            return response()->json(['message' => 'Vendor not found'], 422);
        }

        $debitNote->update($validated);

        return response()->json($debitNote->load('vendor'));
    }

    public function destroy(DebitNote $debitNote)
    {
        $this->ensureCompanyAccess($debitNote->company_id);
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

    private function ensureCompanyAccess(?int $companyId): void
    {
        if ($companyId !== auth()->user()->company_id) {
            abort(404, 'Not found');
        }
    }
}
