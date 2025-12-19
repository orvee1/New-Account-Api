<?php

namespace Database\Seeders;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AccountLedgerSeeder extends Seeder
{
    public function run(): void
    {
        // Get first company (যদি কোনো না থাকে তাহলে এড়িয়ে যান)
        $company = Company::first();
        if (!$company) {
            $this->command->warn('No company found. Please create a company first.');
            return;
        }

        $this->command->info('Creating dummy accounts...');

        // Cash account তৈরি করুন (যদি না থাকে)
        $cashAccount = ChartAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Cash',
                'code' => '1010',
            ],
            [
                'type' => 'ledger',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // Bank account
        $bankAccount = ChartAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Bank Account',
                'code' => '1020',
            ],
            [
                'type' => 'ledger',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        // Sales account
        $salesAccount = ChartAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Sales Revenue',
                'code' => '4010',
            ],
            [
                'type' => 'ledger',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        // Expenses account
        $expensesAccount = ChartAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Office Expenses',
                'code' => '5010',
            ],
            [
                'type' => 'ledger',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 4,
            ]
        );

        $this->command->info('Creating dummy transactions for Cash Account...');

        // Opening balance transaction
        $openingEntry = JournalEntry::create([
            'company_id' => $company->id,
            'entry_date' => Carbon::now()->subDays(30)->toDateString(),
            'reference_id' => 'OB-001',
            'reference_type' => 'Opening Balance',
            'description' => 'Opening Balance - Cash',
            'created_by' => null,
        ]);

        JournalLine::create([
            'journal_entry_id' => $openingEntry->id,
            'company_id' => $company->id,
            'account_id' => $cashAccount->id,
            'debit' => 10000,
            'credit' => 0,
            'memo' => 'Opening Cash Balance',
        ]);

        // Sales transaction 1
        $sale1 = JournalEntry::create([
            'company_id' => $company->id,
            'entry_date' => Carbon::now()->subDays(25)->toDateString(),
            'reference_id' => 'SAL-001',
            'reference_type' => 'Sales',
            'description' => 'Cash Sale - Product A',
            'created_by' => null,
        ]);

        JournalLine::create([
            'journal_entry_id' => $sale1->id,
            'company_id' => $company->id,
            'account_id' => $cashAccount->id,
            'debit' => 5000,
            'credit' => 0,
            'memo' => 'Sale proceeds',
        ]);

        JournalLine::create([
            'journal_entry_id' => $sale1->id,
            'company_id' => $company->id,
            'account_id' => $salesAccount->id,
            'debit' => 0,
            'credit' => 5000,
            'memo' => 'Sales income',
        ]);

        // Expense transaction
        $expense1 = JournalEntry::create([
            'company_id' => $company->id,
            'entry_date' => Carbon::now()->subDays(20)->toDateString(),
            'reference_id' => 'EXP-001',
            'reference_type' => 'Expense',
            'description' => 'Office Supplies Purchase',
            'created_by' => null,
        ]);

        JournalLine::create([
            'journal_entry_id' => $expense1->id,
            'company_id' => $company->id,
            'account_id' => $expensesAccount->id,
            'debit' => 2000,
            'credit' => 0,
            'memo' => 'Office supplies',
        ]);

        JournalLine::create([
            'journal_entry_id' => $expense1->id,
            'company_id' => $company->id,
            'account_id' => $cashAccount->id,
            'debit' => 0,
            'credit' => 2000,
            'memo' => 'Paid for supplies',
        ]);

        // Bank transfer
        $transfer1 = JournalEntry::create([
            'company_id' => $company->id,
            'entry_date' => Carbon::now()->subDays(15)->toDateString(),
            'reference_id' => 'TRF-001',
            'reference_type' => 'Transfer',
            'description' => 'Cash deposited to Bank',
            'created_by' => null,
        ]);

        JournalLine::create([
            'journal_entry_id' => $transfer1->id,
            'company_id' => $company->id,
            'account_id' => $bankAccount->id,
            'debit' => 8000,
            'credit' => 0,
            'memo' => 'Deposit to bank',
        ]);

        JournalLine::create([
            'journal_entry_id' => $transfer1->id,
            'company_id' => $company->id,
            'account_id' => $cashAccount->id,
            'debit' => 0,
            'credit' => 8000,
            'memo' => 'Transferred to bank',
        ]);

        // Sales transaction 2
        $sale2 = JournalEntry::create([
            'company_id' => $company->id,
            'entry_date' => Carbon::now()->subDays(10)->toDateString(),
            'reference_id' => 'SAL-002',
            'reference_type' => 'Sales',
            'description' => 'Cash Sale - Product B',
            'created_by' => null,
        ]);

        JournalLine::create([
            'journal_entry_id' => $sale2->id,
            'company_id' => $company->id,
            'account_id' => $cashAccount->id,
            'debit' => 3500,
            'credit' => 0,
            'memo' => 'Sale proceeds',
        ]);

        JournalLine::create([
            'journal_entry_id' => $sale2->id,
            'company_id' => $company->id,
            'account_id' => $salesAccount->id,
            'debit' => 0,
            'credit' => 3500,
            'memo' => 'Sales income',
        ]);

        $this->command->info('✅ Dummy account data created successfully!');
        $this->command->info("Cash Account ID: {$cashAccount->id}");
        $this->command->info("Bank Account ID: {$bankAccount->id}");
        $this->command->info("Sales Account ID: {$salesAccount->id}");
        $this->command->info("Expenses Account ID: {$expensesAccount->id}");
    }
}
