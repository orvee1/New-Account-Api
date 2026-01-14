<?php

namespace Tests\Unit;

use App\Services\JournalPostingService;
use Mockery;
use PHPUnit\Framework\TestCase;

class JournalPostingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_post_entry_throws_when_unbalanced(): void
    {
        $service = new JournalPostingService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Journal entry is not balanced.');

        $service->postEntry(
            1,
            '2025-01-01',
            'Test',
            null,
            null,
            null,
            [
                ['account_id' => 10, 'debit' => 100, 'credit' => 0],
                ['account_id' => 20, 'debit' => 0, 'credit' => 50],
            ]
        );
    }

    public function test_post_entry_creates_entry_and_lines_when_balanced(): void
    {
        $entryMock = Mockery::mock('alias:App\\Models\\JournalEntry');
        $entryMock->id = 99;
        $entryMock->shouldReceive('create')
            ->once()
            ->andReturn($entryMock);

        $lineMock = Mockery::mock('alias:App\\Models\\JournalLine');
        $lineMock->shouldReceive('create')
            ->times(2)
            ->andReturnTrue();

        $service = new JournalPostingService();

        $result = $service->postEntry(
            1,
            '2025-01-01',
            'Test',
            'receipt',
            123,
            1,
            [
                ['account_id' => 10, 'debit' => 100, 'credit' => 0],
                ['account_id' => 20, 'debit' => 0, 'credit' => 100],
            ]
        );

        $this->assertSame($entryMock, $result);
    }
}
