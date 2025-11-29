<?php
namespace App\Services;

use App\Models\ChartAccount;
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

        if ($amount <= 0 || !in_array($type, ['debit','credit'])) {
            return null;
        }

        // find accounts
        $ar = ChartAccount::where('slug', 'accounts-receivable')->first();
        $advance = ChartAccount::where('slug', 'customer-advance')->first();
        $openingEquity = ChartAccount::where('slug', 'opening-balances')->first();

        if (!$ar || !$openingEquity || !$advance) {
            throw new \Exception('Required chart accounts are missing. Run the ChartAccountSeeder.');
        }

        return DB::transaction(function () use ($customer, $amount, $type, $ar, $advance, $openingEquity) {
            $je = JournalEntry::create([
                'date' => Carbon::today(),
                'reference' => 'OPENING_BAL_' . $customer->id,
                'description' => 'Opening balance for customer ID: ' . $customer->id,
            ]);

            // If opening balance is DEBIT: Customer owes us => Debit AR, Credit Opening Balances (Equity)
            if ($type === 'debit') {
                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'chart_account_id' => $ar->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Opening balance (DR) - Customer: '.$customer->name,
                ]);

                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'chart_account_id' => $openingEquity->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Offset opening balance (CR)',
                ]);
            } else { // credit
                // If opening is CREDIT: We owe customer => Credit Customer Advance (Liability), Debit Opening Balances (Equity)
                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'chart_account_id' => $openingEquity->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Offset opening balance (DR)',
                ]);

                JournalLine::create([
                    'journal_entry_id' => $je->id,
                    'chart_account_id' => $advance->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Opening balance (CR) - Customer: '.$customer->name,
                ]);
            }

            return $je;
        });
    }
}
