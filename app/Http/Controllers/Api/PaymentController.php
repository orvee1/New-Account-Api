<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Vendor;
use App\Services\AccountMappingService;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(
        private AccountMappingService $accountMapping,
        private JournalPostingService $posting
    ) {}

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Payment::query()
            ->where('company_id', $companyId)
            ->with('vendor')
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('payment_number', 'like', $keyword)
                    ->orWhere('invoice_reference', 'like', $keyword);
            })
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('payment_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('payment_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->input('per_page', 15))->withQueryString());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric|min:0',
            'payment_mode' => 'required|string',
            'invoice_reference' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'payment_number' => 'nullable|string',
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
        $validated['payment_number'] = $validated['payment_number'] ?? ('PAY-' . time());

        $payment = DB::transaction(function () use ($validated) {
            $payment = Payment::create($validated);
            $this->postVendorPaymentJournal($payment);
            return $payment;
        });

        return response()->json($payment->load('vendor'), 201);
    }

    public function show(Payment $payment)
    {
        $this->ensureCompanyAccess($payment->company_id);
        return response()->json($payment->load('vendor'));
    }

    public function update(Request $request, Payment $payment)
    {
        $this->ensureCompanyAccess($payment->company_id);

        $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric|min:0',
            'payment_mode' => 'required|string',
            'invoice_reference' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'payment_number' => 'nullable|string',
        ]);

        if (empty($validated['vendor_id']) && !empty($validated['vendor_name'])) {
            $validated['vendor_id'] = $this->resolveVendorId($validated['vendor_name']);
        }

        if (empty($validated['vendor_id'])) {
            return response()->json(['message' => 'Vendor not found'], 422);
        }

        DB::transaction(function () use ($payment, $validated) {
            $payment->update($validated);
            $this->postVendorPaymentJournal($payment);
        });

        return response()->json($payment->load('vendor'));
    }

    public function destroy(Payment $payment)
    {
        $this->ensureCompanyAccess($payment->company_id);
        DB::transaction(function () use ($payment) {
            $this->posting->deleteEntries($payment->company_id, Payment::class, $payment->id);
            $payment->delete();
        });

        return response()->json(['message' => 'Payment deleted']);
    }

    private function postVendorPaymentJournal(Payment $payment): void
    {
        $companyId = $payment->company_id;
        $accountsPayable = $this->accountMapping->accountsPayable($companyId);
        $cashAccount = $this->accountMapping->cash($companyId);
        $bankAccount = $this->accountMapping->bank($companyId);

        if (!$accountsPayable || !$cashAccount || !$bankAccount) {
            throw new \Exception('Required chart accounts are missing. Run ChartAccountSeeder.');
        }

        $this->posting->deleteEntries($companyId, Payment::class, $payment->id);

        $method = strtolower((string) $payment->payment_mode);
        $creditAccount = in_array($method, ['bank', 'cheque', 'online'], true)
            ? $bankAccount
            : $cashAccount;

        $amount = (float) $payment->amount_paid;

        $this->posting->postEntry(
            companyId: $companyId,
            entryDate: $payment->payment_date,
            description: "Vendor Payment #{$payment->payment_number}",
            referenceType: Payment::class,
            referenceId: $payment->id,
            createdBy: $payment->recorded_by,
            lines: [
                [
                    'account_id' => $accountsPayable->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Accounts Payable',
                ],
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Vendor Payment',
                ],
            ]
        );
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
