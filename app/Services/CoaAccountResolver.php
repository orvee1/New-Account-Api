<?php

namespace App\Services;

use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class CoaAccountResolver
{
    public function resolveByKey(int $companyId, string $mapKey): ChartAccount
    {
        $reference = config("coa_map.{$mapKey}");

        if (! is_string($reference) || trim($reference) === '') {
            throw new InvalidArgumentException("COA map key [{$mapKey}] is not configured.");
        }

        return $this->resolveReference($companyId, $reference, $mapKey);
    }

    public function resolveReference(int $companyId, string $reference, ?string $context = null): ChartAccount
    {
        $reference = trim($reference);

        $query = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($reference) {
                $q->where('code', $reference)
                    ->orWhere('slug', $reference);
            });

        /** @var ChartAccount|null $account */
        $account = $query->first();

        if (! $account) {
            $label = $context ? " for key [{$context}]" : '';
            throw (new ModelNotFoundException())->setModel(
                ChartAccount::class,
                ["company_id={$companyId}", "reference={$reference}{$label}"]
            );
        }

        $this->assertPostable($account, $context ? "COA key [{$context}]" : "account [{$reference}]");

        return $account;
    }

    public function assertPostable(ChartAccount $account, string $label = 'account'): ChartAccount
    {
        if ($account->company_id === null) {
            throw new InvalidArgumentException("The {$label} is missing company ownership.");
        }

        if ($account->type !== 'ledger') {
            throw new InvalidArgumentException("The {$label} must resolve to a ledger account.");
        }

        if (! $account->is_active) {
            throw new InvalidArgumentException("The {$label} is inactive.");
        }

        return $account;
    }
}
