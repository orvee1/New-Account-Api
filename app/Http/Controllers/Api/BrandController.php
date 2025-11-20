<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $q = Brand::query()->where('company_id', auth()->user()->company_id);

        if ($s = $request->get('q')) {
            $q->where('name', 'like', "%{$s}%");
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        $q->orderBy('name');
        return response()->json($q->paginate((int)($request->get('per_page', 20))));
    }

    public function store(BrandRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = auth()->user()->company_id;
        $brand = Brand::create($data);
        return response()->json($brand, 201);
    }

    public function show(Brand $brand)
    {
        $this->authorizeCompany($brand);
        return response()->json($brand);
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $this->authorizeCompany($brand);
        $brand->update($request->validated());
        return response()->json($brand);
    }

    public function destroy(Brand $brand)
    {
        $this->authorizeCompany($brand);
        $brand->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function authorizeCompany(Brand $brand): void
    {
        abort_unless($brand->company_id === auth()->user()->company_id, 403, 'Forbidden');
    }
}
