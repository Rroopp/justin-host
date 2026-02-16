<?php

use App\Models\Staff;
use App\Models\PayrollRun;
use App\Services\Accounting\JournalEntryService;
use App\Services\Payroll\PayrollPostingService;
use App\Services\Payroll\PayrollService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Bootstrap services
$journalService = new JournalEntryService();
$postingService = new PayrollPostingService($journalService);
$payrollService = new PayrollService($postingService);

echo "--- STARTING PAYROLL VERIFICATION ---\n";

// 1. Setup Data
// Login as admin for 'created_by'
$admin = Staff::where('role', 'admin')->first();
if (!$admin) {
    echo "ERROR: No admin user found.\n";
    exit(1);
}
Auth::login($admin);
echo "Logged in as: " . $admin->username . "\n";

// Ensure we have a test employee with salary
$testEmployee = Staff::firstOrCreate(
    ['username' => 'test_employee'],
    [
        'full_name' => 'John Doe Test',
        'password_hash' => 'secret',
        'role' => 'staff',
        'salary' => 50000,
        'status' => 'active',
        'email' => 'test@example.com'
    ]
);
// Make sure salary is set
$testEmployee->update(['salary' => 50000]);
echo "Test Employee: " . $testEmployee->full_name . " (Salary: 50,000)\n";

// 2. Create Payroll Run
echo "\n1. Creating Payroll Run (Jan 2026)...\n";
try {
    $run = $payrollService->createPayrollRun('2026-01-01', '2026-01-31');
    echo "Run Created: ID " . $run->id . " Status: " . $run->status . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Calculate Payroll
echo "\n2. Calculating Payroll...\n";
try {
    $payrollService->calculatePayroll($run);
    $run->refresh();
    echo "Calculated!\n";
    echo "Total Gross: " . number_format($run->total_gross, 2) . "\n";
    echo "Total Deductions: " . number_format($run->total_deductions, 2) . "\n";
    echo "Total Net: " . number_format($run->total_net, 2) . "\n";
    echo "Employer Contribs: " . number_format($run->total_employer_contributions, 2) . "\n";
    
    // Check item
    $item = $run->items()->first();
    echo "  - Employee Net: " . number_format($item->net_pay, 2) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Approve Payroll (Check Journal Entry)
echo "\n3. Approving Payroll (Generating Journal Entry)...\n";
try {
    $payrollService->approvePayroll($run);
    $run->refresh();
    echo "Approved! Status: " . $run->status . "\n";
    echo "Journal Entry ID: " . $run->journal_entry_id . "\n";
    
    $je = $run->journalEntry;
    echo "Journal Details:\n";
    foreach ($je->lines as $line) {
        $account = $line->chartOfAccount;
        echo "  - " . str_pad($account->name, 30) . 
             " Dr: " . str_pad(number_format($line->debit_amount, 2), 10) . 
             " Cr: " . str_pad(number_format($line->credit_amount, 2), 10) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Pay Employees
echo "\n4. Paying Employees (Generating Payment Journal)...\n";
try {
    $payrollService->payEmployees($run, date('Y-m-d'));
    $run->refresh();
    echo "Paid! Status: " . $run->status . "\n";
    echo "Payment JE ID: " . $run->payment_journal_entry_id . "\n";
    
    $pje = $run->paymentJournalEntry;
    echo "Payment Journal Details:\n";
    foreach ($pje->lines as $line) {
        $account = $line->chartOfAccount;
        echo "  - " . str_pad($account->name, 30) . 
             " Dr: " . str_pad(number_format($line->debit_amount, 2), 10) . 
             " Cr: " . str_pad(number_format($line->credit_amount, 2), 10) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- VERIFICATION COMPLETE ---\n";
