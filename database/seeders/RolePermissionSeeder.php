<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // clear cached permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'menus',
            'admin.device',
            'course',
            'course.session',
            'course.package',
            'batch',
            'institute',
            'institute.faculty',
            'institute.discipline',
            'topic',
            'module',
            'doctor',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin = Role::firstOrCreate(['name' => 'Administrator']);
        $deleloper = Role::firstOrCreate(['name' => 'Developer']);

        $admin->syncPermissions(Permission::all());
        $deleloper->syncPermissions(Permission::all());
    }
}
