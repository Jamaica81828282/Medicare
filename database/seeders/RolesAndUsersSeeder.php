<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin    = Role::firstOrCreate(['name' => 'admin']);
        $cashier  = Role::firstOrCreate(['name' => 'cashier']);
        $customer = Role::firstOrCreate(['name' => 'customer']);

        // Admin
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@medicare.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('admin123'),
            ]
        );
        $adminUser->syncRoles([$admin]);

        // Cashier
        $cashierUser = User::updateOrCreate(
            ['email' => 'cashier@medicare.com'],
            [
                'name'     => 'Cashier User',
                'password' => Hash::make('cashier123'),
            ]
        );
        $cashierUser->syncRoles([$cashier]);

        // Customer Kiosk (shared account)
        $customerUser = User::updateOrCreate(
            ['email' => 'kiosk@medicare.com'],
            [
                'name'     => 'Customer Kiosk',
                'password' => Hash::make('kiosk123'),
            ]
        );
        $customerUser->syncRoles([$customer]);
    }
}