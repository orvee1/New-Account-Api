<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountType;

class AccountTypeSeeder extends Seeder
{
    public function run()
    {
        // All accounts with children
        $accounts = [
            "Current Asset" => [
                "Cash",
                "Bank A/C-Current",
                "Bank A/C-Saving",
                "Account Receivable",
                "Inventory",
                "Short-Term Investments",
                "Prepaid Expenses",
                "Loans & Advances (Short-Term)",
                "Other Current Assets",
            ],
            "Non-Current Asset" => [
                "Fixed/Tangible Assets",
                "Intangible Assets",
                "Long-term Investments",
                "Other Non-Current Assets",
            ],
            "Current Liability" => [
                "A/C Payable",
                "Short-term Loan",
                "Accrued Expenses",
                "Credit Card",
                "Unearned Revenue",
                "Provisions",
                "Current Portion of Long-term Debt",
                "Other Current Liabilities",
            ],
            "Non-Current Liability" => [
                "Long-term Loans",
                "Bonds Payable",
                "Provisions",
                "Deferred Tax Liabilities",
                "Other Non-Current Liabilities",
            ],
            "Equity" => [
                "Owner's Capital",
                "Owner's Drawings",
                "Retained Earnings",
                "Share Capital",
                "Reserves & Surplus",
                "Others Equity",
            ],
            "Income" => [
                "Operating Income",
                "Non-operating Income",
                "Other Income",
            ],
            "Expense" => [
                "Operating Expenses",
                "Non-Operating Expenses",
                "Others Expenses",
                "Cost of Goods Sold",
            ],
        ];

        $idCounter = 1;
        $parentIds = [];

        // 1️⃣ Create parent accounts first
        foreach ($accounts as $parentName => $children) {
            $parent = AccountType::updateOrCreate(
                ['name' => $parentName, 'parent_id' => 0],
                ['name' => $parentName, 'parent_id' => 0]
            );
            $parentIds[$parentName] = $parent->id;
        }

        // 2️⃣ Create child accounts with parent_id
        foreach ($accounts as $parentName => $children) {
            $parent_id = $parentIds[$parentName];
            foreach ($children as $childName) {
                AccountType::updateOrCreate(
                    ['name' => $childName, 'parent_id' => $parent_id],
                    ['name' => $childName, 'parent_id' => $parent_id]
                );
            }
        }
    }
}
