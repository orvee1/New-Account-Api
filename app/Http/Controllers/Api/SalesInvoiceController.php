<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Http\Resources\SalesInvoiceResource;
use App\Services\SalesInvoiceService;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    public function __construct(private SalesInvoiceService $service) {}

    // GET /api/sales-invoices?q=&customer_id=&date_from=&date_to=&status=&per_page=20
    public function index(Request $request)
    {
        $query = SalesInvoice::query()
            ->with(['customer'])
            ->where('company_id', auth('sanctum')->user()->company_id)
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('invoice_no', 'like', $keyword)
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', $keyword));
            })
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('invoice_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('invoice_date', '<=', $request->date('date_to')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id');

        return SalesInvoiceResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    // GET /api/sales-invoices/{id}
    public function show(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['customer', 'items.product', 'payments']);
        return SalesInvoiceResource::make($salesInvoice);
    }

    // POST /api/sales-invoices
    public function store(StoreSalesInvoiceRequest $request)
    {
        $invoice = $this->service->createInvoice($request->validated(), auth('sanctum')->user()->id);
        return response()->json(SalesInvoiceResource::make($invoice), 201);
    }

    // PUT /api/sales-invoices/{id}
    public function update(StoreSalesInvoiceRequest $request, SalesInvoice $salesInvoice)
    {
        $invoice = $this->service->updateInvoice($salesInvoice, $request->validated(), auth('sanctum')->user()->id);
        return SalesInvoiceResource::make($invoice);
    }

    // DELETE /api/sales-invoices/{id}
    public function destroy(SalesInvoice $salesInvoice)
    {
        $salesInvoice->delete();
        return response()->noContent();
    }

    // POST /api/sales-invoices/{id}/create-return
    public function createReturn(Request $request, SalesInvoice $salesInvoice)
    {
        $return = $this->service->createReturn($salesInvoice, $request->all());
        return response()->json([
            'message' => 'Sales Return created successfully',
            'return' => $return,
        ], 201);
    }

    // POST /api/sales-invoices/{id}/record-payment
    public function recordPayment(Request $request, SalesInvoice $salesInvoice)
    {
        $payment = $this->service->recordPayment($salesInvoice, $request->all());
        return response()->json([
            'message' => 'Payment recorded successfully',
            'payment' => $payment,
        ], 201);
    }
}
