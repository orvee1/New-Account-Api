<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Contra;
use Illuminate\Http\Request;

class ContraController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Contra::query()
            ->where('company_id', $companyId)
            ->with(['fromAccount', 'toAccount'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('contra_number', 'like', $keyword)
                    ->orWhere('reference_number', 'like', $keyword);
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('contra_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('contra_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contra_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'contra_number' => 'nullable|string',
            'from_account_id' => 'nullable|exists:chart_accounts,id',
            'to_account_id' => 'nullable|exists:chart_accounts,id',
            'from_account_name' => 'nullable|string',
            'to_account_name' => 'nullable|string',
        ]);

        $validated['from_account_id'] = $validated['from_account_id'] ?? $this->resolveAccountId($validated['from_account_name'] ?? null);
        $validated['to_account_id'] = $validated['to_account_id'] ?? $this->resolveAccountId($validated['to_account_name'] ?? null);

        if (empty($validated['from_account_id']) || empty($validated['to_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['contra_number'] = $validated['contra_number'] ?? ('CONTRA-' . time());

        $contra = Contra::create($validated);

        return response()->json($contra->load(['fromAccount', 'toAccount']), 201);
    }

    public function show(Contra $contra)
    {
        $this->ensureCompanyAccess($contra->company_id);
        return response()->json($contra->load(['fromAccount', 'toAccount']));
    }

    public function update(Request $request, Contra $contra)
    {
        $this->ensureCompanyAccess($contra->company_id);

        $validated = $request->validate([
            'contra_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'contra_number' => 'nullable|string',
            'from_account_id' => 'nullable|exists:chart_accounts,id',
            'to_account_id' => 'nullable|exists:chart_accounts,id',
            'from_account_name' => 'nullable|string',
            'to_account_name' => 'nullable|string',
        ]);

        $validated['from_account_id'] = $validated['from_account_id'] ?? $this->resolveAccountId($validated['from_account_name'] ?? null);
        $validated['to_account_id'] = $validated['to_account_id'] ?? $this->resolveAccountId($validated['to_account_name'] ?? null);

        if (empty($validated['from_account_id']) || empty($validated['to_account_id'])) {
            return response()->json(['message' => 'Account not found'], 422);
        }

        $contra->update($validated);

        return response()->json($contra->load(['fromAccount', 'toAccount']));
    }

    public function destroy(Contra $contra)
    {
        $this->ensureCompanyAccess($contra->company_id);
        $contra->delete();

        return response()->json(['message' => 'Contra entry deleted']);
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
