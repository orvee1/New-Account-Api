<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductOpeningStockService;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create default category
        $category = Category::first() ?? Category::create([
            'company_id' => 1,
            'name' => 'General',
            'description' => 'General Products Category',
            'status' => 'active'
        ]);

        // Get or create default brand
        $brand = Brand::first() ?? Brand::create([
            'company_id' => 1,
            'name' => 'Generic Brand',
            'description' => 'Generic Brand for Products',
            'status' => 'active'
        ]);

        // Get or create default warehouse (needed for opening stock)
        $warehouse = Warehouse::where('company_id', 1)
            ->where('is_default', true)
            ->first();
        if (!$warehouse) {
            $warehouse = Warehouse::firstOrCreate(
                ['company_id' => 1, 'name' => 'Main Warehouse'],
                ['is_default' => true]
            );
            if (!$warehouse->is_default) {
                $warehouse->is_default = true;
                $warehouse->save();
            }
        }

        $products = [
            [
                'data' => [
                    'name' => 'Laptop Computer',
                    'sku' => 'PROD-001',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'High performance laptop computer',
                    'costing_price' => 45000.00,
                    'sales_price' => 55000.00,
                    'tax_percent' => 15,
                    'has_warranty' => true,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 12,
                    'unit_cost' => 45000.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'Wireless Mouse',
                    'sku' => 'PROD-002',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'Ergonomic wireless mouse',
                    'costing_price' => 1500.00,
                    'sales_price' => 2500.00,
                    'tax_percent' => 15,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 50,
                    'unit_cost' => 1500.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'USB-C Cable',
                    'sku' => 'PROD-003',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => '2 meter USB-C charging cable',
                    'costing_price' => 300.00,
                    'sales_price' => 600.00,
                    'tax_percent' => 15,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 100,
                    'unit_cost' => 300.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'External SSD 1TB',
                    'sku' => 'PROD-004',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => '1TB External Solid State Drive',
                    'costing_price' => 8000.00,
                    'sales_price' => 10000.00,
                    'tax_percent' => 15,
                    'has_warranty' => true,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 18,
                    'unit_cost' => 8000.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'HDMI Cable',
                    'sku' => 'PROD-005',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => '2 meter HDMI 2.1 cable',
                    'costing_price' => 400.00,
                    'sales_price' => 800.00,
                    'tax_percent' => 15,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 80,
                    'unit_cost' => 400.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'Monitor 24 inch',
                    'sku' => 'PROD-006',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => '24 inch Full HD Monitor',
                    'costing_price' => 15000.00,
                    'sales_price' => 18000.00,
                    'tax_percent' => 15,
                    'has_warranty' => true,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 15,
                    'unit_cost' => 15000.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'Mechanical Keyboard',
                    'sku' => 'PROD-007',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'RGB Mechanical Gaming Keyboard',
                    'costing_price' => 5000.00,
                    'sales_price' => 7500.00,
                    'tax_percent' => 15,
                    'has_warranty' => true,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 25,
                    'unit_cost' => 5000.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'USB Hub 7-Port',
                    'sku' => 'PROD-008',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => '7 Port USB 3.0 Hub',
                    'costing_price' => 2000.00,
                    'sales_price' => 3500.00,
                    'tax_percent' => 15,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 40,
                    'unit_cost' => 2000.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'Laptop Stand',
                    'sku' => 'PROD-009',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'Adjustable aluminum laptop stand',
                    'costing_price' => 2500.00,
                    'sales_price' => 4000.00,
                    'tax_percent' => 15,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 30,
                    'unit_cost' => 2500.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'Wireless Headphones',
                    'sku' => 'PROD-010',
                    'product_type' => 'Stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'Noise-cancelling wireless headphones',
                    'costing_price' => 8000.00,
                    'sales_price' => 12000.00,
                    'tax_percent' => 15,
                    'has_warranty' => true,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'opening' => [
                    'quantity' => 22,
                    'unit_cost' => 8000.00,
                ],
            ],
            [
                'data' => [
                    'name' => 'Gift Box Packaging',
                    'sku' => 'PROD-NS-001',
                    'product_type' => 'Non-stock',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'description' => 'Premium gift box packaging',
                    'costing_price' => 30.00,
                    'sales_price' => 80.00,
                    'tax_percent' => 5,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
            ],
            [
                'data' => [
                    'name' => 'On-site Installation',
                    'sku' => 'PROD-SVC-001',
                    'product_type' => 'Service',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'description' => 'On-site installation service',
                    'costing_price' => 500.00,
                    'sales_price' => 1500.00,
                    'tax_percent' => 10,
                    'has_warranty' => false,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
            ],
            [
                'data' => [
                    'name' => 'Laptop + Mouse Bundle',
                    'sku' => 'PROD-CMB-001',
                    'product_type' => 'Combo',
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'description' => 'Laptop and wireless mouse combo package',
                    'costing_price' => 46000.00,
                    'sales_price' => 57000.00,
                    'tax_percent' => 15,
                    'has_warranty' => true,
                    'company_id' => 1,
                    'status' => 'active',
                    'created_by' => 1,
                ],
                'combo_items' => [
                    ['sku' => 'PROD-001', 'quantity' => 1],
                    ['sku' => 'PROD-002', 'quantity' => 1],
                ],
            ],
        ];

        $stockService = app(StockService::class);
        $openingStockService = app(ProductOpeningStockService::class);

        $seeded = [];

        foreach ($products as $item) {
            $data = $item['data'];
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                $data
            );
            $seeded[$data['sku']] = $product;

            if (!empty($item['opening']) && $product->wasRecentlyCreated) {
                $qty = (float) ($item['opening']['quantity'] ?? 0);
                $unitCost = (float) ($item['opening']['unit_cost'] ?? $product->costing_price ?? 0);
                if ($qty > 0 && $unitCost > 0) {
                    $stockService->addOpeningStock($product, $warehouse->id, $qty, $unitCost, now()->toDateString());
                    $openingStockService->createOpeningStockJournal($product, $qty, $unitCost, now()->toDateString());
                }
            }

            if (!empty($item['combo_items']) && $product->product_type === 'Combo') {
                $product->comboItems()->delete();
                foreach ($item['combo_items'] as $ci) {
                    $itemProduct = $seeded[$ci['sku']] ?? Product::where('sku', $ci['sku'])->first();
                    if (!$itemProduct) {
                        continue;
                    }
                    $product->comboItems()->create([
                        'product_id' => $product->id,
                        'item_product_id' => $itemProduct->id,
                        'quantity' => $ci['quantity'],
                    ]);
                }
            }
        }

        $this->command->info('Products seeded successfully!');
    }
}
