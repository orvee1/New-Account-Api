<?php

namespace App\Console\Commands;

use App\Models\AssetDepreciation;
use App\Models\AssetDisposal;
use App\Models\ChartAccount;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ManualJournal;
use App\Services\AccountingPostingService;
use Illuminate\Console\Command;

class BackfillAccountingJournals extends Command
{
    protected $signature = 'accounting:backfill-journals {--company_id=} {--dry-run}';

    protected $description = 'Backfill missing journal entries for legacy manual journals and fixed asset records.';

    public function handle(AccountingPostingService $postingService): int
    {
        $companyId = $this->option('company_id') ? (int) $this->option('company_id') : null;
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;

        $created += $this->backfillManualJournals($postingService, $companyId, $dryRun);
        $created += $this->backfillFixedAssets($postingService, $companyId, $dryRun);
        $created += $this->backfillDepreciations($postingService, $companyId, $dryRun);
        $created += $this->backfillDisposals($postingService, $companyId, $dryRun);

        $this->info(($dryRun ? 'Would create' : 'Created') . " {$created} journal entries.");

        return self::SUCCESS;
    }

    private function backfillManualJournals(AccountingPostingService $postingService, ?int $companyId, bool $dryRun): int
    {
        $count = 0;

        ManualJournal::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['posted', 'completed'])
            ->whereDoesntHave('journalEntry')
            ->chunkById(100, function ($journals) use ($postingService, $dryRun, &$count) {
                foreach ($journals as $journal) {
                    if (round((float) $journal->debit_amount, 2) !== round((float) $journal->credit_amount, 2)) {
                        $this->warn("Skipped unbalanced manual journal #{$journal->id}");
                        continue;
                    }

                    $count++;
                    if ($dryRun) {
                        continue;
                    }

                    $entry = $postingService->post([
                        'company_id' => $journal->company_id,
                        'reference_type' => ManualJournal::class,
                        'reference_id' => $journal->id,
                        'entry_date' => $journal->journal_date?->toDateString() ?? now()->toDateString(),
                        'description' => $journal->description ?: "Manual Journal #{$journal->journal_number}",
                        'created_by' => $journal->recorded_by,
                        'status' => 'posted',
                        'entry_type' => $journal->entry_type ?: 'normal',
                        'lines' => [
                            ['account_id' => $journal->debit_account_id, 'debit' => $journal->debit_amount, 'credit' => 0, 'narration' => $journal->narration],
                            ['account_id' => $journal->credit_account_id, 'debit' => 0, 'credit' => $journal->credit_amount, 'narration' => $journal->narration],
                        ],
                    ]);

                    $journal->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();
                }
            });

