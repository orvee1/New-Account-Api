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
        private CoaAccountResolver $coaResolver
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

        return DB::transaction(function () use ($companyId, $referenceType, $referenceId, $entryDate, $description, $createdBy, $lines) {
            $normalizedLines = $this->normalizeLines($companyId, $lines);
            $this->deleteForReference($companyId, $referenceType, $referenceId);

            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'entry_date' => $entryDate,
                'description' => $description,
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
            ->where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();

        foreach ($entries as $entry) {
            $entry->lines()->delete();
            $entry->delete();
        }
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
