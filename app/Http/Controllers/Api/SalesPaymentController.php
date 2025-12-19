<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesPayment;
use App\Http\Requests\StoreSalesPaymentRequest;
use App\Http\Resources\SalesPaymentResource;
use App\Services\SalesPaymentService;
use Illuminate\Http\Request;

class SalesPaymentController extends Controller
{
    public function __construct(private SalesPaymentService $service) {}

    // GET /api/sales-payments?q=&sales_invoice_id=&date_from=&date_to=&status=&per_page=20
    public function index(Request $request)
    {
        $query = SalesPayment::query()
            ->with(['salesInvoice.customer'])
            ->where('company_id', auth()->user()->company_id)
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('payment_no', 'like', $keyword)
                    ->orWhere('reference_no', 'like', $keyword);
            })
            ->when($request->filled('sales_invoice_id'), fn($q) => $q->where('sales_invoice_id', $request->integer('sales_invoice_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('payment_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('payment_date', '<=', $request->date('date_to')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id');

        return SalesPaymentResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    // GET /api/sales-payments/{id}
    public function show(SalesPayment $salesPayment)
    {
        $salesPayment->load(['salesInvoice.customer']);
        return SalesPaymentResource::make($salesPayment);
    }

    // POST /api/sales-payments
    public function store(StoreSalesPaymentRequest $request)
    {
        $payment = $this->service->recordPayment($request->validated(), auth()->id());
        return response()->json(SalesPaymentResource::make($payment), 201);
    }

    // DELETE /api/sales-payments/{id}
    public function destroy(SalesPayment $salesPayment)
    {
        $salesPayment->delete();
        return response()->noContent();
    }
}
