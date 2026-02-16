<?php

namespace App\Services\Payroll;

use App\Models\PayrollRun;
use App\Models\ChartOfAccount;
use App\Services\Accounting\JournalEntryService;
use Exception;

/**
 * PayrollPostingService
 * 
 * Handles the integration between Payroll and Accounting.
 * Translates payroll data into Journal Entries.
 */
class PayrollPostingService
{
    protected JournalEntryService $journalService;

    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Post Payroll Accrual (Creates Liabilities)
     * 
     * Debits:
     * - Salary Expense (Gross Pay)
     * - Employer NSSF Expense
     * - Employer NHIF Expense
     * 
     * Credits:
     * - Salary Payable (Net Pay)
     * - PAYE Payable
     * - NSSF Payable (Employee + Employer)
     * - NHIF Payable (Employee + Employer)
     * - Loan Deduction Payable
     */
    public function postPayrollAccrual(PayrollRun $run)
    {
        $lines = [];
        
        // 1. Get Accounts
        $salaryExpense = $this->getSystemAccount('SALARY_EXPENSE');
        $salaryPayable = $this->getSystemAccount('SALARY_PAYABLE');
        
        $payePayable = $this->getSystemAccount('PAYE_PAYABLE');
        $nssfPayable = $this->getSystemAccount('NSSF_PAYABLE');
        $nhifPayable = $this->getSystemAccount('NHIF_PAYABLE');
        
        $erNssfExpense = $this->getSystemAccount('EMPLOYER_NSSF_EXPENSE');
        $erNhifExpense = $this->getSystemAccount('EMPLOYER_NHIF_EXPENSE');

        // 2. Debit: Salary Expense (Total Gross)
        if ($run->total_gross > 0) {
            $lines[] = [
                'account_id' => $salaryExpense->id,
                'debit' => $run->total_gross,
                'credit' => 0,
                'description' => "Gross Salaries for " . $run->period_start->format('M Y'),
            ];
        }

        // 3. Aggregate Deductions for Credits
        // In a real system, we might want to query the payroll_deductions table grouped by type
        // For now, we'll iterate through the deduction records
        
        $payeTotal = $run->deductions()->whereHas('deductionType', fn($q) => $q->where('code', 'PAYE'))->sum('amount');
        $nssfEmployeeTotal = $run->deductions()->whereHas('deductionType', fn($q) => $q->where('code', 'NSSF_EE'))->sum('amount');
        $nhifEmployeeTotal = $run->deductions()->whereHas('deductionType', fn($q) => $q->where('code', 'NHIF_EE'))->sum('amount');
        
        // Other deductions (Loans, etc)
        $loanTotal = $run->deductions()->whereHas('deductionType', fn($q) => $q->where('code', 'LOAN'))->sum('amount');
        // Assuming Loan Payable account exists or we use a generic deduction payable
        $loanPayable = ChartOfAccount::where('code', 'LOAN_DEDUCTIONS_PAYABLE')->first();

        // 4. Aggregate Employer Contributions
        $nssfEmployerTotal = $run->employerContributions()->whereHas('contributionType', fn($q) => $q->where('code', 'NSSF_ER'))->sum('amount');
        $nhifEmployerTotal = $run->employerContributions()->whereHas('contributionType', fn($q) => $q->where('code', 'NHIF_ER'))->sum('amount');

        // 5. Debit: Employer Expenses
        if ($nssfEmployerTotal > 0) {
            $lines[] = [
                'account_id' => $erNssfExpense->id,
                'debit' => $nssfEmployerTotal,
                'credit' => 0,
                'description' => "Employer NSSF Contribution",
            ];
        }

        if ($nhifEmployerTotal > 0) {
            $lines[] = [
                'account_id' => $erNhifExpense->id,
                'debit' => $nhifEmployerTotal,
                'credit' => 0,
                'description' => "Employer NHIF Contribution",
            ];
        }

        // 6. Credit: Liabilities
        
        // PAYE Payable
        if ($payeTotal > 0) {
            $lines[] = [
                'account_id' => $payePayable->id,
                'debit' => 0,
                'credit' => $payeTotal,
                'description' => "PAYE Tax Liability",
            ];
        }

        // NSSF Payable (Employee + Employer)
        $totalNssf = $nssfEmployeeTotal + $nssfEmployerTotal;
        if ($totalNssf > 0) {
            $lines[] = [
                'account_id' => $nssfPayable->id,
                'debit' => 0,
                'credit' => $totalNssf,
                'description' => "NSSF Liability (EE+ER)",
            ];
        }

        // NHIF Payable (Employee + Employer)
        $totalNhif = $nhifEmployeeTotal + $nhifEmployerTotal;
        if ($totalNhif > 0) {
            $lines[] = [
                'account_id' => $nhifPayable->id,
                'debit' => 0,
                'credit' => $totalNhif,
                'description' => "NHIF Liability (EE+ER)",
            ];
        }

        // Loan Deductions
        if ($loanTotal > 0 && $loanPayable) {
            $lines[] = [
                'account_id' => $loanPayable->id,
                'debit' => 0,
                'credit' => $loanTotal,
                'description' => "Staff Loan Repayments",
            ];
        }

        // Salary Payable (Net Pay)
        // Net Pay = Gross - (Employee Deductions)
        // OR Net Pay = Run Total Net (if recorded correctly)
        // Let's use the calculated Net from the run to be safe, but verifying:
        // Equation: Debits (Gross + ER Contribs) = Credits (Liabilities + Net Pay)
        
        // Let's rely on the run's total net which should match
        if ($run->total_net > 0) {
            $lines[] = [
                'account_id' => $salaryPayable->id,
                'debit' => 0,
                'credit' => $run->total_net,
                'description' => "Net Salaries Payable",
            ];
        }

        // 7. Create Journal Entry
        return $this->journalService->createEntry([
            'entry_date' => now()->toDateString(), // Date of approval
            'source' => 'PAYROLL',
            'source_id' => $run->id,
            'description' => "Payroll Accrual: " . $run->period_start->format('M Y'),
            'lines' => $lines,
        ]);
    }

    /**
     * Post Payroll Payment (Clears Salary Payable)
     */
    public function postPayrollPayment(PayrollRun $run, string $paymentDate, int $bankAccountId)
    {
        $lines = [];
        
        $salaryPayable = $this->getSystemAccount('SALARY_PAYABLE');
        $bankAccount = ChartOfAccount::findOrFail($bankAccountId);

        // Debit: Salary Payable
        $lines[] = [
            'account_id' => $salaryPayable->id,
            'debit' => $run->total_net,
            'credit' => 0,
            'description' => "Clear Salary Payable",
        ];

        // Credit: Bank/Cash (Selected Account)
        $lines[] = [
            'account_id' => $bankAccount->id,
            'debit' => 0,
            'credit' => $run->total_net,
            'description' => "Payroll Payment",
        ];

        return $this->journalService->createEntry([
            'entry_date' => $paymentDate,
            'source' => 'PAYROLL_PAYMENT',
            'source_id' => $run->id,
            'description' => "Payroll Payment: " . $run->period_start->format('M Y'),
            'lines' => $lines,
        ]);
    }

    private function getSystemAccount(string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)->first();
        if (!$account) {
            throw new Exception("Missing system account: {$code}. Please cycle the PayrollAccountsSeeder.");
        }
        return $account;
    }
}
