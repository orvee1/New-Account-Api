<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(private SalesOrderService $service) {}

    // GET /api/sales-orders?q=&customer_id=&date_from=&date_to=&per_page=20
    public function index(Request $request)
    {
        $query = SalesOrder::query()
            ->with(['customer'])
            ->where('company_id', auth()->user()->company_id)
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('order_no', 'like', $keyword)
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', $keyword));
            })
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('order_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('order_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return SalesOrderResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    // GET /api/sales-orders/{id}
    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'items.product']);
        return SalesOrderResource::make($salesOrder);
    }

    // POST /api/sales-orders
    public function store(StoreSalesOrderRequest $request)
    {
        $order = $this->service->createOrder($request->validated(), auth()->id());
        return response()->json(SalesOrderResource::make($order), 201);
    }

    // PUT /api/sales-orders/{id}
    public function update(StoreSalesOrderRequest $request, SalesOrder $salesOrder)
    {
        $order = $this->service->updateOrder($salesOrder, $request->validated(), auth()->id());
        return SalesOrderResource::make($order);
    }

    // DELETE /api/sales-orders/{id}
    public function destroy(SalesOrder $salesOrder)
    {
        $salesOrder->delete();
        return response()->noContent();
    }

    // POST /api/sales-orders/{id}/convert-to-invoice
    public function convertToInvoice(SalesOrder $salesOrder)
    {
        $invoice = $this->service->convertToInvoice($salesOrder);
        return response()->json([
            'message' => 'Sales Order converted to Invoice successfully',
            'invoice' => $invoice,
        ], 201);
    }
}
