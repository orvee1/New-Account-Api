<?php

namespace Tests\Unit;

use App\Services\StockService;
use Mockery;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_add_opening_stock_throws_for_non_positive_qty(): void
    {
        $product = Mockery::mock('App\\Models\\Product');

        $service = new StockService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Opening quantity must be greater than 0.');

        $service->addOpeningStock($product, 1, 0, 10);
    }

    public function test_add_opening_stock_throws_for_non_positive_unit_cost(): void
    {
        $product = Mockery::mock('App\\Models\\Product');

        $service = new StockService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Opening unit cost must be greater than 0.');

        $service->addOpeningStock($product, 1, 5, 0);
    }
}
