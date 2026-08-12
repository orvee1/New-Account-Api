<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use InvalidArgumentException;

class AccountingPeriodGuard
{
    public function assertPostingAllowed(int $companyId, string $entryDate): void
    {
        $period = AccountingPeriod::query()
            ->coveringDate($companyId, $entryDate)
            ->orderByDesc('id')
            ->first();

        if ($period) {
            if (in_array($period->status, ['closed', 'locked'], true)) {
                throw new InvalidArgumentException("Posting is not allowed in {$period->status} accounting period [{$period->name}].");
            }

            return;
        }

        $fiscalYear = FiscalYear::query()
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->orderByDesc('id')
            ->first();

        if ($fiscalYear && in_array($fiscalYear->status, ['closed', 'locked'], true)) {
            throw new InvalidArgumentException("Posting is not allowed in {$fiscalYear->status} fiscal year [{$fiscalYear->name}].");
        }
    }
}
