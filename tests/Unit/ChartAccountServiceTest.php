<?php

namespace Tests\Unit;

use App\Services\ChartAccountService;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class ChartAccountServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_seed_default_for_company_creates_nodes(): void
    {
        $chartAccount = Mockery::mock('alias:App\\Models\\ChartAccount');

        $chartAccount->id = 1;
        $chartAccount->path = '';
        $chartAccount->depth = 0;
        $chartAccount->shouldReceive('save')->twice()->andReturnTrue();

        $chartAccount->shouldReceive('create')
            ->times(2)
            ->andReturn($chartAccount);

        $service = new ChartAccountService();

        $service->seedDefaultForCompany(1, [
            [
                'name' => 'Assets',
                'code' => '1',
                'type' => 'group',
                'children' => [
                    ['name' => 'Cash', 'code' => '1.1', 'type' => 'ledger'],
                ],
            ],
        ]);

        $this->assertSame('/1/1', $chartAccount->path);
    }
}
