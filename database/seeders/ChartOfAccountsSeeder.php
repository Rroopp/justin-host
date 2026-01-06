<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Minimal but practical default Chart of Accounts for a Hospital/Pharmacy POS.
        // Codes are intentionally stable so other modules can map to them.

        $accounts = [
            // ASSETS
            ['code' => '1000', 'name' => 'Cash on Hand', 'account_type' => 'Asset', 'parent_code' => null],
            ['code' => '1010', 'name' => 'Bank Account', 'account_type' => 'Asset', 'parent_code' => null],
            ['code' => '1020', 'name' => 'Mobile Money (M-Pesa)', 'account_type' => 'Asset', 'parent_code' => null],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => 'Asset', 'parent_code' => null],
            ['code' => '1200', 'name' => 'Inventory', 'account_type' => 'Asset', 'parent_code' => null],

            // LIABILITIES
            ['code' => '2000', 'name' => 'VAT Payable', 'account_type' => 'Liability', 'parent_code' => null],
            ['code' => '2100', 'name' => 'Accounts Payable', 'account_type' => 'Liability', 'parent_code' => null],

            // EQUITY
            ['code' => '3000', 'name' => 'Owner/Capital', 'account_type' => 'Equity', 'parent_code' => null],
            ['code' => '3100', 'name' => 'Retained Earnings', 'account_type' => 'Equity', 'parent_code' => '3000'],

            // INCOME
            ['code' => '4000', 'name' => 'Sales Revenue', 'account_type' => 'Income', 'parent_code' => null],
            ['code' => '4100', 'name' => 'Other Income', 'account_type' => 'Income', 'parent_code' => null],

            // EXPENSES
            ['code' => '5000', 'name' => 'Cost of Goods Sold (COGS)', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5100', 'name' => 'Utilities', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5200', 'name' => 'Rent', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5300', 'name' => 'Salaries & Wages', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5400', 'name' => 'Medical Supplies', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5500', 'name' => 'Repairs & Maintenance', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5600', 'name' => 'Transport', 'account_type' => 'Expense', 'parent_code' => null],
            ['code' => '5700', 'name' => 'Miscellaneous Expense', 'account_type' => 'Expense', 'parent_code' => null],
        ];

        // First pass: create all accounts without parents.
        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'account_type' => $acc['account_type'],
                    'parent_id' => null,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        // Second pass: link parents by code.
        foreach ($accounts as $acc) {
            if (!$acc['parent_code']) {
                continue;
            }

            $child = ChartOfAccount::where('code', $acc['code'])->first();
            $parent = ChartOfAccount::where('code', $acc['parent_code'])->first();

            if ($child && $parent) {
                $child->update(['parent_id' => $parent->id]);
            }
        }
    }
}





