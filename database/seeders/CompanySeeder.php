<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // --- Companies (upsert by unique slug) ---
        $companies = [
            [
                'name'            => 'Innovus Limited',
                'slug'            => 'innovus-limited',
                'email'           => 'contact@innovus.com',
                'phone'           => '01712345678',
                'address'         => 'Dhaka, Bangladesh',
                'logo'            => null,
                'industry_type'   => 'IT Services',
                'registration_no' => 'REG-001',
                'website'         => 'https://innovus.com',
                'status'          => 'active',
                'created_by'      => null,
                'updated_by'      => null,
                'deleted_by'      => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'GenesisEdu',
                'slug'            => 'genesisedu',
                'email'           => 'info@genesis.edu',
                'phone'           => '01798765432',
                'address'         => 'Chattogram, Bangladesh',
                'logo'            => null,
                'industry_type'   => 'Education',
                'registration_no' => 'REG-002',
                'website'         => 'https://genesis.edu',
                'status'          => 'active',
                'created_by'      => null,
                'updated_by'      => null,
                'deleted_by'      => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];

        foreach ($companies as $c) {
            DB::table('companies')->updateOrInsert(
                ['slug' => $c['slug']], // unique
                $c
            );
        }

        $innovusId = DB::table('companies')->where('slug', 'innovus-limited')->value('id');
        $genesisId = DB::table('companies')->where('slug', 'genesisedu')->value('id');

        // --- Company Users (per-company accounts; upsert by unique email) ---
        $companyUsers = [
            [
                'company_id'    => $innovusId,
                'name'          => 'Innovus Owner',
                'email'         => 'admin@gmail.com', // unique
                'phone_number'  => '01900000001',
                'password'      => Hash::make('123456'),
                'role'          => 'owner',
                'status'        => 'active',
                'invited_at'    => $now,
                'joined_at'     => $now,
                'last_login_at' => $now,
                'is_primary'    => true,
                'permissions'   => json_encode(['manage_users' => true, 'manage_accounts' => true]),
                'created_by'    => null,
                'updated_by'    => null,
                'deleted_by'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'company_id'    => $genesisId,
                'name'          => 'Genesis Owner',
                'email'         => 'genesis.owner@example.test', // unique
                'phone_number'  => '01900000002',
                'password'      => Hash::make('123456'),
                'role'          => 'owner',
                'status'        => 'active',
                'invited_at'    => $now,
                'joined_at'     => $now,
                'last_login_at' => $now,
                'is_primary'    => true,
                'permissions'   => json_encode(['manage_users' => true, 'manage_accounts' => true]),
                'created_by'    => null,
                'updated_by'    => null,
                'deleted_by'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        foreach ($companyUsers as $cu) {
            DB::table('company_users')->updateOrInsert(
                ['email' => $cu['email']], // unique
                $cu
            );
        }
    }
}
