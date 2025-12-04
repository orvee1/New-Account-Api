<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Vendor;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\DB;

class VendorOpeningBalanceService
{
    /**
     * Create Opening Balance Journal Entry for Vendor
     */
    public function createOpeningBalanceJournal(Vendor $vendor)
    {
        $amount = (float) $vendor->opening_balance;
        $type = $vendor->opening_balance_type; // 'debit' or 'credit'

        if ($amount <= 0 || !in_array($type, ['debit', 'credit'])) {
            return null;
        }

        // get account ids (you may replace these with config-based values)
        $companyId = $vendor->company_id;

        $openingBalanceAccount = $this->getOpeningBalanceAdjustmentAccount($companyId);
        $vendorPayableAccount  = $this->getVendorPayableAccount($companyId);
        $vendorAdvanceAccount  = $this->getVendorAdvanceAccount($companyId);

        if (!$openingBalanceAccount || !$vendorPayableAccount || !$vendorAdvanceAccount) {
            throw new \Exception('Required chart accounts are missing. Run the ChartAccountSeeder.');
        }

        return DB::transaction(function () use ($vendor, $amount, $type, $openingBalanceAccount, $vendorAdvanceAccount, $vendorPayableAccount, $companyId) {

            // Create Journal Entry (Header)
            $journal = JournalEntry::create([
                'company_id'       => $companyId,
                'reference_id'     => $vendor->id,
                'reference_type'   => Vendor::class,
                'description'      => "Opening Balance for Vendor: " . $vendor->display_name ?? '',
                'created_by'       => $vendor->created_by,
            ]);

            // ============================
            //    DEBIT OPENING BALANCE
            // ============================
            if ($type === 'debit') {

                // Vendor Advance Dr
                JournalLine::create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $vendorAdvanceAccount,
                    'debit'            => $amount,
                    'credit'           => 0,
                ]);

                // Opening Balance Adjustment Cr
                JournalLine::create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $openingBalanceAccount,
                    'debit'            => 0,
                    'credit'           => $amount,
                ]);
            }

            // ============================
            //    CREDIT OPENING BALANCE
            // ============================
            if ($type === 'credit') {

                // Opening Balance Adjustment Dr
                JournalLine::create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $openingBalanceAccount,
                    'debit'            => $amount,
                    'credit'           => 0,
                ]);

                // Vendor Payable Cr
                JournalLine::create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $vendorPayableAccount,
                    'debit'            => 0,
                    'credit'           => $amount,
                ]);
            }

            return $journal;
        });
    }

    /**
     * Delete existing opening balance journal for vendor
     */
    public function deleteOpeningBalanceJournal(Vendor $vendor): void
    {
        JournalEntry::where('company_id', $vendor->company_id)
            ->where('reference_type', Vendor::class)
            ->where('reference_id', $vendor->id)
            ->where('sub_type', 'vendor_opening_balance')
            ->delete();
    }

    /**
     * Get Opening Balance Adjustment account ID
     */
    private function getOpeningBalanceAdjustmentAccount(int $companyId)
    {
        $openingBalanceAdjustment = ChartAccount::where('company_id', $companyId)
            ->where('slug', 'opening-balance-adjustment')
            ->first();

        if (!$openingBalanceAdjustment) {
            $parent = ChartAccount::where([
                'slug' => 'others-equity',
                'type' => 'group',
                'company_id' => $companyId,
            ])->first();

            if ($parent) {
                $openingBalanceAdjustment = ChartAccount::create([
                    'parent_id' => $parent->id,
                    'type' => 'ledger',
                    'company_id' => $companyId,
                    'name' => 'Opening Balance Adjustment',
                    'slug' => 'opening-balance-adjustment',
                ]);
                $openingBalanceAdjustment->path = $parent ? rtrim($parent->path, '/') . '/' . $openingBalanceAdjustment->id : '/' . $openingBalanceAdjustment->id;
                $openingBalanceAdjustment->save();
            }
        }
        return $openingBalanceAdjustment->id ?? null;
    }

    /**
     * Get Vendor Payable account ID
     */
    private function getVendorPayableAccount(int $companyId)
    {
        $vendorPayable = ChartAccount::where('company_id', $companyId)
            ->where('slug', 'vendor-payable')
            ->first();

        if (!$vendorPayable) {
            $parent = ChartAccount::where([
                'slug' => 'ac-payable',
                'type' => 'group',
                'company_id' => $companyId,
            ])->first();

            if ($parent) {
                $vendorPayable = ChartAccount::create([
                    'parent_id' => $parent->id,
                    'type' => 'ledger',
                    'company_id' => $companyId,
                    'name' => 'Vendor payable',
                    'slug' => 'vendor-payable',
                ]);
                $vendorPayable->path = $parent ? rtrim($parent->path, '/') . '/' . $vendorPayable->id : '/' . $vendorPayable->id;
                $vendorPayable->save();
            }
        }
        return $vendorPayable->id ?? null;
    }

    /**
     * Get Vendor Advance account ID
     */
    private function getVendorAdvanceAccount(int $companyId)
    {
        $vendorAdvanceAccount = ChartAccount::where('company_id', $companyId)
            ->where('slug', 'vendor-advance')
            ->first();

        if (!$vendorAdvanceAccount) {
            $parent = ChartAccount::where([
                'slug' => 'other-current-liabilities',
                'type' => 'group',
                'company_id' => $companyId,
            ])->first();

            if ($parent) {
                $vendorAdvanceAccount = ChartAccount::create([
                    'parent_id' => $parent->id,
                    'type' => 'ledger',
                    'company_id' => $companyId,
                    'name' => 'Vendor Advance',
                    'slug' => 'vendor-advance',
                ]);
                $vendorAdvanceAccount->path = $parent ? rtrim($parent->path, '/') . '/' . $vendorAdvanceAccount->id : '/' . $vendorAdvanceAccount->id;
                $vendorAdvanceAccount->save();
            }
        }
        return $vendorAdvanceAccount->id ?? null;;
    }
}
