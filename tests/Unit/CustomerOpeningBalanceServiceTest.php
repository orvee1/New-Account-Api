<?php

namespace Tests\Unit;

use App\Services\CustomerOpeningBalanceService;
use Mockery;
use PHPUnit\Framework\TestCase;

class CustomerOpeningBalanceServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_null_when_amount_is_zero(): void
    {
        $customer = new \App\Models\Customer();
        $customer->setRawAttributes([
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
        ], true);

        $service = new CustomerOpeningBalanceService();

        $this->assertNull($service->createOpeningBalanceJournal($customer));
    }

    public function test_returns_null_when_type_is_invalid(): void
    {
        $customer = new \App\Models\Customer();
        $customer->setRawAttributes([
            'opening_balance' => 100,
            'opening_balance_type' => 'other',
        ], true);

        $service = new CustomerOpeningBalanceService();

        $this->assertNull($service->createOpeningBalanceJournal($customer));
    }
}
