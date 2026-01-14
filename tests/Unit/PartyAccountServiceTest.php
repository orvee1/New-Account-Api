<?php

namespace Tests\Unit;

use App\Services\PartyAccountService;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Support\FakeChartAccountQuery;

class PartyAccountServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_create_vendor_account_returns_null_when_group_missing(): void
    {
        $chartAccount = Mockery::mock('alias:App\\Models\\ChartAccount');
        $groupQuery = new FakeChartAccountQuery();

        $chartAccount->shouldReceive('query')
            ->once()
            ->andReturn($groupQuery);
        $chartAccount->shouldReceive('firstOrCreate')->never();

        $vendor = new \App\Models\Vendor();
        $vendor->setRawAttributes([
            'company_id' => 1,
            'name' => 'Vendor A',
            'display_name' => null,
            'vendor_number' => null,
        ], true);

        $service = new PartyAccountService();

        $this->assertNull($service->createVendorAccount($vendor));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_create_vendor_account_builds_expected_name_and_slug(): void
    {
        $chartAccount = Mockery::mock('alias:App\\Models\\ChartAccount');

        $chartAccount->id = 77;
        $groupQuery = new FakeChartAccountQuery();
        $groupQuery->firstByWhereIn['slug:ac-payable|a-c-payable'] = $chartAccount;

        $chartAccount->shouldReceive('query')
            ->once()
            ->andReturn($groupQuery);

        $chartAccount->shouldReceive('firstOrCreate')
            ->once()
            ->with(
                Mockery::on(fn ($attrs) => $attrs['company_id'] === 1
                    && $attrs['parent_id'] === 77
                    && $attrs['name'] === 'Vendor - Acme (V-100)'
                ),
                Mockery::on(fn ($attrs) => $attrs['type'] === 'ledger'
                    && $attrs['slug'] === Str::slug('Vendor - Acme (V-100)')
                    && $attrs['is_active'] === true
                )
            )
            ->andReturn($chartAccount);

        $vendor = new \App\Models\Vendor();
        $vendor->setRawAttributes([
            'company_id' => 1,
            'name' => 'Acme',
            'display_name' => null,
            'vendor_number' => 'V-100',
        ], true);

        $service = new PartyAccountService();

        $this->assertSame($chartAccount, $service->createVendorAccount($vendor));
    }
}
