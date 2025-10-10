<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    // GET /api/warehouses
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $q = Warehouse::query()->forCompany($companyId);

        // optional search & sort
        if ($search = $request->get('search')) {
            $q->where('name', 'like', "%{$search}%");
        }
        $sort = in_array($request->get('sort'), ['name','is_default','created_at']) ? $request->get('sort') : 'id';
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        $items = $q->orderBy($sort, $dir)->paginate($request->integer('per_page', 20))->withQueryString();

        return WarehouseResource::collection($items);
    }

    // POST /api/warehouses
    public function store(WarehouseRequest $request)
    {
        $companyId = $request->user()->company_id;

        $data = [
            'company_id' => $companyId,
            'name'       => $request->string('name'),
            'is_default' => (bool) $request->boolean('is_default'),
        ];

        $warehouse = DB::transaction(function () use ($data, $companyId) {
            // যদি is_default=1 হয়, অন্য সব default=false
            if (!empty($data['is_default'])) {
                Warehouse::forCompany($companyId)->where('is_default', true)->update(['is_default' => false]);
            }
            return Warehouse::create($data);
        });

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/warehouses/{warehouse}
    public function show(Request $request, Warehouse $warehouse)
    {
        $this->authorizeCompany($request, $warehouse);
        return new WarehouseResource($warehouse);
    }

    // PUT /api/warehouses/{warehouse}
    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorizeCompany($request, $warehouse);

        $data = [
            'name'       => $request->string('name'),
        ];
        if ($request->has('is_default')) {
            $data['is_default'] = (bool) $request->boolean('is_default');
        }

        DB::transaction(function () use ($request, $warehouse, $data) {
            if (array_key_exists('is_default', $data) && $data['is_default'] === true) {
                Warehouse::forCompany($warehouse->company_id)
                    ->where('is_default', true)
                    ->where('id', '!=', $warehouse->id)
                    ->update(['is_default' => false]);
            }
            $warehouse->update($data);
        });

        return new WarehouseResource($warehouse->refresh());
    }

    // DELETE /api/warehouses/{warehouse}
    public function destroy(Request $request, Warehouse $warehouse)
    {
        $this->authorizeCompany($request, $warehouse);

        // default warehouse delete করতে দিলে সতর্কতা
        if ($warehouse->is_default) {
            return response()->json([
                'message' => 'Default warehouse cannot be deleted.',
                'errors'  => ['is_default' => ['Default warehouse cannot be deleted.']]
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse deleted.'
        ]);
    }

    // POST /api/warehouses/{warehouse}/make-default
    public function makeDefault(Request $request, Warehouse $warehouse)
    {
        $this->authorizeCompany($request, $warehouse);

        DB::transaction(function () use ($warehouse) {
            Warehouse::forCompany($warehouse->company_id)->where('is_default', true)->update(['is_default' => false]);
            $warehouse->update(['is_default' => true]);
        });

        return new WarehouseResource($warehouse->refresh());
    }

    protected function authorizeCompany(Request $request, Warehouse $warehouse): void
    {
        if ($warehouse->company_id !== ($request->user()->company_id)) {
            abort(403, 'Unauthorized.');
        }
    }
}
