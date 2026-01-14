<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Receipt;
use App\Services\AccountMappingService;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function __construct(
        private AccountMappingService $accountMapping,
        private JournalPostingService $posting
    ) {}

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

        if (empty($validated['customer_id'])) {
            return response()->json(['message' => 'Customer not found'], 422);
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['receipt_number'] = $validated['receipt_number'] ?? ('RCP-' . time());

        $receipt = DB::transaction(function () use ($validated) {
            $receipt = Receipt::create($validated);
            $this->postReceiptJournal($receipt);
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

        if (empty($validated['customer_id'])) {
            return response()->json(['message' => 'Customer not found'], 422);
        }

        DB::transaction(function () use ($receipt, $validated) {
            $receipt->update($validated);
            $this->postReceiptJournal($receipt);
        });

        return response()->json($receipt->load('customer'));
    }

    public function destroy(Receipt $receipt)
    {
        $this->ensureCompanyAccess($receipt->company_id);
        DB::transaction(function () use ($receipt) {
            $this->posting->deleteEntries($receipt->company_id, Receipt::class, $receipt->id);
            $receipt->delete();
        });

        return response()->json(['message' => 'Receipt deleted']);
    }

    private function postReceiptJournal(Receipt $receipt): void
    {
        $companyId = $receipt->company_id;
        $accountsReceivable = $this->accountMapping->accountsReceivable($companyId);
        $cashAccount = $this->accountMapping->cash($companyId);
        $bankAccount = $this->accountMapping->bank($companyId);

        if (!$accountsReceivable || !$cashAccount || !$bankAccount) {
            throw new \Exception('Required chart accounts are missing. Run ChartAccountSeeder.');
        }

        $this->posting->deleteEntries($companyId, Receipt::class, $receipt->id);

        $method = strtolower((string) $receipt->payment_mode);
        $debitAccount = in_array($method, ['bank', 'cheque', 'online'], true)
            ? $bankAccount
            : $cashAccount;

        $amount = (float) $receipt->amount_received;

        $this->posting->postEntry(
            companyId: $companyId,
            entryDate: $receipt->receipt_date,
            description: "Receipt #{$receipt->receipt_number}",
            referenceType: Receipt::class,
            referenceId: $receipt->id,
            createdBy: $receipt->recorded_by,
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Customer Receipt',
                ],
                [
                    'account_id' => $accountsReceivable->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Accounts Receivable',
                ],
            ]
        );
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
