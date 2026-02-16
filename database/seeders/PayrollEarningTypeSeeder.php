<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayrollEarningType;

class PayrollEarningTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Basic Salary',
                'code' => 'BASIC',
                'is_taxable' => true,
                'is_recurring' => true,
            ],
            [
                'name' => 'House Allowance',
                'code' => 'HOUSE_ALLOWANCE',
                'is_taxable' => true,
                'is_recurring' => true,
            ],
            [
                'name' => 'Transport Allowance',
                'code' => 'TRANSPORT_ALLOWANCE',
                'is_taxable' => true,
                'is_recurring' => true,
            ],
            [
                'name' => 'Overtime',
                'code' => 'OVERTIME',
                'is_taxable' => true,
                'is_recurring' => false,
            ],
            [
                'name' => 'Bonus',
                'code' => 'BONUS',
                'is_taxable' => true,
                'is_recurring' => false,
            ],
        ];

        foreach ($types as $type) {
            PayrollEarningType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
