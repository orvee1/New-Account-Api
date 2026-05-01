<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class PaymentController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

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
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
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

            if (empty($validated['vendor_id']) && empty($validated['debit_account_id'])) {
                return response()->json(['message' => 'Vendor or debit account is required'], 422);
            }

            $validated['company_id'] = auth()->user()->company_id;
            $validated['recorded_by'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'completed';
            $validated['payment_number'] = $validated['payment_number'] ?? ('PAY-' . time());

            $payment = Payment::create($validated);
            $this->postPaymentJournal($payment);

            return response()->json($payment->load(['vendor', 'debitAccount']), 201);
        });
    }

    public function show(Payment $payment)
    {
        $this->ensureModelCompany($payment);
        return response()->json($payment->load(['vendor', 'debitAccount']));
    }

    public function update(Request $request, Payment $payment)
    {
        $this->ensureModelCompany($payment);

        return DB::transaction(function () use ($request, $payment) {
            $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string',
            'debit_account_id' => 'nullable|exists:chart_accounts,id',
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

            if (empty($validated['vendor_id']) && empty($validated['debit_account_id'])) {
                return response()->json(['message' => 'Vendor or debit account is required'], 422);
            }

            $payment->update($validated);
            $this->postPaymentJournal($payment);

            return response()->json($payment->load(['vendor', 'debitAccount']));
        });
    }

    public function destroy(Payment $payment)
    {
        $this->ensureModelCompany($payment);
        $this->postingService->deleteForReference($payment->company_id, Payment::class, $payment->id);
        $payment->delete();

        return response()->json(['message' => 'Payment deleted']);
    }

    private function resolveVendorId(string $name): ?int
    {
        return Vendor::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', $name)
            ->value('id');
    }

    private function postPaymentJournal(Payment $payment): void
    {
        $debitLine = ! empty($payment->debit_account_id)
            ? ['account_id' => $payment->debit_account_id, 'debit' => (float) $payment->amount_paid, 'credit' => 0]
            : ['key' => 'accounts_payable', 'debit' => (float) $payment->amount_paid, 'credit' => 0];

        $this->postingService->post([
            'company_id' => $payment->company_id,
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
            'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'description' => "Payment #{$payment->payment_number}",
            'created_by' => $payment->recorded_by,
            'lines' => [
                $debitLine + ['narration' => $payment->description ?: 'Payment expense or payable'],
                [
                    'key' => $this->paymentAssetKey($payment->payment_mode),
                    'debit' => 0,
                    'credit' => (float) $payment->amount_paid,
                    'narration' => $payment->description ?: 'Cash/Bank outflow',
                ],
            ],
        ]);
    }

    private function paymentAssetKey(?string $mode): string
    {
        $mode = strtolower((string) $mode);

        return str_contains($mode, 'bank')
            || str_contains($mode, 'cheque')
            || str_contains($mode, 'check')
            || str_contains($mode, 'online')
            || str_contains($mode, 'transfer')
                ? 'bank'
                : 'cash';
    }
}
