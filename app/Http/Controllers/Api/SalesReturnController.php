<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesReturn;
use App\Http\Requests\StoreSalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Services\SalesReturnService;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function __construct(
        private SalesReturnService $service,
        private JournalPostingService $posting
    ) {}

    // GET /api/sales-returns?q=&customer_id=&date_from=&date_to=&per_page=20
    public function index(Request $request)
    {
        $query = SalesReturn::query()
            ->with(['customer'])
            ->where('company_id', auth()->user()->company_id)
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = "%{$request->q}%";
                $q->where('return_no', 'like', $keyword)
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', $keyword));
            })
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('return_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('return_date', '<=', $request->date('date_to')))
            ->orderByDesc('id');

        return SalesReturnResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    // GET /api/sales-returns/{id}
    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load(['customer', 'items.product', 'salesInvoice']);
        return SalesReturnResource::make($salesReturn);
    }

    // POST /api/sales-returns
    public function store(StoreSalesReturnRequest $request)
    {
        $return = $this->service->createReturn($request->validated(), auth()->id());
        return response()->json(SalesReturnResource::make($return), 201);
    }

    // DELETE /api/sales-returns/{id}
    public function destroy(SalesReturn $salesReturn)
    {
        DB::transaction(function () use ($salesReturn) {
            $this->posting->deleteEntries($salesReturn->company_id, SalesReturn::class, $salesReturn->id);
            $salesReturn->delete();
        });
        return response()->noContent();
    }
}
