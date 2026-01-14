<?php

namespace App\Services;

use App\Models\ChartAccount;

class AccountMappingService
{
    public function cash(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['cash'],
            groupNames: ['Cash'],
            ledgerName: 'Cash on Hand',
            ledgerSlug: 'cash-on-hand'
        );
    }

    public function bank(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['cash-at-bank', 'bank-a-c-current', 'bank-a-c-saving'],
            groupNames: ['Cash at Bank', 'Bank A/C-Current', 'Bank A/C-Saving'],
            ledgerName: 'Bank Account',
            ledgerSlug: 'bank-account'
        );
    }

    public function accountsReceivable(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['account-receivable'],
            groupNames: ['Account Receivable', 'Accounts Receivable'],
            ledgerName: 'Accounts Receivable',
            ledgerSlug: 'accounts-receivable'
        );
    }

    public function accountsPayable(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['ac-payable', 'a-c-payable'],
            groupNames: ['A/C Payable', 'AC Payable', 'Accounts Payable'],
            ledgerName: 'Accounts Payable',
            ledgerSlug: 'accounts-payable'
        );
    }

    public function inventory(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['inventory'],
            groupNames: ['Inventory'],
            ledgerName: 'Inventory',
            ledgerSlug: 'inventory'
        );
    }

    public function salesRevenue(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['sales-revenue'],
            groupNames: ['Sales Revenue'],
            ledgerName: 'Sales Revenue',
            ledgerSlug: 'sales-revenue-ledger'
        );
    }

    public function salesReturn(int $companyId): ?ChartAccount
    {
        return $this->getOrCreateLedger(
            $companyId,
            groupSlugs: ['sales-revenue'],
            groupNames: ['Sales Revenue'],
            ledgerName: 'Sales Return',
            ledgerSlug: 'sales-return'
        );
    }

    private function getOrCreateLedger(
        int $companyId,
        array $groupSlugs,
        array $groupNames,
        string $ledgerName,
        string $ledgerSlug
    ): ?ChartAccount {
        $group = $this->findGroup($companyId, $groupSlugs, $groupNames);
        if (!$group) {
            return null;
        }

        $ledger = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('parent_id', $group->id)
            ->where('slug', $ledgerSlug)
            ->first();

        if ($ledger) {
            return $ledger;
        }

        return ChartAccount::firstOrCreate(
            [
                'company_id' => $companyId,
                'parent_id' => $group->id,
                'name' => $ledgerName,
            ],
            [
                'type' => 'ledger',
                'slug' => $ledgerSlug,
                'is_active' => true,
            ]
        );
    }

    private function findGroup(int $companyId, array $slugs, array $names): ?ChartAccount
    {
        $query = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('type', 'group');

        if (!empty($slugs)) {
            $group = (clone $query)->whereIn('slug', $slugs)->first();
            if ($group) {
                return $group;
            }
        }

        if (!empty($names)) {
            return (clone $query)->whereIn('name', $names)->first();
        }

        return null;
    }
}
