<?php

namespace App\Http\Controllers;

use App\Models\StaffReimbursement;
use App\Models\Staff;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StaffReimbursementController extends Controller
{
    /**
     * Display a listing of reimbursements
     */
    public function index(Request $request)
    {
        $query = StaffReimbursement::with(['staff', 'approvedBy', 'paidBy', 'payrollRun']);

        // Filter by staff (non-admin sees only their own)
        if (!Auth::user()->hasRole('admin')) {
            $query->where('staff_id', Auth::id());
        } else {
            // Admin can filter by staff
            if ($request->filled('staff_id')) {
                $query->where('staff_id', $request->staff_id);
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        $reimbursements = $query->orderByDesc('created_at')->paginate(20);

        // Get staff list for admin filter
        $staffList = Auth::user()->hasRole('admin') ? Staff::orderBy('full_name')->get() : collect();

        return view('reimbursements.index', compact('reimbursements', 'staffList'));
    }

    /**
     * Show the form for creating a new reimbursement
     */
    public function create()
    {
        return view('reimbursements.create');
    }

    /**
     * Store a newly created reimbursement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|in:Travel,Meals,Supplies,Fuel,Other',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        $validated['staff_id'] = Auth::id();
        $validated['status'] = 'pending';

        // Handle receipt upload
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('reimbursements/receipts', 'public');
            $validated['receipt_file_path'] = $path;
        }

        StaffReimbursement::create($validated);

        return redirect()->route('reimbursements.index')
            ->with('success', 'Reimbursement request submitted successfully.');
    }

    /**
     * Display the specified reimbursement
     */
    public function show(StaffReimbursement $reimbursement)
    {
        // Authorization: staff can only view their own, admin can view all
        if (!Auth::user()->hasRole('admin') && $reimbursement->staff_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $reimbursement->load(['staff', 'approvedBy', 'paidBy', 'payrollRun', 'paymentAccount']);

        // Get payment accounts for admin (All ASSET accounts - Cash, Bank, etc.)
        $paymentAccounts = Auth::user()->hasRole('admin') 
            ? ChartOfAccount::where('account_type', 'Asset')
                ->orderBy('name')
                ->get() 
            : collect();

        return view('reimbursements.show', compact('reimbursement', 'paymentAccounts'));
    }

    /**
     * Show the form for editing (only pending reimbursements)
     */
    public function edit(StaffReimbursement $reimbursement)
    {
        // Only staff can edit their own pending reimbursements
        if ($reimbursement->staff_id !== Auth::id() || $reimbursement->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        return view('reimbursements.edit', compact('reimbursement'));
    }

    /**
     * Update the specified reimbursement
     */
    public function update(Request $request, StaffReimbursement $reimbursement)
    {
        // Only staff can edit their own pending reimbursements
        if ($reimbursement->staff_id !== Auth::id() || $reimbursement->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|in:Travel,Meals,Supplies,Fuel,Other',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Handle receipt upload
        if ($request->hasFile('receipt')) {
            // Delete old receipt
            if ($reimbursement->receipt_file_path) {
                Storage::disk('public')->delete($reimbursement->receipt_file_path);
            }
            $path = $request->file('receipt')->store('reimbursements/receipts', 'public');
            $validated['receipt_file_path'] = $path;
        }

        $reimbursement->update($validated);

        return redirect()->route('reimbursements.show', $reimbursement)
            ->with('success', 'Reimbursement updated successfully.');
    }

    /**
     * Remove the specified reimbursement (only pending)
     */
    public function destroy(StaffReimbursement $reimbursement)
    {
        // Only staff can delete their own pending reimbursements
        if ($reimbursement->staff_id !== Auth::id() || $reimbursement->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        // Delete receipt file
        if ($reimbursement->receipt_file_path) {
            Storage::disk('public')->delete($reimbursement->receipt_file_path);
        }

        $reimbursement->delete();

        return redirect()->route('reimbursements.index')
            ->with('success', 'Reimbursement deleted successfully.');
    }

    /**
     * Approve a reimbursement (admin only)
     */
    public function approve(Request $request, StaffReimbursement $reimbursement)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        if ($reimbursement->status !== 'pending') {
            return back()->with('error', 'Only pending reimbursements can be approved.');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:500',
        ]);

        $reimbursement->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        return back()->with('success', 'Reimbursement approved successfully.');
    }

    /**
     * Reject a reimbursement (admin only)
     */
    public function reject(Request $request, StaffReimbursement $reimbursement)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        if ($reimbursement->status !== 'pending') {
            return back()->with('error', 'Only pending reimbursements can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $reimbursement->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Reimbursement rejected.');
    }

    /**
     * Mark reimbursement as paid (admin only, manual payment)
     */
    public function markAsPaid(Request $request, StaffReimbursement $reimbursement)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        if ($reimbursement->status !== 'approved') {
            return back()->with('error', 'Only approved reimbursements can be marked as paid.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bank_transfer',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        DB::beginTransaction();
        try {
            // Update reimbursement status
            $reimbursement->update([
                'status' => 'paid',
                'paid_by' => Auth::id(),
                'paid_at' => now(),
                'payment_method' => $validated['payment_method'],
                'payment_account_id' => $validated['payment_account_id'],
            ]);

            // Create journal entry for the payment
            // Debit: Staff Reimbursements Payable (Liability) or Expense
            // Credit: Cash/Bank Account (Asset)
            
            // Get or create expense account for reimbursements
            $expenseAccount = ChartOfAccount::firstOrCreate(
                ['code' => '5300'],
                [
                    'name' => 'Staff Reimbursements',
                    'account_type' => 'Expense',
                    'description' => 'Reimbursements paid to staff for business expenses',
                ]
            );

            $paymentAccount = ChartOfAccount::find($validated['payment_account_id']);

            // Create journal entry
            $journalEntry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(),
                'reference_number' => 'REIMB-' . $reimbursement->reference_number,
                'description' => "Reimbursement payment to {$reimbursement->staff->full_name} - {$reimbursement->description}",
                'total_debit' => $reimbursement->amount,
                'total_credit' => $reimbursement->amount,
                'created_by' => Auth::id(),
                'status' => 'posted',
            ]);

            // Debit expense account
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $expenseAccount->id,
                'debit_amount' => $reimbursement->amount,
                'credit_amount' => 0,
                'description' => "Reimbursement: {$reimbursement->description}",
                'line_number' => 1,
            ]);

            // Credit payment account (cash/bank)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $paymentAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $reimbursement->amount,
                'description' => "Payment via {$paymentAccount->name}",
                'line_number' => 2,
            ]);

            DB::commit();
            return back()->with('success', 'Reimbursement marked as paid and journal entry created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }
}
