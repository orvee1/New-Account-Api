<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CompanyUser;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create company first
        if (class_exists(\Database\Seeders\CompanySeeder::class)) {
            $this->call(CompanySeeder::class);
        }

        if (class_exists(\Database\Seeders\RolePermissionSeeder::class)) {
            $this->call(RolePermissionSeeder::class);
        }

        if (class_exists(\Database\Seeders\MenuSeeder::class)) {
            $this->call(MenuSeeder::class);
        }

        if (class_exists(\Database\Seeders\AdminDeviceLogSeeder::class)) {
            $this->call(AdminDeviceLogSeeder::class);
        }

        if (class_exists(\Database\Seeders\AccountTypeSeeder::class)) {
            $this->call(AccountTypeSeeder::class);
        }

        if (class_exists(\Database\Seeders\AccountLedgerSeeder::class)) {
            // Commented out due to reference_id type error
            // $this->call(AccountLedgerSeeder::class);
        }

        if (class_exists(\Database\Seeders\DefaultChartOfAccountsSeeder::class)) {
            $this->call(DefaultChartOfAccountsSeeder::class);
        }

        if (class_exists(\Database\Seeders\ProductSeeder::class)) {
            $this->call(ProductSeeder::class);
        }

        if (class_exists(\Database\Seeders\CustomerSeeder::class)) {
            $this->call(CustomerSeeder::class);
        }

        if (class_exists(\Database\Seeders\VendorSeeder::class)) {
            $this->call(VendorSeeder::class);
        }


        // Create Company Users for API authentication (using CompanyUser model)
        $admin = CompanyUser::updateOrCreate(
            ['email' => 'jahirul.iit5th@gmail.com'],
            [
                'name' => 'Jahir',
                'phone_number' => '01893309078',
                'password' => Hash::make('123456'),
                'company_id' => 1, // Assign to company created by CompanySeeder
            ]
        );

        $admin3 = CompanyUser::updateOrCreate(
            ['email' => 'orvee.imrul32@gmail.com'],
            [
                'name' => 'Orvee',
                'phone_number' => '01617794123',
                'password' => Hash::make('123456'),
                'company_id' => 1,
            ]
        );

        // Note: Role assignment is handled separately for CompanyUser model
    }
}
