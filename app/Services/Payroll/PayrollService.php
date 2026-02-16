<?php

namespace App\Services\Payroll;

use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\PayrollEarning;
use App\Models\PayrollDeduction;
use App\Models\PayrollEmployerContribution;
use App\Models\PayrollDeductionType;
use App\Models\PayrollContributionType;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * PayrollService - Enterprise Payroll Processing
 * 
 * Handles payroll calculation following proper accounting principles:
 * - Creates LIABILITIES, not expenses
 * - Tracks deductions and employer contributions
 * - Integrates with accounting module
 */
class PayrollService
{
    protected PayrollPostingService $postingService;

    public function __construct(PayrollPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * Create a new payroll run
     */
    public function createPayrollRun(string $periodStart, string $periodEnd): PayrollRun
    {
        return PayrollRun::create([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_gross' => 0,
            'total_tax' => 0,
            'total_net' => 0,
            'total_deductions' => 0,
            'total_employer_contributions' => 0,
            'status' => 'DRAFT',
            'created_by' => Auth::user()->username ?? 'system',
        ]);
    }

    /**
     * Calculate payroll for all active employees
     */
    public function calculatePayroll(PayrollRun $run, array $selectedStaffIds = []): void
    {
        if ($run->status !== 'DRAFT') {
            throw new Exception("Can only calculate payroll for DRAFT runs.");
        }

        DB::beginTransaction();
        try {
            $query = Staff::where('status', 'active');
            
            if (!empty($selectedStaffIds)) {
                $query->whereIn('id', $selectedStaffIds);
            } else {
                // Only if running for ALL (legacy/fallback), we might want to skip 0 salary
                // But generally, if no selection is not allowed by controller anymore.
                // Keeping it safe strictly for auto-runs if valid
                $query->whereNotNull('salary')->where('salary', '>', 0);
            }

            $employees = $query->get();

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $totalEmployerContributions = 0;

            foreach ($employees as $employee) {
                $result = $this->calculateEmployeePayroll($run, $employee);
                
                $totalGross += $result['gross_pay'];
                $totalDeductions += $result['total_deductions'];
                $totalNet += $result['net_pay'];
                $totalEmployerContributions += $result['employer_contributions'];
            }

            // Update run totals
            $run->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'total_employer_contributions' => $totalEmployerContributions,
                'total_tax' => 0, // Will be sum of PAYE
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to calculate payroll: " . $e->getMessage());
        }
    }

    /**
     * Calculate payroll for a single employee
     */
    protected function calculateEmployeePayroll(PayrollRun $run, Staff $employee): array
    {
        $basicSalary = $employee->salary ?? 0;
        
        // Process Recurring Earnings (Allowances, etc)
        $recurringEarnings = $employee->recurringEarnings()
            ->where('is_active', true)
            ->where(function ($q) use ($run) {
                // Check if start date is valid
                $q->whereNull('start_date')->orWhere('start_date', '<=', $run->period_end);
            })
            ->where(function ($q) use ($run) {
                // Check if end date is valid
                $q->whereNull('end_date')->orWhere('end_date', '>=', $run->period_start);
            })
            ->with('earningType')
            ->get();

        $allowances = 0;
        $overtime = 0;
        $bonuses = 0;
        $otherEarnings = 0;

        // Sum up earnings based on type codes
        foreach ($recurringEarnings as $earning) {
            $amount = $earning->amount;
            $code = $earning->earningType->code ?? '';

            if (str_contains($code, 'ALLOWANCE')) {
                $allowances += $amount;
            } elseif ($code === 'OVERTIME') {
                $overtime += $amount;
            } elseif ($code === 'BONUS') {
                $bonuses += $amount;
            } else {
                $otherEarnings += $amount;
            }
        }

        // Total Gross includes all earnings
        $grossPay = $basicSalary + $allowances + $overtime + $bonuses + $otherEarnings;

        // Include Approved Reimbursements in Payroll
        $reimbursements = \App\Models\StaffReimbursement::where('staff_id', $employee->id)
            ->where('status', 'approved')
            ->whereNull('payroll_run_id')
            ->whereBetween('expense_date', [$run->period_start, $run->period_end])
            ->get();

        $totalReimbursements = $reimbursements->sum('amount');

        // Mark reimbursements as included in this payroll run
        foreach ($reimbursements as $reimbursement) {
            $reimbursement->update([
                'payroll_run_id' => $run->id,
                'status' => 'paid',
                'payment_method' => 'payroll',
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);
        }

        // Create earnings record (including reimbursements)
        PayrollEarning::create([
            'payroll_run_id' => $run->id,
            'staff_id' => $employee->id,
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'overtime' => $overtime,
            'bonuses' => $bonuses,
            'reimbursements' => $totalReimbursements,
            'gross_pay' => $grossPay + $totalReimbursements, // Include reimbursements in total
        ]);

        // Calculate statutory deductions
        // DISABLED: Automatic calculation disabled per user request. 
        // All deductions should be set manually via Recurring Deductions.
        $paye = 0;
        $nssfEmployee = 0;
        $nhifEmployee = 0;

        $totalDeductions = 0;

        // Create deduction records
        // $this->createDeduction($run, $employee, 'PAYE', $paye);
        // $this->createDeduction($run, $employee, 'NSSF_EE', $nssfEmployee);
        // $this->createDeduction($run, $employee, 'NHIF_EE', $nhifEmployee);

        // Calculate employer contributions
        // DISABLED: Automatic calculation disabled.
        $nssfEmployer = 0;
        $nhifEmployer = 0; 
        
        $totalEmployerContributions = 0;

        // Create employer contribution records
        // $this->createEmployerContribution($run, $employee, 'NSSF_ER', $nssfEmployer);
        // $this->createEmployerContribution($run, $employee, 'NHIF_ER', $nhifEmployer);

        // Process Recurring Deductions (Loans, Advances, etc)
        $recurringDeductions = $employee->recurringDeductions()
            ->where('is_active', true)
            ->where(function ($q) use ($run) {
                // Check if start date is valid (started before or during this period)
                $q->whereNull('start_date')->orWhere('start_date', '<=', $run->period_end);
            })
            ->where(function ($q) use ($run) {
                // Check if end date is valid (not ended before this period)
                $q->whereNull('end_date')->orWhere('end_date', '>=', $run->period_start);
            })
            ->with('deductionType')
            ->get();

        foreach ($recurringDeductions as $recurring) {
            $amount = $recurring->amount;

            // Handle reducing balance for loans
            if ($recurring->balance !== null) {
                if ($recurring->balance <= 0) continue; 
                $amount = min($recurring->amount, $recurring->balance);
            }

            if ($amount > 0 && $recurring->deductionType) {
                $totalDeductions += $amount;

                PayrollDeduction::create([
                    'payroll_run_id' => $run->id,
                    'staff_id' => $employee->id,
                    'deduction_type_id' => $recurring->deductionType->id,
                    'amount' => $amount,
                    'is_statutory' => false,
                ]);
            }
        }

        $netPay = $grossPay - $totalDeductions;

        // Create payroll item summary
        PayrollItem::create([
            'run_id' => $run->id,
            'employee_id' => $employee->id,
            'gross_pay' => $grossPay,
            'tax_amount' => $paye,
            'net_pay' => $netPay,
            'total_deductions' => $totalDeductions,
            'total_employer_contributions' => $totalEmployerContributions,
        ]);

        return [
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'employer_contributions' => $totalEmployerContributions,
        ];
    }

    /**
     * Approve payroll and post to general ledger
     */
    public function approvePayroll(PayrollRun $run): void
    {
        if ($run->status !== 'DRAFT') {
            throw new Exception("Can only approve DRAFT payroll runs.");
        }

        DB::beginTransaction();
        try {
            // Post to general ledger (creates liabilities)
            $journalEntry = $this->postingService->postPayrollAccrual($run);

            // Update run status
            $run->update([
                'status' => 'APPROVED',
                'journal_entry_id' => $journalEntry->id,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to approve payroll: " . $e->getMessage());
        }
    }

    /**
     * Record employee payments (clears salary payable)
     */
    public function payEmployees(PayrollRun $run, string $paymentDate, int $bankAccountId): void
    {
        if ($run->status !== 'APPROVED') {
            throw new Exception("Can only pay APPROVED payroll runs.");
        }

        DB::beginTransaction();
        try {
            $paymentEntry = $this->postingService->postPayrollPayment($run, $paymentDate, $bankAccountId);

            $run->update([
                'status' => 'PAID',
                'payment_journal_entry_id' => $paymentEntry->id,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to record payment: " . $e->getMessage());
        }
    }

    /**
     * Delete a payroll run and its items (only if DRAFT)
     */
    public function deletePayrollRun(PayrollRun $run): void
    {
        if ($run->status === 'PAID') {
            throw new Exception("Cannot delete a PAID payroll run. Please reverse the payment first.");
        }

        // if APPROVED, we might need to reverse accrual journal entry?
        // For now, let's strictly allow DRAFT or ensure we handle cleanup
        if ($run->status === 'APPROVED' && $run->journal_entry_id) {
            // Ideally reverse the journal entry or throw error
             throw new Exception("Cannot delete an APPROVED payroll run. Please void/reverse it first.");
        }

        DB::beginTransaction();
        try {
            // Cascade delete items, earnings, deductions, contributions
            // Assuming database foreign keys are set to cascade, but if not:
            PayrollItem::where('run_id', $run->id)->delete();
            PayrollEarning::where('payroll_run_id', $run->id)->delete();
            PayrollDeduction::where('payroll_run_id', $run->id)->delete();
            PayrollEmployerContribution::where('payroll_run_id', $run->id)->delete();

            $run->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to delete payroll run: " . $e->getMessage());
        }
    }

    // Helper methods for calculations (simplified - should be configurable)
    
    protected function calculatePAYE(float $grossPay): float
    {
        // Simplified PAYE calculation (Kenya tax bands)
        if ($grossPay <= 24000) return 0;
        if ($grossPay <= 32333) return ($grossPay - 24000) * 0.10;
        if ($grossPay <= 40000) return 833 + ($grossPay - 32333) * 0.15;
        if ($grossPay <= 48000) return 1983 + ($grossPay - 40000) * 0.20;
        return 3583 + ($grossPay - 48000) * 0.25;
    }

    protected function calculateNSSF(float $grossPay, string $type = 'employee'): float
    {
        // Simplified NSSF (6% each for employee and employer, capped)
        $rate = 0.06;
        $maxContribution = 2160; // 6% of 36,000
        return min($grossPay * $rate, $maxContribution);
    }

    protected function calculateNHIF(float $grossPay): float
    {
        // Simplified NHIF bands
        if ($grossPay <= 5999) return 150;
        if ($grossPay <= 7999) return 300;
        if ($grossPay <= 11999) return 400;
        if ($grossPay <= 14999) return 500;
        if ($grossPay <= 19999) return 600;
        if ($grossPay <= 24999) return 750;
        if ($grossPay <= 29999) return 850;
        if ($grossPay <= 34999) return 900;
        if ($grossPay <= 39999) return 950;
        if ($grossPay <= 44999) return 1000;
        if ($grossPay <= 49999) return 1100;
        if ($grossPay <= 59999) return 1200;
        if ($grossPay <= 69999) return 1300;
        if ($grossPay <= 79999) return 1400;
        if ($grossPay <= 89999) return 1500;
        if ($grossPay <= 99999) return 1600;
        return 1700;
    }

    protected function createDeduction(PayrollRun $run, Staff $employee, string $code, float $amount): void
    {
        if ($amount <= 0) return;

        $deductionType = PayrollDeductionType::where('code', $code)->first();
        if (!$deductionType) return;

        PayrollDeduction::create([
            'payroll_run_id' => $run->id,
            'staff_id' => $employee->id,
            'deduction_type_id' => $deductionType->id,
            'amount' => $amount,
            'is_statutory' => $deductionType->is_statutory,
        ]);
    }

    protected function createEmployerContribution(PayrollRun $run, Staff $employee, string $code, float $amount): void
    {
        if ($amount <= 0) return;

        $contributionType = PayrollContributionType::where('code', $code)->first();
        if (!$contributionType) return;

        PayrollEmployerContribution::create([
            'payroll_run_id' => $run->id,
            'staff_id' => $employee->id,
            'contribution_type_id' => $contributionType->id,
            'amount' => $amount,
        ]);
    }
}
