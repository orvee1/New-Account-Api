<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseBillRequest;
use App\Http\Resources\PurchaseBillResource;
use App\Models\PurchaseBill;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseBillController extends Controller
{
    public function __construct(private PurchaseService $service) {}

    // GET /api/purchase-bills?q=&vendor_id=&date_from=&date_to=&per_page=20
    public function index(Request $req)
    {
        $q = PurchaseBill::query()
            ->with(['vendor'])
            ->when($req->filled('q'), function($qq) use ($req) {
                $keyword = "%{$req->q}%";
                $qq->where('bill_no','like',$keyword)
                   ->orWhereHas('vendor', fn($v)=> $v->where('name','like',$keyword));
            })
            ->when($req->filled('vendor_id'), fn($qq)=> $qq->where('vendor_id',$req->integer('vendor_id')))
            ->when($req->filled('date_from'), fn($qq)=> $qq->whereDate('bill_date','>=',$req->date('date_from')))
            ->when($req->filled('date_to'),   fn($qq)=> $qq->whereDate('bill_date','<=',$req->date('date_to')))
            ->orderByDesc('id');

        return PurchaseBillResource::collection($q->paginate($req->integer('per_page', 20)));
    }

    // GET /api/purchase-bills/{bill}
    public function show(PurchaseBill $bill)
    {
        $bill->load(['vendor','items.product']);
        return PurchaseBillResource::make($bill);
    }

    // POST /api/purchase-bills
    public function store(PurchaseBillRequest $request)
    {
        $bill = $this->service->createBill($request->validated(), auth()->id());
        return response()->json(PurchaseBillResource::make($bill), 201);
    }

    // DELETE /api/purchase-bills/{bill}
    public function destroy(PurchaseBill $bill)
    {
        $bill->delete();
        return response()->noContent();
    }
}
