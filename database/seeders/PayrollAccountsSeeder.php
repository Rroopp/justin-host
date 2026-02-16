<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\PayrollDeductionType;
use App\Models\PayrollContributionType;
use Illuminate\Database\Seeder;

class PayrollAccountsSeeder extends Seeder
{
    /**
     * Seed payroll-related accounts and deduction/contribution types
     */
    public function run(): void
    {
        // 1. Create Payroll Accounts in Chart of Accounts
        $accounts = [
            // Expense Accounts
            ['code' => 'SALARY_EXPENSE', 'name' => 'Salary Expense', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => true],
            ['code' => 'EMPLOYER_NSSF_EXPENSE', 'name' => 'Employer NSSF Contribution', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => true],
            ['code' => 'EMPLOYER_NHIF_EXPENSE', 'name' => 'Employer NHIF Contribution', 'account_type' => 'Expense', 'sub_type' => 'Operating Expense', 'normal_balance' => 'DEBIT', 'is_system' => true],
            
            // Liability Accounts
            ['code' => 'SALARY_PAYABLE', 'name' => 'Salary Payable', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => true],
            ['code' => 'PAYE_PAYABLE', 'name' => 'PAYE Tax Payable', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => true],
            ['code' => 'NSSF_PAYABLE', 'name' => 'NSSF Payable', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => true],
            ['code' => 'NHIF_PAYABLE', 'name' => 'NHIF Payable', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => true],
            ['code' => 'LOAN_DEDUCTIONS_PAYABLE', 'name' => 'Loan Deductions Payable', 'account_type' => 'Liability', 'sub_type' => 'Current Liability', 'normal_balance' => 'CREDIT', 'is_system' => false],
        ];

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
                ]
            );
        }

        // 2. Create Deduction Types
        $payeAccount = ChartOfAccount::where('code', 'PAYE_PAYABLE')->first();
        $nssfAccount = ChartOfAccount::where('code', 'NSSF_PAYABLE')->first();
        $nhifAccount = ChartOfAccount::where('code', 'NHIF_PAYABLE')->first();
        $loanAccount = ChartOfAccount::where('code', 'LOAN_DEDUCTIONS_PAYABLE')->first();

        $deductionTypes = [
            ['code' => 'PAYE', 'name' => 'PAYE Tax', 'type' => 'STATUTORY', 'is_statutory' => true, 'liability_account_id' => $payeAccount?->id],
            ['code' => 'NSSF_EE', 'name' => 'NSSF (Employee)', 'type' => 'STATUTORY', 'is_statutory' => true, 'liability_account_id' => $nssfAccount?->id],
            ['code' => 'NHIF_EE', 'name' => 'NHIF (Employee)', 'type' => 'STATUTORY', 'is_statutory' => true, 'liability_account_id' => $nhifAccount?->id],
            ['code' => 'LOAN', 'name' => 'Loan Deduction', 'type' => 'LOAN', 'is_statutory' => false, 'liability_account_id' => $loanAccount?->id],
            ['code' => 'ADVANCE', 'name' => 'Salary Advance', 'type' => 'ADVANCE', 'is_statutory' => false, 'liability_account_id' => null],
        ];

        foreach ($deductionTypes as $deduction) {
            PayrollDeductionType::firstOrCreate(
                ['code' => $deduction['code']],
                $deduction
            );
        }

        // 3. Create Employer Contribution Types
        $employerNssfExpense = ChartOfAccount::where('code', 'EMPLOYER_NSSF_EXPENSE')->first();
        $employerNhifExpense = ChartOfAccount::where('code', 'EMPLOYER_NHIF_EXPENSE')->first();

        $contributionTypes = [
            [
                'code' => 'NSSF_ER',
                'name' => 'NSSF (Employer)',
                'expense_account_id' => $employerNssfExpense?->id,
                'liability_account_id' => $nssfAccount?->id,
            ],
            [
                'code' => 'NHIF_ER',
                'name' => 'NHIF (Employer)',
                'expense_account_id' => $employerNhifExpense?->id,
                'liability_account_id' => $nhifAccount?->id,
            ],
        ];

        foreach ($contributionTypes as $contribution) {
            PayrollContributionType::firstOrCreate(
                ['code' => $contribution['code']],
                $contribution
            );
        }

        $this->command->info('Payroll accounts and types seeded successfully.');
        $this->command->info('- 8 Chart of Accounts entries');
        $this->command->info('- 5 Deduction Types');
        $this->command->info('- 2 Employer Contribution Types');
    }
}
