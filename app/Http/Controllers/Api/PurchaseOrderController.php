<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $service)
    {}

    public function index(Request $req)
    {
        return PurchaseOrder::with(['vendor', 'items.product'])
            ->where('company_id', auth()->user()->company_id)
            ->latest()->paginate(20);
    }

    public function store(PurchaseOrderRequest $request)
    {
        $order = $this->service->create($request->validated());
        return response()->json($order, 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load(['vendor', 'items.product']);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function convertToBill(PurchaseOrder $purchaseOrder)
    {
        $bill = $this->service->convertToBill($purchaseOrder);
        return response()->json([
            'message' => 'Purchase Order converted to Bill successfully',
            'bill'    => $bill,
        ], 201);
    }

}
