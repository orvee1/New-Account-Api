<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseService $service) {}

    public function index(Request $req)
    {
        $q = PurchaseReturn::query()
            ->with(['vendor'])
            ->when($req->filled('q'), function($qq) use ($req) {
                $keyword = "%{$req->q}%";
                $qq->where('return_no','like',$keyword)
                   ->orWhereHas('vendor', fn($v)=> $v->where('name','like',$keyword));
            })
            ->when($req->filled('vendor_id'), fn($qq)=> $qq->where('vendor_id',$req->integer('vendor_id')))
            ->when($req->filled('date_from'), fn($qq)=> $qq->whereDate('return_date','>=',$req->date('date_from')))
            ->when($req->filled('date_to'),   fn($qq)=> $qq->whereDate('return_date','<=',$req->date('date_to')))
            ->orderByDesc('id');

        return PurchaseReturnResource::collection($q->paginate($req->integer('per_page', 20)));
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['vendor','items.product']);
        return PurchaseReturnResource::make($purchaseReturn);
    }

    public function store(PurchaseReturnRequest $request)
    {
        $ret = $this->service->createReturn($request->validated(), auth()->id());
        return response()->json(PurchaseReturnResource::make($ret), 201);
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->delete();
        return response()->noContent();
    }
}
