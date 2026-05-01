<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class ReceiptController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'customer_id', 'payment_mode', 'status', 'per_page']);

        $companyId = auth()->user()->company_id;
        $query = Receipt::query()
            ->where('company_id', $companyId)
            ->with('customer');

        if ($request->filled('q')) {
            $query->where('receipt_number', 'like', "%{$request->q}%");
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'receipt_date' => 'required|date',
            'amount_received' => 'required|numeric|min:0',
            'payment_mode' => 'required|in:cash,cheque,bank,online',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'receipt_number' => 'nullable|string',
            ]);

            if (empty($validated['customer_id']) && !empty($validated['customer_name'])) {
                $validated['customer_id'] = $this->resolveCustomerId($validated['customer_name']);
            }

            if (empty($validated['customer_id']) && empty($validated['credit_account_id'])) {
                return response()->json(['message' => 'Customer or credit account is required'], 422);
            }

            $validated['company_id'] = auth()->user()->company_id;
            $validated['recorded_by'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'completed';
            $validated['receipt_number'] = $validated['receipt_number'] ?? ('RCP-' . time());

            $receipt = Receipt::create($validated);
            $this->postReceiptJournal($receipt);

            return response()->json($receipt->load(['customer', 'creditAccount']), 201);
        });
    }

    public function show(Receipt $receipt)
    {
        $this->ensureModelCompany($receipt);
        return response()->json($receipt->load(['customer', 'creditAccount']));
    }

    public function update(Request $request, Receipt $receipt)
    {
        $this->ensureModelCompany($receipt);

        return DB::transaction(function () use ($request, $receipt) {
            $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'receipt_date' => 'required|date',
            'amount_received' => 'required|numeric|min:0',
            'payment_mode' => 'required|in:cash,cheque,bank,online',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'receipt_number' => 'nullable|string',
            ]);

            if (empty($validated['customer_id']) && !empty($validated['customer_name'])) {
                $validated['customer_id'] = $this->resolveCustomerId($validated['customer_name']);
            }

            if (empty($validated['customer_id']) && empty($validated['credit_account_id'])) {
                return response()->json(['message' => 'Customer or credit account is required'], 422);
            }

            $receipt->update($validated);
            $this->postReceiptJournal($receipt);

            return response()->json($receipt->load(['customer', 'creditAccount']));
        });
    }

    public function destroy(Receipt $receipt)
    {
        $this->ensureModelCompany($receipt);
        $this->postingService->deleteForReference($receipt->company_id, Receipt::class, $receipt->id);
        $receipt->delete();

        return response()->json(['message' => 'Receipt deleted']);
    }

    private function resolveCustomerId(string $name): ?int
    {
        return Customer::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', $name)
            ->value('id');
    }

    private function postReceiptJournal(Receipt $receipt): void
    {
        $creditLine = ! empty($receipt->credit_account_id)
            ? ['account_id' => $receipt->credit_account_id, 'debit' => 0, 'credit' => (float) $receipt->amount_received]
            : ['key' => 'accounts_receivable', 'debit' => 0, 'credit' => (float) $receipt->amount_received];

        $this->postingService->post([
            'company_id' => $receipt->company_id,
            'reference_type' => Receipt::class,
            'reference_id' => $receipt->id,
            'entry_date' => $receipt->receipt_date?->toDateString() ?? now()->toDateString(),
            'description' => "Receipt #{$receipt->receipt_number}",
            'created_by' => $receipt->recorded_by,
            'lines' => [
                [
                    'key' => in_array($receipt->payment_mode, ['bank', 'cheque', 'online'], true) ? 'bank' : 'cash',
                    'debit' => (float) $receipt->amount_received,
                    'credit' => 0,
                    'narration' => $receipt->description ?: 'Receipt received',
                ],
                $creditLine + ['narration' => $receipt->description ?: 'Receipt source'],
            ],
        ]);
    }
}
