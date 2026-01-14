<?php

namespace Tests\Unit;

use App\Services\VendorOpeningBalanceService;
use Mockery;
use PHPUnit\Framework\TestCase;

class VendorOpeningBalanceServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_null_when_amount_is_zero(): void
    {
        $vendor = new \App\Models\Vendor();
        $vendor->setRawAttributes([
            'opening_balance' => 0,
            'opening_balance_type' => 'credit',
        ], true);

        $service = new VendorOpeningBalanceService();

        $this->assertNull($service->createOpeningBalanceJournal($vendor));
    }

    public function test_returns_null_when_type_is_invalid(): void
    {
        $vendor = new \App\Models\Vendor();
        $vendor->setRawAttributes([
            'opening_balance' => 50,
            'opening_balance_type' => 'other',
        ], true);

        $service = new VendorOpeningBalanceService();

        $this->assertNull($service->createOpeningBalanceJournal($vendor));
    }
}
