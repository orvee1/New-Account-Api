<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductService
{
    public function __construct(
        private StockService $stockService,
        private ProductOpeningStockService $openingStockJournalService
    ) {}

    public function create(array $data): Product
    {
        $companyId = auth()->user()->company_id;

        // ✅ FIX: warehouse_id default (no $product reference before create)
        $warehouseId = array_key_exists('warehouse_id', $data) ? (int)$data['warehouse_id'] : null;

        $product = Product::create([
            'company_id'    => $companyId,
            'product_type'  => $data['product_type'],
            'name'          => $data['name'],
            'sku'           => $data['sku'] ?? null,
            'barcode'       => $data['barcode'] ?? null,
            'category_id'   => $data['category_id'] ?? null,
            'brand_id'      => $data['brand_id'] ?? null,
            'warehouse_id'  => $data['warehouse_id'] ?? null,
            'unit'          => $data['unit'] ?? null,
            'costing_price' => $data['costing_price'] ?? null,
            'sales_price'   => $data['sales_price'] ?? null,
            'tax_percent'   => $data['tax_percent'] ?? null,

            'manufactured_at' => $data['manufactured_at'] ?? null,
            'expired_at'      => $data['expired_at'] ?? null,
            'has_warranty'    => $data['has_warranty'] ?? false,
            'warranty_days'   => $data['warranty_days'] ?? null,
            'description'     => $data['description'] ?? null,
            'status'          => $data['status'] ?? 'active',
            'meta'            => $data['meta'] ?? null,
            'created_by'    => Auth::id(),
        ]);

        // units (replace set)
        if (!empty($data['units'])) {
            $product->units()->delete();
            foreach ($data['units'] as $u) {
                $product->units()->create([
                    'company_id'    => auth()->user()->company_id,
                    'name'    => $u['name'],
                    'factor'  => $u['factor'],
                    'is_base' => $u['is_base'],
                ]);
            }
        }

        // ✅ opening stock + warehouse wise stock + journal
        if (($data['product_type'] ?? null) === 'Stock' && !empty($data['opening_quantity'])) {

            if (!$warehouseId) {
                // opening qty দিলে warehouse বাধ্যতামূলক হওয়া ভালো
                throw new \Exception('Warehouse is required when opening_quantity is provided.');
            }

            $openingQty = (float) $data['opening_quantity'];

            // unit cost priority: opening_unit_cost -> costing_price -> product costing_price
            $unitCost = (float) (
                $data['opening_unit_cost']
                ?? $data['costing_price']
                ?? $product->costing_price
                ?? 0
            );

            $openingDate = $data['opening_date'] ?? null;

            // 1) stock table update + movement create
            $this->stockService->addOpeningStock($product, $warehouseId, $openingQty, $unitCost, $openingDate);

            // 2) accounting journal (Inventory DR, Opening Balances CR)
            $this->openingStockJournalService->createOpeningStockJournal($product, $openingQty, $unitCost, $openingDate);
        }

        // combo items
        if (($data['product_type'] ?? null) === 'Combo' && !empty($data['combo_items'])) {
            $product->comboItems()->delete();
            foreach ($data['combo_items'] as $ci) {
                $product->comboItems()->create([
                    'company_id' => auth()->user()->company_id,
                    'combo_product_id' => $product->id,
                    'item_product_id' => $ci['product_id'],
                    'quantity'        => $ci['quantity'],
                ]);
            }
        }

        return $product;
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);

        $product->fill([
            'product_type'  => $data['product_type']  ?? $product->product_type,
            'name'          => $data['name']          ?? $product->name,
            'sku'           => $data['sku']           ?? $product->sku,
            'barcode'       => $data['barcode']       ?? $product->barcode,
            'category_id'   => array_key_exists('category_id', $data) ? $data['category_id'] : $product->category_id,
            'brand_id'      => array_key_exists('brand_id', $data) ? $data['brand_id'] : $product->brand_id,
            'warehouse_id'  => array_key_exists('warehouse_id', $data) ? $data['warehouse_id'] : $product->warehouse_id,
            'unit'          => $data['unit']          ?? $product->unit,
            'costing_price' => $data['costing_price'] ?? $product->costing_price,
            'sales_price'   => $data['sales_price']   ?? $product->sales_price,
            'tax_percent'   => $data['tax_percent']   ?? $product->tax_percent,
            'manufactured_at' => $data['manufactured_at'] ?? null,
            'expired_at'      => $data['expired_at'] ?? null,
            'has_warranty'    => $data['has_warranty']  ?? $product->has_warranty,
            'warranty_days'   => $data['warranty_days'] ?? $product->warranty_days,
            'description'     => $data['description']   ?? $product->description,
            'status'          => $data['status']        ?? $product->status,
            'meta'            => $data['meta']          ?? $product->meta,
        ])->save();

        if (array_key_exists('units', $data)) {
            $product->units()->delete();
            foreach (($data['units'] ?? []) as $u) {
                $product->units()->create([
                    'company_id' => auth()->user()->company_id,
                    'name'    => $u['name'],
                    'factor'  => $u['factor'],
                    'is_base' => $u['is_base'],
                ]);
            }
        }

        if (array_key_exists('combo_items', $data)) {
            $product->comboItems()->delete();
            foreach (($data['combo_items'] ?? []) as $ci) {
                $product->comboItems()->create([
                    'company_id' => auth()->user()->company_id,
                    'combo_product_id' => $product->id,
                    'item_product_id' => $ci['product_id'],
                    'quantity'        => $ci['quantity'],
                ]);
            }
        }

        return $product;
    }

    public function paginate(array $filters)
    {
        /** @var Builder $q */
        $q = Product::query()->where('company_id', auth()->user()->company_id);
        $q->with(['units', 'comboItems.itemProduct']);

        $q->when(!empty($filters['q']), function (Builder $qr) use ($filters) {
            $term = $filters['q'];
            $qr->where(function ($s) use ($term) {
                $s->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        });

        $q->when(!empty($filters['product_type']), fn($qr) => $qr->where('product_type', $filters['product_type']));
        $q->when(!empty($filters['category_id']), fn($qr) => $qr->where('category_id', $filters['category_id']));
        $q->when(!empty($filters['brand_id']), fn($qr) => $qr->where('brand_id', $filters['brand_id']));
        $q->when(!empty($filters['sku']), fn($qr) => $qr->where('sku', $filters['sku']));
        $q->when(!empty($filters['barcode']), fn($qr) => $qr->where('barcode', $filters['barcode']));
        $q->when(!empty($filters['status']), fn($qr) => $qr->where('status', $filters['status']));
        $q->when(!empty($filters['min_price']), fn($qr) => $qr->where('sales_price', '>=', $filters['min_price']));
        $q->when(!empty($filters['max_price']), fn($qr) => $qr->where('sales_price', '<=', $filters['max_price']));

        $sort = $filters['sort'] ?? '-created_at';
        if (str_starts_with($sort, '-')) {
            $q->orderBy(ltrim($sort, '-'), 'desc');
        } else {
            $q->orderBy($sort, 'asc');
        }

        $per = (int)($filters['per_page'] ?? 20);
        return $q->paginate($per)->withQueryString();
    }
}
