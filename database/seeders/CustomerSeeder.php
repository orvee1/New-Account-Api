<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Services\CustomerOpeningBalanceService;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate unique customer numbers
        $baseNumber = 'C' . now()->format('ymd');

        $customers = [
            [
                'name' => 'Acme Corporation',
                'company_id' => 1,
                'email' => 'contact@acme.com',
                'phone_number' => '01711111111',
                'address' => '123 Business Street, Dhaka',
                'opening_balance' => 50000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 500000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Global Tech Solutions',
                'company_id' => 1,
                'email' => 'info@globaltech.com',
                'phone_number' => '01722222222',
                'address' => '456 Tech Boulevard, Chittagong',
                'opening_balance' => 75000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 750000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Digital Innovations Ltd',
                'company_id' => 1,
                'email' => 'sales@digitalinno.com',
                'phone_number' => '01733333333',
                'address' => '789 Innovation Park, Sylhet',
                'opening_balance' => 30000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 300000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Premium Traders',
                'company_id' => 1,
                'email' => 'contact@premium.com',
                'phone_number' => '01744444444',
                'address' => '321 Trading Center, Khulna',
                'opening_balance' => 100000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1000000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Sunrise Enterprises',
                'company_id' => 1,
                'email' => 'info@sunrise.com',
                'phone_number' => '01755555555',
                'address' => '654 Enterprise Way, Rajshahi',
                'opening_balance' => 45000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 450000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Blue Sky Industries',
                'company_id' => 1,
                'email' => 'contact@bluesky.com',
                'phone_number' => '01766666666',
                'address' => '987 Industrial Zone, Barisal',
                'opening_balance' => 60000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 600000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'NextGen Technologies',
                'company_id' => 1,
                'email' => 'support@nextgen.com',
                'phone_number' => '01777777777',
                'address' => '147 Tech Park, Rangpur',
                'opening_balance' => 55000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 550000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Stellar Commerce Co',
                'company_id' => 1,
                'email' => 'hello@stellar.com',
                'phone_number' => '01788888888',
                'address' => '258 Commerce Plaza, Mymensingh',
                'opening_balance' => 70000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 700000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Quantum Business Group',
                'company_id' => 1,
                'email' => 'contact@quantum.com',
                'phone_number' => '01799999999',
                'address' => '369 Business District, Narail',
                'opening_balance' => 40000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 400000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Phoenix Retail Group',
                'company_id' => 1,
                'email' => 'sales@phoenix.com',
                'phone_number' => '01800000000',
                'address' => '741 Retail Street, Gazipur',
                'opening_balance' => 85000,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 850000,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ];

        $openingService = app(CustomerOpeningBalanceService::class);

        foreach ($customers as $index => $customer) {
            // Add unique customer number for each record
            $customer['customer_number'] = $baseNumber . '-' . (1000 + $index + 1);
            $created = Customer::updateOrCreate(
                [
                    'company_id' => $customer['company_id'],
                    'customer_number' => $customer['customer_number'],
                ],
                $customer
            );
            if ($created->opening_balance > 0 && in_array($created->opening_balance_type, ['debit', 'credit'], true)) {
                $openingService->createOpeningBalanceJournal($created);
            }
        }

        $this->command->info('✅ 10 Customers seeded successfully!');
    }
}
