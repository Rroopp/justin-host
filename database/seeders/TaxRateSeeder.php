<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxRate;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default tax rates
        // Default: 0% (Zero Rated)
        TaxRate::create([
            'name' => 'Zero Rated (Default)',
            'rate' => 0.00,
            'type' => 'percentage',
            'is_default' => true,
            'is_active' => true,
            'description' => 'Default tax rate - No tax applied',
        ]);

        // VAT 16%
        TaxRate::create([
            'name' => 'VAT 16%',
            'rate' => 16.00,
            'type' => 'percentage',
            'is_default' => false,
            'is_active' => true,
            'description' => 'Standard VAT rate for vatable items',
        ]);

        // Exempt
        TaxRate::create([
            'name' => 'Exempt',
            'rate' => 0.00,
            'type' => 'percentage',
            'is_default' => false,
            'is_active' => true,
            'description' => 'Tax exempt items',
        ]);

        echo "Tax rates seeded successfully.\n";
    }
}
