<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    use \App\Traits\CsvExportable;

    protected $payrollService;

    public function __construct(\App\Services\Payroll\PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * List all payroll runs
     */
    public function index(Request $request)
    {
        $query = PayrollRun::query();

        // Export functionality (simplified for now)
        if ($request->has('export')) {
            // ... (keep existing export logic later if needed)
        }

        $runs = $query->orderBy('id', 'desc')->get();
        return view('payroll.index', compact('runs'));
    }

    /**
     * Show form to create a new run
     */
    public function create()
    {
        // Default dates: First to last day of current month
        $defaultStart = now()->startOfMonth()->format('Y-m-d');
        $defaultEnd = now()->endOfMonth()->format('Y-m-d');

        // Fetch active staff eligible for payroll to show in selection list
        $staff = Staff::where('status', 'active')
            ->orderBy('full_name')
            ->get();

        return view('payroll.create', compact('defaultStart', 'defaultEnd', 'staff'));
    }

    /**
     * Store a new payroll run (Create & Calculate)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'exists:staff,id',
        ], [
            'staff_ids.required' => 'Please select at least one employee.',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Draft Run
            $run = $this->payrollService->createPayrollRun(
                $validated['period_start'],
                $validated['period_end']
            );

            // 2. Calculate Payroll (Engine) - Pass selected staff
            $this->payrollService->calculatePayroll($run, $validated['staff_ids']);

            DB::commit();
            
            return redirect()->route('payroll.show', $run->id)
                ->with('success', 'Payroll calculated successfully. Please review and approve.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    /**
     * Show details of a specific payroll run
     */
    public function show($id)
    {
        $run = PayrollRun::with(['items.employee', 'earnings', 'deductions', 'employerContributions'])->findOrFail($id);
        return view('payroll.show', compact('run'));
    }

    /**
     * Approve a payroll run (Creates Accrual Journal Entry)
     */
    public function approve($id)
    {
        $run = PayrollRun::findOrFail($id);

        try {
            $this->payrollService->approvePayroll($run);
            return back()->with('success', 'Payroll approved and posted to General Ledger.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve payroll: ' . $e->getMessage());
        }
    }

    /**
     * Pay employees (Creates Payment Journal Entry)
     */
    public function pay(Request $request, $id)
    {
        $run = PayrollRun::findOrFail($id);

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'payment_date' => 'required|date',
        ]);

        try {
            $this->payrollService->payEmployees($run, $validated['payment_date'], $validated['bank_account_id']);
            return back()->with('success', 'Payments recorded and posted to General Ledger.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process payments: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payroll run from storage.
     */
    public function destroy($id)
    {
        $run = PayrollRun::findOrFail($id);

        try {
            $this->payrollService->deletePayrollRun($run);
            return redirect()->route('payroll.index')->with('success', 'Payroll run deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete payroll run: ' . $e->getMessage());
        }
    }
}
