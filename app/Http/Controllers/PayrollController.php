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

    /**
     * List all payroll runs
     */
    public function index(Request $request)
    {
        $query = PayrollRun::query();

        // Export functionality
        if ($request->has('export')) {
            $runs = $query->orderBy('id', 'desc')->get();
            $data = $runs->map(function($run) {
                return [
                    'Run ID' => $run->id,
                    'Period Start' => $run->period_start,
                    'Period End' => $run->period_end,
                    'Total Gross' => $run->total_gross,
                    'Total Tax' => $run->total_tax,
                    'Total Net' => $run->total_net,
                    'Status' => $run->status,
                    'Created By' => $run->created_by,
                    'Created At' => $run->created_at->format('Y-m-d H:i:s'),
                ];
            });
            
            return $this->streamCsv('payroll_runs_report.csv', ['Run ID', 'Period Start', 'Period End', 'Total Gross', 'Total Tax', 'Total Net', 'Status', 'Created By', 'Created At'], $data, 'Payroll Runs Report');
        }

        $runs = $query->orderBy('id', 'desc')->get();
        return view('payroll.index', compact('runs'));
    }

    /**
     * Show form to create a new run
     */
    public function create()
    {
        // Get active staff
        $staff = Staff::where('status', 'active')
            ->where('is_deleted', false)
            ->orderBy('full_name')
            ->get();

        // Default dates: From today to 1 month from today
        $defaultStart = now()->format('Y-m-d');
        $defaultEnd = now()->addMonth()->format('Y-m-d');

        return view('payroll.create', compact('staff', 'defaultStart', 'defaultEnd'));
    }

    /**
     * Show details of a specific payroll run
     */
    public function show($id)
    {
        $run = PayrollRun::with(['items.employee'])->findOrFail($id);
        return view('payroll.show', compact('run'));
    }

    /**
     * Store a new payroll run
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'items' => 'required|array',
            'items.*.staff_id' => 'required|exists:staff,id',
            'items.*.gross_pay' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.net_pay' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate totals
            $totalGross = collect($validated['items'])->sum('gross_pay');
            $totalTax = collect($validated['items'])->sum('tax_amount');
            $totalNet = collect($validated['items'])->sum('net_pay');

            // Create Run
            $run = PayrollRun::create([
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'total_gross' => $totalGross,
                'total_tax' => $totalTax,
                'total_net' => $totalNet,
                'status' => 'DRAFT', // Default to DRAFT
                'created_by' => $request->user()->username ?? 'system',
            ]);

            // Create Items
            foreach ($validated['items'] as $item) {
                PayrollItem::create([
                    'run_id' => $run->id,
                    'employee_id' => $item['staff_id'],
                    'gross_pay' => $item['gross_pay'],
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'net_pay' => $item['net_pay'],
                ]);
            }

            // Note: Accounting entry is now triggered when status becomes 'PAID'

            DB::commit();
            return redirect()->route('payroll.index')->with('success', 'Payroll run created as DRAFT. Please review and approve.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create payroll: ' . $e->getMessage());
        }
    }

    /**
     * Update payroll status
     */
    public function updateStatus(Request $request, PayrollRun $payroll)
    {
        $validated = $request->validate([
            'status' => 'required|in:DRAFT,COMPLETED,CANCELLED',
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $payroll->status;

        // Prevent reverting from COMPLETED
        if ($oldStatus === 'COMPLETED') {
            return response()->json(['error' => 'Cannot change status of a COMPLETED payroll run'], 422);
        }

        DB::beginTransaction();
        try {
            $payroll->update(['status' => $newStatus]);

            // Trigger accounting only when moving to COMPLETED
            if ($newStatus === 'COMPLETED' && $oldStatus !== 'COMPLETED') {
                $accounting = new \App\Services\AccountingService();
                $accounting->recordPayrollExpense($payroll, $request->user());
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Status updated successfully', 'status' => $newStatus]);
            }
            return redirect()->back()->with('success', 'Payroll status updated to ' . $newStatus);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}
