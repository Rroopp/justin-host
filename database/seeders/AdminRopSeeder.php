<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminRopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staff = Staff::firstOrCreate(
            ['username' => 'Rop'],
            [
                'full_name' => 'Admin Rop',
                'role' => 'admin',
                'status' => 'active',
                'email' => 'rop@hospital.com',
                'phone' => '+254700000002',
                // Set initial password
                'password_hash' => Hash::make('@Kipkosgei.21'),
            ]
        );

        // Always update password and role to ensure they match requirements even if user existed
        $staff->password_hash = Hash::make('@Kipkosgei.21');
        $staff->role = 'admin';
        $staff->save();
    }
}
