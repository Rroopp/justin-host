<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Staff;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    // Dashboard / Reporting
    public function index(Request $request) 
    {
        $query = Commission::with(['staff', 'sale']);

        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Restrict Staff to view only their own commissions
        $user = $request->user() ?? auth()->user();
        if ($user && !$user->hasRole(['admin', 'accountant'])) {
            // Assuming the authenticated user IS the staff member or linked to one
            // We need to match the 'staff' ID. 
            // The Staff model is separate from User model likely, or User has 'staff_id'?
            // Wait, usually User IS Staff if role is staff.
            // Let's check if User has 'id' that matches 'staff_id' in commissions table.
            // In this app, 'Staff' extends Model, and auth uses 'User'.
            // I need to check if there is a relationship or if they are the same table.
            // Based on previous files, 'StaffController' uses 'Staff' model. 'AuthController' uses 'User'? 
            // Actually, in many POS systems here, 'Staff' might be the user model or linked.
            // Let's assuming User ID = Staff ID for simplicity if they are the same entity in auth(),
            // OR find the staff record linked to the user.
            // Looking at StaffController store: it creates a Staff record with password_hash. So 'Staff' IS the authenticatable User.
            $query->where('staff_id', $user->id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $commissions = $query->latest()->paginate(20);
        
        // Calculate totals for dashboard summary
        $totals = [
            'pending' => Commission::where('status', 'pending')->sum('amount'),
            'paid' => Commission::where('status', 'paid')->sum('amount'),
        ];

        return view('commissions.index', compact('commissions', 'totals'));
    }

    // Store manual commission (e.g. Locum, Bonus) or API endpoint
    public function store(Request $request, \App\Services\AccountingService $accountingService)
    {
        // STRICT CHECK: Only Admin/Accountant can add commissions manually
        if ($request->user()->hasRole('staff') && !$request->user()->hasRole(['admin', 'accountant'])) {
            abort(403, 'Unauthorized. Staff cannot add commissions.');
        }

        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:sale,service,locum,bonus',
            'description' => 'nullable|string',
            'invoice_number' => 'nullable|exists:pos_sales,invoice_number'
        ]);

        if (!empty($validated['invoice_number'])) {
            $sale = \App\Models\PosSale::where('invoice_number', $validated['invoice_number'])->first();
            $validated['pos_sale_id'] = $sale->id;
        }

        // 1. Create Expense Record first
        $accounts = $accountingService->getCommissionAccounts();
        $staff = Staff::find($validated['staff_id']);
        $payeeName = $staff ? ($staff->first_name . ' ' . $staff->last_name) : "Staff #{$validated['staff_id']}";

        $expense = \App\Models\Expense::create([
            'payee' => $payeeName,
            'description' => $validated['description'] ?? "Commission (Type: {$validated['type']})",
            'amount' => $validated['amount'],
            'expense_date' => now(), // Date of accrual
            'category_id' => $accounts['expense_id'],
            'payment_account_id' => $accounts['payable_id'], // Booked as Liability
            'created_by' => $request->user() ? $request->user()->username : 'system',
        ]);

        // 2. Create Commission linked to Expense
        $validated['expense_id'] = $expense->id;
        $commission = Commission::create($validated);
        
        // 3. Record Accounting Entry via Expense (Standard Flow)
        $accountingService->recordExpense($expense, $request->user());

        if ($request->expectsJson()) {
            return response()->json($commission, 201);
        }

        return redirect()->back()->with('success', 'Commission recorded as Expense successfully');
    }

    // Mark as Paid
    public function update(Request $request, Commission $commission, \App\Services\AccountingService $accountingService)
    {
        // STRICT CHECK: Only Admin/Accountant can update/pay commissions
        if ($request->user()->hasRole('staff') && !$request->user()->hasRole(['admin', 'accountant'])) {
            abort(403, 'Unauthorized. Staff cannot pay commissions.');
        }

        if ($request->has('status') && $request->status === 'paid') {
            $commission->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);
            
            // Record Payment in Accounting
            $accountingService->recordCommissionPayment($commission, $request->user());
            
            return redirect()->back()->with('success', 'Commission marked as paid');
        }

        // If updating other fields (e.g. amount correction before payment)
        $commission->update($request->all()); // Naive update, ideally validate
        
        // If linked expense exists, sync amount
        if ($commission->expense) {
            $commission->expense->update([
                 'amount' => $commission->amount,
                 'description' => $commission->description
            ]);
            $accountingService->updateExpenseAccounting($commission->expense, $request->user());
        }

        return redirect()->back()->with('success', 'Commission updated');
    }
}
