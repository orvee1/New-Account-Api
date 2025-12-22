<?php

namespace App\Services;

use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerOpeningBalanceService
{
    /**
     * Create journal entry lines for a customer's opening balance.
     *
     * @param  \App\Models\Customer  $customer
     * @return \App\Models\JournalEntry|null
     */
    public function createOpeningBalanceJournal($customer)
    {
        $amount = (float) $customer->opening_balance;
        $type = $customer->opening_balance_type; // 'debit' or 'credit'

        if ($amount <= 0 || !in_array($type, ['debit', 'credit'])) {
            return null;
        }
        $companyId = $customer->company_id;
        // find accounts
        $accountReceivable = $this->getAccountReceivable($companyId);
        $customerAdvance  = $this->getCustomerAdvance($companyId);
        $openingEquity  = $this->getOpeningEquity($companyId);

        if (!$accountReceivable || !$openingEquity || !$customerAdvance) {
            throw new \Exception('Required chart accounts are missing. Run the ChartAccountSeeder.');
        }

        $existing = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('reference_type', Customer::class)
            ->where('reference_id', $customer->id)
            ->where('description', 'like', 'Opening balance for customer:%')
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($customer, $amount, $type, $accountReceivable, $customerAdvance, $openingEquity) {
            $je = JournalEntry::create([
                'entry_date'       => Carbon::today(),
                'company_id'       => $customer->company_id,
                'reference_id'     => $customer->id,
                'reference_type'   => Customer::class,
                'description'      => 'Opening balance for customer : ' . $customer->name ?? '',
                'created_by'       => $customer->created_by,
            ]);

            // If opening balance is DEBIT: Customer owes us => Debit AR, Credit Opening Balances (Equity)
            if ($type === 'debit') {
                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'company_id' => $customer->company_id,
                    'account_id' => $accountReceivable->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Opening balance (DR) - Customer: ' . $customer->name,
                ]);

                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'company_id' => $customer->company_id,
                    'account_id' => $openingEquity->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Offset opening balance (CR)',
                ]);
            } else { // credit
                // If opening is CREDIT: We owe customer => Credit Customer Advance (Liability), Debit Opening Balances (Equity)
                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'company_id' => $customer->company_id,
                    'account_id' => $openingEquity->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Offset opening balance (DR)',
                ]);

                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'company_id' => $customer->company_id,
                    'account_id' => $customerAdvance->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Opening balance (CR) - Customer: ' . $customer->name,
                ]);
            }

            return $je;
        });
    }

    public function getAccountReceivable(int $companyId)
    {
        $accountReceivable = ChartAccount::where('slug', 'account-receivable')
            ->where('company_id', $companyId)
            ->first();

        if (!$accountReceivable) {
            $parent = ChartAccount::where([
                'slug' => 'account-receivable',
                'type' => 'group',
                'company_id' => $companyId,
            ])->first();

            if ($parent) {
                $accountReceivable = ChartAccount::create([
                    'parent_id' => $parent->id,
                    'type' => 'ledger',
                    'company_id' => $companyId,
                    'name' => 'Account Receivable',
                    'slug' => 'account-receivable',
                ]);
                $accountReceivable->path = $parent ? rtrim($parent->path, '/') . '/' . $accountReceivable->id : '/' . $accountReceivable->id;
                $accountReceivable->save();
            }
        }
        return $accountReceivable ?? null;
    }

    public function getCustomerAdvance(int $companyId)
    {
        $customerAdvance = ChartAccount::where('slug', 'customer-advance')->first();

        if (!$customerAdvance) {
            $parent = ChartAccount::where([
                'slug' => 'current-liability',
                'type' => 'group',
                'company_id' => $companyId,
            ])->first();

            if ($parent) {
                $customerAdvance = ChartAccount::create([
                    'parent_id' => $parent->id,
                    'type' => 'ledger',
                    'company_id' => $companyId,
                    'name' => 'Customer Advance',
                    'slug' => 'customer-advance',
                ]);
                $customerAdvance->path = $parent ? rtrim($parent->path, '/') . '/' . $customerAdvance->id : '/' . $customerAdvance->id;
                $customerAdvance->save();
            }
        }

        return $customerAdvance ?? null;
    }
    public function getOpeningEquity(int $companyId)
    {
        $openingEquity = ChartAccount::where('slug', 'opening-balances')->first();

        if (!$openingEquity) {
            $parent = ChartAccount::where([
                'slug' => 'owners-capital',
                'type' => 'group',
                'company_id' => $companyId,
            ])->first();

            if ($parent) {
                $openingEquity = ChartAccount::create([
                    'parent_id' => $parent->id,
                    'type' => 'ledger',
                    'company_id' => $companyId,
                    'name' => 'Opening  Balances',
                    'slug' => 'opening-balances',
                ]);
                $openingEquity->path = $parent ? rtrim($parent->path, '/') . '/' . $openingEquity->id : '/' . $openingEquity->id;
                $openingEquity->save();
            }
        }

        return $openingEquity ?? null;
    }
}
