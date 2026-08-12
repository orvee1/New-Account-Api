<?php

namespace Tests\Unit;

use App\Models\ChartAccount;
use App\Services\AccountingPeriodGuard;
use App\Services\AccountingPostingService;
use App\Services\CoaAccountResolver;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class AccountingPostingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_rejects_unbalanced_entries_before_writing(): void
    {
        $resolver = Mockery::mock(CoaAccountResolver::class);
        $guard = Mockery::mock(AccountingPeriodGuard::class);
        $service = new AccountingPostingService($resolver, $guard);

        $guard->shouldReceive('assertPostingAllowed')->once();
        $resolver->shouldReceive('resolveByKey')
            ->twice()
            ->andReturn($this->ledgerAccount());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry is not balanced');

        $service->post([
            'company_id' => 1,
            'reference_type' => 'unit-test',
            'reference_id' => 10,
            'entry_date' => '2026-08-12',
            'lines' => [
                ['key' => 'cash', 'debit' => 100, 'credit' => 0],
                ['key' => 'sales_revenue', 'debit' => 0, 'credit' => 90],
            ],
        ]);
    }

    public function test_it_rejects_invalid_journal_status(): void
    {
        $service = new AccountingPostingService(
            Mockery::mock(CoaAccountResolver::class),
            Mockery::mock(AccountingPeriodGuard::class)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry status is invalid');

        $service->post([
            'company_id' => 1,
            'reference_type' => 'unit-test',
            'reference_id' => 11,
            'status' => 'paid',
            'lines' => [
                ['key' => 'cash', 'debit' => 100, 'credit' => 0],
                ['key' => 'sales_revenue', 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_it_blocks_posting_when_period_guard_rejects_date(): void
    {
        $resolver = Mockery::mock(CoaAccountResolver::class);
        $guard = Mockery::mock(AccountingPeriodGuard::class);
        $service = new AccountingPostingService($resolver, $guard);

        $guard->shouldReceive('assertPostingAllowed')
            ->once()
            ->andThrow(new InvalidArgumentException('Posting is not allowed in closed accounting period [August 2026].'));
        $resolver->shouldNotReceive('resolveByKey');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('closed accounting period');

        $service->post([
            'company_id' => 1,
            'reference_type' => 'unit-test',
            'reference_id' => 12,
            'entry_date' => '2026-08-12',
            'lines' => [
                ['key' => 'cash', 'debit' => 100, 'credit' => 0],
                ['key' => 'sales_revenue', 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    private function ledgerAccount(): ChartAccount
    {
        return new ChartAccount([
            'id' => 1,
            'company_id' => 1,
            'type' => 'ledger',
            'is_active' => true,
        ]);
    }
}
