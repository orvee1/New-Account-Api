<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Helper to create/update a menu item and return the model
        $make = function (
            string $title,
            int $parent_id = 0,
            ?string $url = null,
            ?string $permission = null,
            ?string $icon = null
        ): Menu {
            return Menu::updateOrCreate(
                ['title' => $title, 'parent_id' => $parent_id],
                [
                    'url'        => $url,
                    'permission' => $permission,
                    'icon'       => $icon,
                ]
            );
        };

        // ----- Top-level -----
        $make('Admin Device Log', 0, '/admin/admin-device-log', 'admin.device', 'fas fa-tasks');
        $make('Companies', 0, '/admin/companies', 'company.view', 'fas fa-building');
        $make('Users', 0, '/admin/users', 'user.view', 'fas fa-users');
        $make('Roles & Permissions', 0, '/admin/roles', 'role.view', 'fas fa-user-shield');
        $make('Settings', 0, '/admin/settings', 'setting.view', 'fas fa-cogs');
        $make('Company users', 0, '/admin/company-users', 'company.user.view', 'fas fa-user-friends');
    }
}
