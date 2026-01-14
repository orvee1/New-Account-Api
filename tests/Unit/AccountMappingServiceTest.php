<?php

namespace Tests\Unit;

use App\Services\AccountMappingService;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Support\FakeChartAccountQuery;

class AccountMappingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_cash_returns_null_when_group_missing(): void
    {
        $chartAccount = Mockery::mock('alias:App\\Models\\ChartAccount');
        $groupQuery = new FakeChartAccountQuery();

        $chartAccount->shouldReceive('query')
            ->once()
            ->andReturn($groupQuery);
        $chartAccount->shouldReceive('firstOrCreate')->never();

        $service = new AccountMappingService();

        $this->assertNull($service->cash(1));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_cash_creates_ledger_when_missing(): void
    {
        $chartAccount = Mockery::mock('alias:App\\Models\\ChartAccount');

        $chartAccount->id = 10;

        $groupQuery = new FakeChartAccountQuery();
        $groupQuery->firstByWhereIn['slug:cash'] = $chartAccount;

        $ledgerQuery = new FakeChartAccountQuery();
        $ledgerQuery->firstByWhere['slug:=:cash-on-hand'] = null;

        $chartAccount->shouldReceive('query')
            ->andReturn($groupQuery, $ledgerQuery);

        $chartAccount->shouldReceive('firstOrCreate')
            ->once()
            ->with(
                Mockery::on(fn ($attrs) => $attrs['company_id'] === 1
                    && $attrs['parent_id'] === 10
                    && $attrs['name'] === 'Cash on Hand'
                ),
                Mockery::on(fn ($attrs) => $attrs['type'] === 'ledger'
                    && $attrs['slug'] === 'cash-on-hand'
                    && $attrs['is_active'] === true
                )
            )
            ->andReturn($chartAccount);

        $service = new AccountMappingService();

        $this->assertSame($chartAccount, $service->cash(1));
    }
}
