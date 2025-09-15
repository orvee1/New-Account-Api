
<?php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductComboItem;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function createProductWithRelations(array $data, int $userId): Product
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1) Product
            $product = new Product();
            $product->fill([
                'product_type' => $data['product_type'],
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'costing_price' => $data['product_type']==='Combo' ? 0 : ($data['costing_price'] ?? 0),
                'sales_price' => $data['product_type']==='Combo' ? 0 : ($data['sales_price'] ?? 0),
                'has_warranty' => $data['has_warranty'] ?? false,
                'warranty_days' => $data['has_warranty'] ? ($data['warranty_days'] ?? 0) : 0,
                'extra_field1_name' => $data['extra_field1_name'] ?? null,
                'extra_field1_value' => $data['extra_field1_value'] ?? null,
                'extra_field2_name' => $data['extra_field2_name'] ?? null,
                'extra_field2_value' => $data['extra_field2_value'] ?? null,
                'default_batch_no' => $data['batch_no'] ?? null,
                'default_manufactured_at' => $data['manufactured_at'] ?? null,
                'default_expired_at' => $data['expired_at'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $product->save();

            // 2) Units (Stock/Non-stock)
            if (in_array($product->product_type, ['Stock','Non-stock'])) {
                $bases = 0; $baseName = null;
                foreach ($data['units'] ?? [] as $u) {
                    $unit = new ProductUnit([
                        'name' => $u['name'],
                        'factor' => $u['factor'],
                        'is_base' => (bool)$u['is_base'],
                    ]);
                    $product->units()->save($unit);
                    if ($unit->is_base) { $bases++; $baseName = $unit->name; }
                }
                if ($bases !== 1) throw new \RuntimeException('Exactly one base unit is required.');
                $product->base_unit_name = $baseName;
                $product->save();
            } else {
                $product->base_unit_name = 'N/A';
                $product->save();
            }

            // 3) Combo BOM
            if ($product->product_type === 'Combo') {
                foreach ($data['combo_items'] ?? [] as $ci) {
                    $product->comboItems()->save(new ProductComboItem([
                        'item_product_id' => $ci['id'],
                        'quantity' => $ci['quantity'],
                    ]));
                }
            }

            // 4) Opening Stock (only Stock, optional)
            if ($product->product_type === 'Stock' && isset($data['opening_quantity']) && $data['opening_quantity'] > 0) {
                // Warehouse resolve
                $warehouse = isset($data['warehouse_id'])
                    ? Warehouse::findOrFail($data['warehouse_id'])
                    : Warehouse::where('company_id', Auth::user()->company_id)->where('is_default', true)->first();

                if (!$warehouse) throw new \RuntimeException('Default warehouse not found.');

                // Optional: create/find batch
                $batchId = null;
                if (!empty($data['batch_no']) || !empty($data['manufactured_at']) || !empty($data['expired_at'])) {
                    $batch = ProductBatch::firstOrCreate([
                        'company_id' => Auth::user()->company_id,
                        'product_id' => $product->id,
                        'batch_no'   => $data['batch_no'] ?? null,
                    ], [
                        'manufactured_at' => $data['manufactured_at'] ?? null,
                        'expired_at'      => $data['expired_at'] ?? null,
                    ]);
                    $batchId = $batch->id;
                }

                // Movement is recorded in BASE UNIT
                $product->refresh(); // ensure base_unit_name set
                $baseUnit = $product->units()->where('is_base', true)->first();
                $factor = $baseUnit?->factor ?? 1;

                $product->loadMissing('units');
                StockMovement::create([
                    'company_id' => Auth::user()->company_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'product_batch_id' => $batchId,
                    'type' => 'OPENING',
                    'quantity' => (float)$data['opening_quantity'], // already base unit by UI
                    'unit_name' => $baseUnit?->name,
                    'unit_factor_to_base' => $factor,
                    'created_by' => $userId,
                ]);
            }

            return $product->fresh(['units','comboItems','batches']);
        });
    }

    public function updateProductWithRelations(Product $product, array $data, int $userId): Product
    {
        return DB::transaction(function () use ($product, $data, $userId) {
            $product->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'sales_price' => $product->product_type==='Combo' ? 0 : ($data['sales_price'] ?? $product->sales_price),
                'has_warranty' => $data['has_warranty'] ?? false,
                'warranty_days' => ($data['has_warranty'] ?? false) ? ($data['warranty_days'] ?? 0) : 0,
                'extra_field1_name' => $data['extra_field1_name'] ?? null,
                'extra_field1_value' => $data['extra_field1_value'] ?? null,
                'extra_field2_name' => $data['extra_field2_name'] ?? null,
                'extra_field2_value' => $data['extra_field2_value'] ?? null,
                'default_batch_no' => $data['batch_no'] ?? null,
                'default_manufactured_at' => $data['manufactured_at'] ?? null,
                'default_expired_at' => $data['expired_at'] ?? null,
                'updated_by' => $userId,
            ])->save();

            // Replace Units if provided (Stock/Non-stock only; costing price & opening qty are immutable by your UI)
            if (in_array($product->product_type, ['Stock','Non-stock']) && isset($data['units'])) {
                $product->units()->delete();
                $bases = 0; $baseName = null;
                foreach ($data['units'] as $u) {
                    $unit = new ProductUnit([
                        'name' => $u['name'],
                        'factor' => $u['factor'],
                        'is_base' => (bool)$u['is_base'],
                    ]);
                    $product->units()->save($unit);
                    if ($unit->is_base) { $bases++; $baseName = $unit->name; }
                }
                if ($bases !== 1) throw new \RuntimeException('Exactly one base unit is required.');
                $product->forceFill(['base_unit_name' => $baseName])->save();
            }

            // Replace combo BOM if provided
            if ($product->product_type === 'Combo' && isset($data['combo_items'])) {
                $product->comboItems()->delete();
                foreach ($data['combo_items'] as $ci) {
                    $product->comboItems()->create([
                        'item_product_id' => $ci['id'],
                        'quantity' => $ci['quantity'],
                    ]);
                }
            }

            return $product->fresh(['units','comboItems','batches']);
        });
    }
}
