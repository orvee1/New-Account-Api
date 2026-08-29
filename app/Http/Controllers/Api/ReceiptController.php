<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Receipt;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(private AccountingPostingService $postingService)
    {
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
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'receipt_date' => 'required|date',
            'amount_received' => 'required|numeric|gt:0',
            'payment_mode' => 'required|in:cash,cheque,bank,online',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'receipt_number' => 'nullable|string',
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
        $validated['receipt_number'] = $validated['receipt_number'] ?? ('RCP-' . time());

        $receipt = DB::transaction(function () use ($validated) {
            $receipt = Receipt::create($validated);

            if (($receipt->status ?? 'completed') === 'completed') {
                $this->postReceiptJournal($receipt->load('customer'));
            }

            return $receipt;
        });

        return response()->json($receipt->load('customer'), 201);
    }

    public function show(Receipt $receipt)
    {
        $this->ensureCompanyAccess($receipt->company_id);
        return response()->json($receipt->load('customer'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        $this->ensureCompanyAccess($receipt->company_id);

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'receipt_date' => 'required|date',
            'amount_received' => 'required|numeric|gt:0',
            'payment_mode' => 'required|in:cash,cheque,bank,online',
            'credit_account_id' => 'nullable|exists:chart_accounts,id',
            'reference_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'receipt_number' => 'nullable|string',
        ]);

        if (empty($validated['customer_id']) && !empty($validated['customer_name'])) {
            $validated['customer_id'] = $this->resolveCustomerId($validated['customer_name']);
        }

        if (empty($validated['customer_id'])) {
            return response()->json(['message' => 'Customer not found'], 422);
        }

        DB::transaction(function () use ($receipt, $validated) {
            $this->postingService->deleteForReference($receipt->company_id, Receipt::class, $receipt->id);
            $receipt->update($validated);

            if (($receipt->status ?? 'completed') === 'completed') {
                $this->postReceiptJournal($receipt->refresh()->load('customer'));
            }
        });

        return response()->json($receipt->load('customer'));
    }

    public function destroy(Receipt $receipt)
    {
        $this->ensureCompanyAccess($receipt->company_id);
        DB::transaction(function () use ($receipt) {
            $this->postingService->deleteForReference($receipt->company_id, Receipt::class, $receipt->id);
            $receipt->delete();
        });

        return response()->json(['message' => 'Receipt deleted']);
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

    private function postReceiptJournal(Receipt $receipt): void
    {
        $amount = round((float) $receipt->amount_received, 2);
        $creditLine = [
            'debit' => 0,
            'credit' => $amount,
            'narration' => "Receipt {$receipt->receipt_number}",
        ];

        if ($receipt->credit_account_id) {
            $creditLine['account_id'] = $receipt->credit_account_id;
        } elseif ($receipt->customer?->chart_account_id) {
            $creditLine['account_id'] = $receipt->customer->chart_account_id;
        } else {
            $creditLine['key'] = 'accounts_receivable';
        }

        $this->postingService->post([
            'company_id' => $receipt->company_id,
            'reference_type' => Receipt::class,
            'reference_id' => $receipt->id,
            'entry_date' => $receipt->receipt_date?->toDateString() ?? now()->toDateString(),
            'description' => "Receipt #{$receipt->receipt_number}",
            'created_by' => $receipt->recorded_by,
            'lines' => [
                [
                    'key' => $this->paymentModeKey($receipt->payment_mode),
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => "Receipt received {$receipt->receipt_number}",
                ],
                $creditLine,
            ],
        ]);
    }

    private function paymentModeKey(?string $mode): string
    {
        $mode = strtolower((string) $mode);

        return in_array($mode, ['bank', 'cheque', 'online'], true) ? 'bank' : 'cash';
    }
}