        return $count;
    }

    private function backfillFixedAssets(AccountingPostingService $postingService, ?int $companyId, bool $dryRun): int
    {
        $count = 0;

        FixedAsset::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('journal_entry_id')
            ->where('amount', '>', 0)
            ->chunkById(100, function ($assets) use ($postingService, $dryRun, &$count) {
                foreach ($assets as $asset) {
                    $count++;
                    if ($dryRun) {
                        continue;
                    }

                    $creditKey = match ($asset->purchase_mode) {
                        'Cash Purchase' => 'cash',
                        'Bank Purchase' => 'bank',
                        default => 'accounts_payable',
                    };

                    $entry = $postingService->post([
                        'company_id' => $asset->company_id,
                        'reference_type' => FixedAsset::class,
                        'reference_id' => $asset->id,
                        'entry_date' => $asset->purchase_date?->toDateString() ?? now()->toDateString(),
                        'description' => "Fixed asset capitalization - {$asset->name}",
                        'created_by' => $asset->created_by,
                        'status' => 'posted',
                        'entry_type' => 'normal',
                        'lines' => [
                            ['key' => 'fixed_asset', 'debit' => $asset->amount, 'credit' => 0, 'narration' => "Capitalize fixed asset {$asset->name}"],
                            ['key' => $creditKey, 'debit' => 0, 'credit' => $asset->amount, 'narration' => "Fixed asset purchase {$asset->name}"],
                        ],
                    ]);

                    $asset->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();
                }
            });

        return $count;
    }

    private function backfillDepreciations(AccountingPostingService $postingService, ?int $companyId, bool $dryRun): int
    {
        $count = 0;

        AssetDepreciation::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('journal_entry_id')
            ->where('is_active', true)
            ->where('amount', '>', 0)
            ->chunkById(100, function ($rows) use ($postingService, $dryRun, &$count) {
                foreach ($rows as $row) {
                    $count++;
                    if ($dryRun) {
                        continue;
                    }

                    $entry = $postingService->post([
                        'company_id' => $row->company_id,
                        'reference_type' => AssetDepreciation::class,
                        'reference_id' => $row->id,
                        'entry_date' => $row->start_date?->toDateString() ?? now()->toDateString(),
                        'description' => 'Asset depreciation',
                        'created_by' => $row->created_by,
                        'status' => 'posted',
                        'entry_type' => 'adjusting',
                        'lines' => [
                            ['key' => 'depreciation_expense', 'debit' => $row->amount, 'credit' => 0, 'narration' => $row->debit_acc_name],
                            ['key' => 'accumulated_depreciation', 'debit' => 0, 'credit' => $row->amount, 'narration' => $row->credit_acc_name],
                        ],
                    ]);

                    $row->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();
                }
            });

        return $count;
    }

    private function backfillDisposals(AccountingPostingService $postingService, ?int $companyId, bool $dryRun): int
    {
        $count = 0;

        AssetDisposal::query()
            ->with('asset')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('journal_entry_id')
            ->chunkById(100, function ($rows) use ($postingService, $dryRun, &$count) {
                foreach ($rows as $row) {
                    if (! $row->asset) {
                        continue;
                    }

                    $lines = $this->disposalLines($row);
                    if (count($lines) < 2) {
                        continue;
                    }

                    $count++;
                    if ($dryRun) {
                        continue;
                    }

                    $entry = $postingService->post([
                        'company_id' => $row->company_id,
                        'reference_type' => AssetDisposal::class,
                        'reference_id' => $row->id,
                        'entry_date' => $row->disposed_at?->toDateString() ?? now()->toDateString(),
                        'description' => "Asset disposal - {$row->asset->name}",
                        'created_by' => $row->created_by,
                        'status' => 'posted',
                        'entry_type' => 'normal',
                        'lines' => $lines,
                    ]);

                    $row->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();
                }
            });

        return $count;
    }

    private function disposalLines(AssetDisposal $row): array
    {
        $assetCost = round((float) $row->asset->amount, 2);
        $proceeds = round((float) $row->disposal_value, 2);
        $accumulatedDepreciation = min($assetCost, $this->accumulatedDepreciationForAsset($row->asset));
        $bookValue = round($assetCost - $accumulatedDepreciation, 2);
        $gainOrLoss = round($proceeds - $bookValue, 2);
        $lines = [];

        if ($proceeds > 0) {
            $lines[] = ['key' => 'cash', 'debit' => $proceeds, 'credit' => 0, 'narration' => "Asset disposal proceeds {$row->asset->name}"];
        }
        if ($accumulatedDepreciation > 0) {
            $lines[] = ['key' => 'accumulated_depreciation', 'debit' => $accumulatedDepreciation, 'credit' => 0, 'narration' => "Remove accumulated depreciation {$row->asset->name}"];
        }
        if ($gainOrLoss < 0) {
            $lines[] = ['key' => 'loss_on_asset_disposal', 'debit' => abs($gainOrLoss), 'credit' => 0, 'narration' => "Loss on disposal {$row->asset->name}"];
        }
        if ($assetCost > 0) {
            $lines[] = ['key' => 'fixed_asset', 'debit' => 0, 'credit' => $assetCost, 'narration' => "Remove fixed asset {$row->asset->name}"];
        }
        if ($gainOrLoss > 0) {
            $lines[] = ['key' => 'gain_on_asset_disposal', 'debit' => 0, 'credit' => $gainOrLoss, 'narration' => "Gain on disposal {$row->asset->name}"];
        }

        return $lines;
    }

    private function accumulatedDepreciationForAsset(FixedAsset $asset): float
    {
        $depreciationIds = AssetDepreciation::where('company_id', $asset->company_id)
            ->where('fixed_asset_id', $asset->id)
            ->pluck('id');

        if ($depreciationIds->isEmpty()) {
            return 0.0;
        }

        $accountId = ChartAccount::where('company_id', $asset->company_id)
            ->where('code', config('coa_map.accumulated_depreciation'))
            ->value('id');

        if (! $accountId) {
            return 0.0;
        }

        $totals = JournalLine::query()
            ->selectRaw('SUM(debit) as debit, SUM(credit) as credit')
            ->where('company_id', $asset->company_id)
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', fn($q) => $q->whereIn('status', ['posted', 'reversed'])
                ->where('reference_type', AssetDepreciation::class)
                ->whereIn('reference_id', $depreciationIds))
            ->first();

        return round((float) ($totals->credit ?? 0) - (float) ($totals->debit ?? 0), 2);
    }
}
