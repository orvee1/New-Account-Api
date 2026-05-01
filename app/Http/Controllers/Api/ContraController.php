<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Contra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class ContraController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

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
        return DB::transaction(function () use ($request) {
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
            $this->postContraJournal($contra);

            return response()->json($contra->load(['fromAccount', 'toAccount']), 201);
        });
    }

    public function show(Contra $contra)
    {
        $this->ensureModelCompany($contra);
        return response()->json($contra->load(['fromAccount', 'toAccount']));
    }

    public function update(Request $request, Contra $contra)
    {
        $this->ensureModelCompany($contra);

        return DB::transaction(function () use ($request, $contra) {
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
            $this->postContraJournal($contra);

            return response()->json($contra->load(['fromAccount', 'toAccount']));
        });
    }

    public function destroy(Contra $contra)
    {
        $this->ensureModelCompany($contra);
        $this->postingService->deleteForReference($contra->company_id, Contra::class, $contra->id);
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

    private function postContraJournal(Contra $contra): void
    {
        $this->postingService->post([
            'company_id' => $contra->company_id,
            'reference_type' => Contra::class,
            'reference_id' => $contra->id,
            'entry_date' => $contra->contra_date?->toDateString() ?? now()->toDateString(),
            'description' => "Contra #{$contra->contra_number}",
            'created_by' => $contra->recorded_by,
            'lines' => [
                [
                    'account_id' => $contra->to_account_id,
                    'debit' => (float) $contra->amount,
                    'credit' => 0,
                    'narration' => $contra->description ?: 'Contra destination',
                ],
                [
                    'account_id' => $contra->from_account_id,
                    'debit' => 0,
                    'credit' => (float) $contra->amount,
                    'narration' => $contra->description ?: 'Contra source',
                ],
            ],
        ]);
    }
}
