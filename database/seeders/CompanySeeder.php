<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Default Company',
                'status' => 'active',
            ]
        );

        $this->command->info('✅ Company created successfully!');
    }
}
