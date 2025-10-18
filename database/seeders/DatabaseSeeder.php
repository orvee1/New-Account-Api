<?php

namespace Database\Seeders;

use App\Models\User;
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


        $admin = User::updateOrCreate(
            ['email' => 'jahirul.iit5th@gmail.com'],
            [
                'name' => 'Jahir',
                'phone_number' => '01893309078',
                'password' => Hash::make('123456'), // CHANGE in prod
            ]
        );

        $admin3 = User::updateOrCreate(
            ['email' => 'orvee.imrul32@gmail.com'],
            [
                'name' => 'Orvee',
                'phone_number' => '01617794123',
                'password' => Hash::make('123456'),
            ]
        );

        // Spatie role assign (থাকলে)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $admin->assignRole('Administrator');
            $admin3->assignRole('Administrator');
        }
    }
}
