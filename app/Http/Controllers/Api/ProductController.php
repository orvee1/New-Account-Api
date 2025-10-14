<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'q', 'product_type', 'category_id', 'brand_id',
            'sku', 'barcode', 'status', 'min_price', 'max_price',
            'per_page', 'sort'
        ]);

        $list = app(ProductService::class)->paginate($filters);
        return response()->json($list);
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $product = app(ProductService::class)->create($data);
        return response()->json($product->load(['units','comboItems.itemProduct']), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['units','comboItems.itemProduct']));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $updated = app(ProductService::class)->update($product->id, $data);
        return response()->json($updated->load(['units','comboItems.itemProduct']));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
