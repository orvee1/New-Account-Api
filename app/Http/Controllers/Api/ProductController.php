<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $inventory) {}

    // GET /api/products?q=&type=&category=&per_page=...
    public function index(Request $req) {
        $q = Product::query()
            ->with(['units:id,product_id,name,factor,is_base', 'comboItems.item:id,name,product_type'])
            ->when($req->filled('q'), fn($qq) =>
                $qq->where(function($w) use ($req){
                    $w->where('name','like','%'.$req->q.'%')
                      ->orWhere('code','like','%'.$req->q.'%')
                      ->orWhere('category','like','%'.$req->q.'%');
                })
            )
            ->when($req->filled('type'), fn($qq)=> $qq->where('product_type',$req->type))
            ->when($req->filled('category'), fn($qq)=> $qq->where('category',$req->category))
            ->orderByDesc('id');

        return $q->paginate($req->integer('per_page', 20));
    }

    // GET /api/products/{product}
    public function show(Product $product) {
        $product->load(['units','comboItems.item','batches']);
        return $product;
    }

    // POST /api/products
    public function store(ProductRequest $request) {
        $data = $this->mapPayload($request);
        $product = $this->inventory->createProductWithRelations($data, auth()->id());
        return response()->json($product, 201);
    }

    // PUT/PATCH /api/products/{product}
    public function update(ProductRequest $request, Product $product) {
        // Code & costing_price & opening_quantity are intentionally immutable based on your UI
        $data = $this->mapPayload($request, updating: true);
        $product = $this->inventory->updateProductWithRelations($product, $data, auth()->id());
        return response()->json($product);
    }

    // DELETE /api/products/{product}
    public function destroy(Product $product) {
        $product->delete();
        return response()->noContent();
    }

    private function mapPayload(ProductRequest $r, bool $updating=false): array {
        return [
            'product_type'    => $r->product_type,
            'name'            => $r->name,
            'code'            => $updating ? null : ($r->code ?: null),
            'description'     => $r->description,
            'category'        => $r->category,

            'has_warranty'    => (bool)$r->has_warranty,
            'warranty_days'   => $r->warranty_days,

            'costing_price'   => $updating ? null : $r->costing_price,
            'sales_price'     => $r->sales_price,

            'units'           => $r->units ?? null,

            'combo_items'     => collect($r->combo_items ?? [])->map(fn($i)=>[
                'id' => (int)$i['id'],
                'quantity' => (float)$i['quantity'],
            ])->values()->all(),

            // opening stock
            'opening_quantity'=> $updating ? null : $r->opening_quantity,
            'warehouse_id'    => $updating ? null : $r->warehouse_id,

            // default batch meta + batch record for opening
            'batch_no'        => $r->batch_no,
            'manufactured_at' => $r->manufactured_at,
            'expired_at'      => $r->expired_at,
        ];
    }
}
