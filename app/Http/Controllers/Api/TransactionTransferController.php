<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\TransactionTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class TransactionTransferController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = TransactionTransfer::query()
            ->where('company_id', $companyId)
            ->with(['fromAccount', 'toAccount'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('transfer_number', 'like', $keyword)
                    ->orWhere('reference_number', 'like', $keyword);
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('transfer_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('transfer_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'transfer_number' => 'nullable|string',
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
        $validated['transfer_number'] = $validated['transfer_number'] ?? ('TRF-' . time());

            $transfer = TransactionTransfer::create($validated);
            $this->postTransferJournal($transfer);

            return response()->json($transfer->load(['fromAccount', 'toAccount']), 201);
        });
    }

    public function show(TransactionTransfer $transactionTransfer)
    {
        $this->ensureModelCompany($transactionTransfer);
        return response()->json($transactionTransfer->load(['fromAccount', 'toAccount']));
    }

    public function update(Request $request, TransactionTransfer $transactionTransfer)
    {
        $this->ensureModelCompany($transactionTransfer);

        return DB::transaction(function () use ($request, $transactionTransfer) {
            $validated = $request->validate([
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'transfer_number' => 'nullable|string',
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

            $transactionTransfer->update($validated);
            $this->postTransferJournal($transactionTransfer);

            return response()->json($transactionTransfer->load(['fromAccount', 'toAccount']));
        });
    }

    public function destroy(TransactionTransfer $transactionTransfer)
    {
        $this->ensureModelCompany($transactionTransfer);
        $this->postingService->deleteForReference($transactionTransfer->company_id, TransactionTransfer::class, $transactionTransfer->id);
        $transactionTransfer->delete();

        return response()->json(['message' => 'Transaction transfer deleted']);
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

    private function postTransferJournal(TransactionTransfer $transactionTransfer): void
    {
        $this->postingService->post([
            'company_id' => $transactionTransfer->company_id,
            'reference_type' => TransactionTransfer::class,
            'reference_id' => $transactionTransfer->id,
            'entry_date' => $transactionTransfer->transfer_date?->toDateString() ?? now()->toDateString(),
            'description' => "Transfer #{$transactionTransfer->transfer_number}",
            'created_by' => $transactionTransfer->recorded_by,
            'lines' => [
                [
                    'account_id' => $transactionTransfer->to_account_id,
                    'debit' => (float) $transactionTransfer->amount,
                    'credit' => 0,
                    'narration' => $transactionTransfer->description ?: 'Transfer destination',
                ],
                [
                    'account_id' => $transactionTransfer->from_account_id,
                    'debit' => 0,
                    'credit' => (float) $transactionTransfer->amount,
                    'narration' => $transactionTransfer->description ?: 'Transfer source',
                ],
            ],
        ]);
    }
}
