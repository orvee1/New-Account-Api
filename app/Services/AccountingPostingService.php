<?php

namespace App\Services;

use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingPostingService
{
    public function __construct(
        private CoaAccountResolver $coaResolver,
        private AccountingPeriodGuard $periodGuard
    ) {
    }

    public function post(array $payload): JournalEntry
    {
        $companyId = (int) ($payload['company_id'] ?? 0);
        $referenceType = (string) ($payload['reference_type'] ?? '');
        $referenceId = $payload['reference_id'] ?? null;
        $entryDate = $payload['entry_date'] ?? now()->toDateString();
        $description = (string) ($payload['description'] ?? '');
        $createdBy = $payload['created_by'] ?? null;
        $status = (string) ($payload['status'] ?? 'posted');
        $entryType = (string) ($payload['entry_type'] ?? 'normal');
        $lines = $payload['lines'] ?? [];

        if ($companyId <= 0) {
            throw new InvalidArgumentException('A valid company_id is required for journal posting.');
        }

        if ($referenceType === '' || blank($referenceId)) {
            throw new InvalidArgumentException('reference_type and reference_id are required for journal posting.');
        }

        if (! is_array($lines) || count($lines) < 2) {
            throw new InvalidArgumentException('At least two journal lines are required.');
        }

        if (! in_array($status, ['draft', 'submitted', 'approved', 'posted', 'reversed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Journal entry status is invalid.');
        }

        if (! in_array($entryType, ['normal', 'adjusting', 'opening', 'closing', 'reversal'], true)) {
            throw new InvalidArgumentException('Journal entry type is invalid.');
        }

        if ($status === 'posted') {
            $this->periodGuard->assertPostingAllowed($companyId, $entryDate);
        }

        $normalizedLines = $this->normalizeLines($companyId, $lines);

        return DB::transaction(function () use ($companyId, $referenceType, $referenceId, $entryDate, $description, $createdBy, $status, $entryType, $normalizedLines) {
            $this->deleteForReference($companyId, $referenceType, $referenceId);

            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'entry_date' => $entryDate,
                'description' => $description,
                'status' => $status,
                'entry_type' => $entryType,
                'posted_at' => $status === 'posted' ? now() : null,
                'posted_by' => $status === 'posted' ? $createdBy : null,
                'created_by' => $createdBy,
            ]);

            foreach ($normalizedLines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'company_id' => $companyId,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'narration' => $line['narration'],
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public function deleteForReference(int $companyId, string $referenceType, mixed $referenceId): void
    {
        $entries = JournalEntry::query()
            ->with('lines')
            ->where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereNull('reversal_entry_id')
            ->where('entry_type', '!=', 'reversal')
            ->get();

        foreach ($entries as $entry) {
            if (in_array($entry->status, ['posted', 'reversed'], true)) {
                $this->reverseEntry($entry);
                continue;
            }

            $entry->lines()->delete();
            $entry->delete();
        }
    }

    public function reverseEntry(JournalEntry $entry, ?string $reversalDate = null, mixed $reversedBy = null): ?JournalEntry
    {
        if ($entry->reversal_entry_id || $entry->entry_type === 'reversal') {
            return null;
        }

        $entry->loadMissing('lines');

        if ($entry->lines->isEmpty()) {
            return null;
        }

        $reversalDate ??= $entry->entry_date?->toDateString() ?? now()->toDateString();
        $reversedBy ??= auth()->id();
        $this->periodGuard->assertPostingAllowed((int) $entry->company_id, $reversalDate);

        return DB::transaction(function () use ($entry, $reversalDate, $reversedBy) {
            $reversal = JournalEntry::create([
                'company_id' => $entry->company_id,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'entry_date' => $reversalDate,
                'description' => "Reversal of journal entry #{$entry->id}",
                'status' => 'posted',
                'entry_type' => 'reversal',
                'posted_at' => now(),
                'posted_by' => $reversedBy,
                'reversed_entry_id' => $entry->id,
                'created_by' => $reversedBy,
            ]);

            foreach ($entry->lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $reversal->id,
                    'company_id' => $line->company_id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'narration' => "Reversal: {$line->narration}",
                ]);
            }

            $entry->forceFill([
                'status' => 'reversed',
                'reversal_entry_id' => $reversal->id,
            ])->save();

            return $reversal->load('lines.account');
        });
    }

    private function normalizeLines(int $companyId, array $lines): array
    {
        $normalized = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException("Journal line #".($index + 1)." is invalid.");
            }

            $account = $this->resolveAccountForLine($companyId, $line);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException("Journal line #".($index + 1)." cannot use negative debit or credit values.");
            }

            if (($debit > 0 && $credit > 0) || ($debit == 0.0 && $credit == 0.0)) {
                throw new InvalidArgumentException("Journal line #".($index + 1)." must have exactly one non-zero side.");
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $normalized[] = [
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
                'narration' => $line['narration'] ?? $line['memo'] ?? null,
            ];
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException('Journal entry is not balanced. Total debit must equal total credit.');
        }

        return $normalized;
    }

    private function resolveAccountForLine(int $companyId, array $line): ChartAccount
    {
        if (! empty($line['account_id'])) {
            /** @var ChartAccount|null $account */
            $account = ChartAccount::query()->find($line['account_id']);

            if (! $account || (int) $account->company_id !== $companyId) {
                throw new InvalidArgumentException('Journal line account does not belong to the current company.');
            }

            return $this->coaResolver->assertPostable($account, "account_id [{$line['account_id']}]");
        }

        $key = $line['key'] ?? $line['coa_key'] ?? null;
        if (is_string($key) && $key !== '') {
            return $this->coaResolver->resolveByKey($companyId, $key);
        }

        $reference = $line['account_code'] ?? $line['account_slug'] ?? null;
        if (is_string($reference) && $reference !== '') {
            return $this->coaResolver->resolveReference($companyId, $reference);
        }

        throw new InvalidArgumentException('Each journal line must provide either account_id, key, account_code, or account_slug.');
    }
}
