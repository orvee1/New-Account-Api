<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $vendorPayableAccount  = $vendor->chart_account_id ?: $this->getVendorPayableAccount($companyId);
        $vendorAdvanceAccount  = $this->getVendorAdvanceAccount($companyId);

        if (!$openingBalanceAccount || !$vendorPayableAccount || !$vendorAdvanceAccount) {
            Log::warning("Required chart accounts are missing for Vendor Opening Balance. Vendor ID: {$vendor->id}");
            return null;
        }

        $existing = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('reference_type', Vendor::class)
            ->where('reference_id', $vendor->id)
            ->where('description', 'like', 'Opening Balance for Vendor:%')
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($vendor, $amount, $type, $openingBalanceAccount, $vendorAdvanceAccount, $vendorPayableAccount, $companyId) {

            // Create Journal Entry (Header)
            $journal = JournalEntry::create([
                'company_id'       => $companyId,
                'reference_id'     => $vendor->id,
                'reference_type'   => Vendor::class,
                'entry_date'       => $vendor->opening_balance_date ? Carbon::parse($vendor->opening_balance_date) : Carbon::today(),
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
            ->whereIn('slug', ['opening-balance-adjustment', 'opening-balances'])
            ->first();

        if (!$openingBalanceAdjustment) {
            $parent = ChartAccount::where('company_id', $companyId)
                ->where('type', 'group')
                ->where(function($q) {
                    $q->whereIn('slug', ['others-equity', 'equity'])
                      ->orWhereIn('name', ['Others Equity', 'Equity']);
                })->first();

            if ($parent) {
                $openingBalanceAdjustment = ChartAccount::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'parent_id'  => $parent->id,
                        'name'       => 'Opening Balance Adjustment',
                    ],
                    [
                        'type' => 'ledger',
                        'slug' => 'opening-balance-adjustment',
                    ]
                );
                if (blank($openingBalanceAdjustment->path) || $openingBalanceAdjustment->path === '/') {
                    $openingBalanceAdjustment->path = rtrim($parent->path, '/') . '/' . $openingBalanceAdjustment->id;
                    $openingBalanceAdjustment->save();
                }
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
            ->whereIn('slug', ['vendor-payable', 'accounts-payable'])
            ->where('type', 'ledger')
            ->first();

        if (!$vendorPayable) {
            $parent = ChartAccount::query()
                ->where('company_id', $companyId)
                ->where('type', 'group')
                ->where(function($q) {
                    $q->whereIn('slug', ['ac-payable', 'a-c-payable', 'accounts-payable', 'vendor-payable'])
                      ->orWhereIn('name', ['A/C Payable', 'AC Payable', 'Accounts Payable', 'Vendor Payable']);
                })
                ->first();

            if ($parent) {
                $vendorPayable = ChartAccount::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'parent_id'  => $parent->id,
                        'name'       => 'Vendor payable',
                    ],
                    [
                        'type' => 'ledger',
                        'slug' => 'vendor-payable',
                    ]
                );
                if (blank($vendorPayable->path) || $vendorPayable->path === '/') {
                    $vendorPayable->path = rtrim($parent->path, '/') . '/' . $vendorPayable->id;
                    $vendorPayable->save();
                }
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
            ->where('type', 'ledger')
            ->first();

        if (!$vendorAdvanceAccount) {
            $parent = ChartAccount::where('company_id', $companyId)
                ->where('type', 'group')
                ->where(function($q) {
                    $q->whereIn('slug', ['other-current-liabilities', 'current-liabilities'])
                      ->orWhereIn('name', ['Other Current Liabilities', 'Current Liabilities']);
                })->first();

            if ($parent) {
                $vendorAdvanceAccount = ChartAccount::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'parent_id'  => $parent->id,
                        'name'       => 'Vendor Advance',
                    ],
                    [
                        'type' => 'ledger',
                        'slug' => 'vendor-advance',
                    ]
                );
                if (blank($vendorAdvanceAccount->path) || $vendorAdvanceAccount->path === '/') {
                    $vendorAdvanceAccount->path = rtrim($parent->path, '/') . '/' . $vendorAdvanceAccount->id;
                    $vendorAdvanceAccount->save();
                }
            }
        }
        return $vendorAdvanceAccount->id ?? null;;
    }
}
