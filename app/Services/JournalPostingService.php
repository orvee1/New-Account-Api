<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalLine;

class JournalPostingService
{
    public function deleteEntries(int $companyId, string $referenceType, int $referenceId): void
    {
        $entries = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();

        foreach ($entries as $entry) {
            JournalLine::query()
                ->where('journal_entry_id', $entry->id)
                ->delete();
            $entry->delete();
        }
    }

    public function postEntry(
        int $companyId,
        string $entryDate,
        string $description,
        ?string $referenceType,
        ?int $referenceId,
        ?int $createdBy,
        array $lines
    ): JournalEntry {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception('Journal entry is not balanced.');
        }

        $entry = JournalEntry::create([
            'company_id' => $companyId,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'entry_date' => $entryDate,
            'description' => $description,
            'created_by' => $createdBy,
        ]);

        foreach ($lines as $line) {
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'company_id' => $companyId,
                'account_id' => $line['account_id'],
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
                'narration' => $line['narration'] ?? null,
            ]);
        }

        return $entry;
    }
}
