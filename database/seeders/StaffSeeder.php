<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user (only if doesn't exist)
        Staff::firstOrCreate(
            ['username' => 'admin'],
            [
                'password_hash' => Hash::make('admin123'),
                'full_name' => 'System Administrator',
                'roles' => ['admin'],
                'primary_role' => 'admin',
                'status' => 'active',
                'email' => 'admin@hospital.com',
                'phone' => '+254700000000',
            ]
        );

        // Create sample POS clerk (only if doesn't exist)
        Staff::firstOrCreate(
            ['username' => 'cashier'],
            [
                'password_hash' => Hash::make('cashier123'),
                'full_name' => 'Cashier User',
                'roles' => ['pos_clerk', 'cashier'],
                'primary_role' => 'pos_clerk',
                'status' => 'active',
                'email' => 'cashier@hospital.com',
                'phone' => '+254700000001',
            ]
        );
    }
}
