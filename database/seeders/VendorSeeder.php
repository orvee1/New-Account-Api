<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Services\VendorOpeningBalanceService;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate unique vendor numbers
        $baseNumber = 'V' . now()->format('ymd');

        $vendors = [
            [
                'name' => 'TechWorld Suppliers',
                'display_name' => 'TechWorld',
                'proprietor_name' => 'Mr. Ahmed Hassan',
                'email' => 'vendor1@techworld.com',
                'phone_number' => '01511111111',
                'address' => '100 Supply Street, Dhaka',
                'nid' => '1234567890123',
                'bank_details' => 'Bank: Dhaka Bank, Account: 123456789',
                'opening_balance' => 150000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1500000,
                'notes' => 'Reliable vendor with good delivery record',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Digital Components Ltd',
                'display_name' => 'Digital Components',
                'proprietor_name' => 'Ms. Fatima Khan',
                'email' => 'vendor2@digital.com',
                'phone_number' => '01522222222',
                'address' => '200 Component Way, Chittagong',
                'nid' => '2234567890123',
                'bank_details' => 'Bank: Standard Bank, Account: 987654321',
                'opening_balance' => 200000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 2000000,
                'notes' => 'Quality assured components supplier',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Hardware Hub International',
                'display_name' => 'Hardware Hub',
                'proprietor_name' => 'Mr. Kamal Rahman',
                'email' => 'vendor3@hardwarehub.com',
                'phone_number' => '01533333333',
                'address' => '300 Hardware Plaza, Sylhet',
                'nid' => '3234567890123',
                'bank_details' => 'Bank: Eastern Bank, Account: 456789012',
                'opening_balance' => 120000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1200000,
                'notes' => 'Wholesale hardware supplier',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Wholesale Electronics Co',
                'display_name' => 'Wholesale Electronics',
                'proprietor_name' => 'Mr. Shahid Hossain',
                'email' => 'vendor4@wholesale.com',
                'phone_number' => '01544444444',
                'address' => '400 Electronics Market, Khulna',
                'nid' => '4234567890123',
                'bank_details' => 'Bank: BRAC Bank, Account: 789012345',
                'opening_balance' => 180000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1800000,
                'notes' => 'Bulk electronics supplier',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Premium IT Solutions',
                'display_name' => 'Premium IT',
                'proprietor_name' => 'Dr. Abdur Rahim',
                'email' => 'vendor5@premiumit.com',
                'phone_number' => '01555555555',
                'address' => '500 IT Complex, Rajshahi',
                'nid' => '5234567890123',
                'bank_details' => 'Bank: Islami Bank, Account: 012345678',
                'opening_balance' => 160000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1600000,
                'notes' => 'High quality IT equipment distributor',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Global Supply Chain',
                'display_name' => 'Global Supply',
                'proprietor_name' => 'Mr. Jamal Khan',
                'email' => 'vendor6@globalsupply.com',
                'phone_number' => '01566666666',
                'address' => '600 Supply Hub, Barisal',
                'nid' => '6234567890123',
                'bank_details' => 'Bank: Sonali Bank, Account: 345678901',
                'opening_balance' => 140000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1400000,
                'notes' => 'International import-export vendor',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Metro Tech Distributors',
                'display_name' => 'Metro Tech',
                'proprietor_name' => 'Ms. Noor Jahan',
                'email' => 'vendor7@metrotech.com',
                'phone_number' => '01577777777',
                'address' => '700 Metro Plaza, Rangpur',
                'nid' => '7234567890123',
                'bank_details' => 'Bank: Agrani Bank, Account: 678901234',
                'opening_balance' => 130000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1300000,
                'notes' => 'Metropolitan area distributor',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Eastern Traders Association',
                'display_name' => 'Eastern Traders',
                'proprietor_name' => 'Mr. Imran Sheikh',
                'email' => 'vendor8@easterntraders.com',
                'phone_number' => '01588888888',
                'address' => '800 Trading Square, Mymensingh',
                'nid' => '8234567890123',
                'bank_details' => 'Bank: Rupali Bank, Account: 901234567',
                'opening_balance' => 170000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1700000,
                'notes' => 'Regional trading partnership',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Century Technology Partners',
                'display_name' => 'Century Tech',
                'proprietor_name' => 'Mr. Asif Ali',
                'email' => 'vendor9@centurytech.com',
                'phone_number' => '01599999999',
                'address' => '900 Tech Avenue, Narail',
                'nid' => '9234567890123',
                'bank_details' => 'Bank: Janata Bank, Account: 234567890',
                'opening_balance' => 110000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1100000,
                'notes' => 'Technology partnership firm',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Advanced Supply Systems',
                'display_name' => 'Advanced Supply',
                'proprietor_name' => 'Mr. Rashid Mahmud',
                'email' => 'vendor10@advancedsupply.com',
                'phone_number' => '01600000000',
                'address' => '1000 Advanced Street, Gazipur',
                'nid' => '0234567890123',
                'bank_details' => 'Bank: Mutual Bank, Account: 567890123',
                'opening_balance' => 190000,
                'opening_balance_type' => 'credit',
                'opening_balance_date' => now()->subDays(30),
                'credit_limit' => 1900000,
                'notes' => 'Advanced logistics and supply partner',
                'company_id' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ];

        $openingService = app(VendorOpeningBalanceService::class);

        foreach ($vendors as $index => $vendor) {
            // Add unique vendor number for each record
            $vendor['vendor_number'] = $baseNumber . '-' . (2000 + $index + 1);
            $created = Vendor::updateOrCreate(
                [
                    'company_id' => $vendor['company_id'],
                    'vendor_number' => $vendor['vendor_number'],
                ],
                $vendor
            );
            if ($created->opening_balance > 0 && in_array($created->opening_balance_type, ['debit', 'credit'], true)) {
                $openingService->createOpeningBalanceJournal($created);
            }
        }

        $this->command->info('✅ 10 Vendors seeded successfully!');
    }
}
