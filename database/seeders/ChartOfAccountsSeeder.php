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
        // Enterprise-grade Chart of Accounts with system account flags
        // System accounts CANNOT be deleted and are required for posting logic
        
        $accounts = [
            // ASSETS (Normal Balance: DEBIT)
            ['code' => 'CASH', 'name' => 'Cash on Hand', 'account_type' => 'Asset', 'sub_type' => 'Current Asset', 'normal_balance' => 'DEBIT', 'is_system' => true, 'parent_code' => null],
            ['code' => '1010', 'name' => 'Bank Account', 'account_type' => 'Asset', 'sub_type' => 'Current Asset', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '1020', 'name' => 'Mobile Money (M-Pesa)', 'account_type' => 'Asset', 'sub_type' => 'Current Asset', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => 'ACCOUNTS_RECEIVABLE', 'name' => 'Accounts Receivable', 'account_type' => 'Asset', 'sub_type' => 'Current Asset', 'normal_balance' => 'DEBIT', 'is_system' => true, 'parent_code' => null],
            ['code' => 'INVENTORY', 'name' => 'Inventory', 'account_type' => 'Asset', 'sub_type' => 'Current Asset', 'normal_balance' => 'DEBIT', 'is_system' => true, 'parent_code' => null],

            // LIABILITIES (Normal Balance: CREDIT)
            ['code' => 'VAT_PAYABLE', 'name' => 'VAT Payable (Output Tax)', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => true, 'parent_code' => null],
            ['code' => 'VAT_RECEIVABLE', 'name' => 'VAT Receivable (Input Tax)', 'account_type' => 'Asset', 'sub_type' => 'Current Asset', 'normal_balance' => 'DEBIT', 'is_system' => true, 'parent_code' => null],
            ['code' => 'ACCOUNTS_PAYABLE', 'name' => 'Accounts Payable', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => true, 'parent_code' => null],

            // EQUITY (Normal Balance: CREDIT)
            ['code' => '3000', 'name' => 'Owner Capital', 'account_type' => 'Equity', 'sub_type' => 'Capital', 'normal_balance' => 'CREDIT', 'is_system' => false, 'parent_code' => null],
            ['code' => 'RETAINED_EARNINGS', 'name' => 'Retained Earnings', 'account_type' => 'Equity', 'sub_type' => 'Retained Earnings', 'normal_balance' => 'CREDIT', 'is_system' => true, 'parent_code' => null],

            // INCOME (Normal Balance: CREDIT)
            ['code' => 'SALES_REVENUE', 'name' => 'Sales Revenue', 'account_type' => 'Income', 'sub_type' => 'Operating Revenue', 'normal_balance' => 'CREDIT', 'is_system' => true, 'parent_code' => null],
            ['code' => '4100', 'name' => 'Other Income', 'account_type' => 'Income', 'sub_type' => 'Non-Operating Revenue', 'normal_balance' => 'CREDIT', 'is_system' => false, 'parent_code' => null],

            // EXPENSES (Normal Balance: DEBIT)
            ['code' => 'COST_OF_GOODS_SOLD', 'name' => 'Cost of Goods Sold (COGS)', 'account_type' => 'Expense', 'sub_type' => 'Cost of Sales', 'normal_balance' => 'DEBIT', 'is_system' => true, 'parent_code' => null],
            ['code' => '5100', 'name' => 'Utilities', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '5200', 'name' => 'Rent', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '5300', 'name' => 'Salaries & Wages', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '5400', 'name' => 'Medical Supplies', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '5500', 'name' => 'Repairs & Maintenance', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '5600', 'name' => 'Transport', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            ['code' => '5700', 'name' => 'Miscellaneous Expense', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => false, 'parent_code' => null],
            
            // SUSPENSE ACCOUNT (for error handling)
            ['code' => 'SUSPENSE', 'name' => 'Suspense Account', 'account_type' => 'Asset', 'sub_type' => 'Temporary', 'normal_balance' => 'DEBIT', 'is_system' => true, 'parent_code' => null],
        ];

        // First pass: create all accounts
        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'account_type' => $acc['account_type'],
                    'sub_type' => $acc['sub_type'],
                    'normal_balance' => $acc['normal_balance'],
                    'is_system' => $acc['is_system'],
                    'is_locked' => false,
                    'is_active' => true,
                    'currency' => 'KES',
                    'parent_id' => null,
                    'description' => null,
                ]
            );
        }

        // Second pass: link parents by code
        foreach ($accounts as $acc) {
            if (!isset($acc['parent_code']) || !$acc['parent_code']) {
                continue;
            }

            $child = ChartOfAccount::where('code', $acc['code'])->first();
            $parent = ChartOfAccount::where('code', $acc['parent_code'])->first();

            if ($child && $parent) {
                $child->update(['parent_id' => $parent->id]);
            }
        }
        
        $this->command->info('Chart of Accounts seeded with ' . count($accounts) . ' accounts.');
        $this->command->info('System accounts (is_system=true) are protected from deletion.');
    }
}





