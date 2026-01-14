<?php

namespace Tests\Unit;

use App\Services\ProductOpeningStockService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ProductOpeningStockServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_null_for_non_stock_product(): void
    {
        $product = new \App\Models\Product();
        $product->setRawAttributes([
            'product_type' => 'Service',
        ], true);

        $service = new ProductOpeningStockService();

        $this->assertNull($service->createOpeningStockJournal($product, 10, 5));
    }

    public function test_returns_null_when_qty_or_cost_invalid(): void
    {
        $product = new \App\Models\Product();
        $product->setRawAttributes([
            'product_type' => 'Stock',
        ], true);

        $service = new ProductOpeningStockService();

        $this->assertNull($service->createOpeningStockJournal($product, 0, 5));
        $this->assertNull($service->createOpeningStockJournal($product, 5, 0));
    }
}
