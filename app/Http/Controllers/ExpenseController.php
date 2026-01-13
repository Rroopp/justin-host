<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $query = Expense::with('category', 'paymentAccount');

        if ($request->has('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payee', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Export functionality
        if ($request->has('export')) {
            $expenses = $query->orderBy('expense_date', 'desc')->get();
            $data = $expenses->map(function($expense) {
                return [
                    'Date' => $expense->expense_date,
                    'Payee' => $expense->payee,
                    'Description' => $expense->description,
                    'Category' => $expense->category?->name ?? '-',
                    'Amount' => $expense->amount,
                    'Payment Account' => $expense->paymentAccount?->name ?? '-',
                ];
            });
            
            return $this->streamCsv('expenses_report.csv', ['Date', 'Payee', 'Description', 'Category', 'Amount', 'Payment Account'], $data, 'Expenses Report');
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($expenses);
        }

        $categories = ChartOfAccount::where('account_type', 'Expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $paymentAccounts = ChartOfAccount::whereIn('account_type', ['Asset'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('expenses.index', compact('expenses', 'categories', 'paymentAccounts'));
    }

    public function store(Request $request, \App\Services\AccountingService $accountingService)
    {
        $validated = $request->validate([
            'payee' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'required|exists:chart_of_accounts,id',
            'payment_account_id' => 'nullable|exists:chart_of_accounts,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'required|in:paid,unpaid',
            'due_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $expense = Expense::create([
                'payee' => $validated['payee'],
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'category_id' => $validated['category_id'],
                'payment_account_id' => $validated['payment_account_id'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'status' => $validated['status'],
                'due_date' => $validated['due_date'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'created_by' => $request->user() ? $request->user()->username : 'system',
            ]);

            // Record accounting entry based on status
            if ($validated['status'] === 'unpaid') {
                // Record as Bill (Dr Expense / Cr Accounts Payable)
                $accountingService->recordBill($expense, $request->user());
            } else {
                // Record as Direct Expense (Dr Expense / Cr Bank/Cash)
                if ($expense->payment_account_id) {
                    $accountingService->recordDirectExpense($expense, $request->user());
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json($expense, 201);
            }

            return redirect()->route('expenses.index')->with('success', 
                $validated['status'] === 'unpaid' ? 'Bill recorded successfully' : 'Expense recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to record: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to record: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, Expense $expense, \App\Services\AccountingService $accountingService)
    {
        $validated = $request->validate([
            'payee' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|exists:chart_of_accounts,id',
            'payment_account_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $expense->update($validated);
        
        // Sync with Accounting
        if ($expense->category_id && $expense->payment_account_id) {
             $accountingService->updateExpenseAccounting($expense, $request->user());
        }

        if ($request->expectsJson()) {
            return response()->json($expense);
        }

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Expense deleted successfully']);
        }

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully');
    }


    /**
     * Display unpaid bills
     */
    public function unpaidBills(Request $request)
    {
        $bills = Expense::with('category', 'vendor')
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->paginate(50);

        $paymentAccounts = ChartOfAccount::whereIn('account_type', ['Asset'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('expenses.unpaid', compact('bills', 'paymentAccounts'));
    }

    /**
     * Pay a bill (full or partial)
     */
    public function payBill(Request $request, Expense $expense, \App\Services\AccountingService $accountingService)
    {
        if ($expense->status === 'paid') {
            return redirect()->back()->with('error', 'Bill is already paid.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $expense->amount,
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            // Record payment in accounting
            $accountingService->payBill($expense, $validated['amount'], $validated['payment_account_id'], $request->user());

            // Update expense status
            if ($validated['amount'] >= $expense->amount) {
                $expense->status = 'paid';
                $expense->payment_account_id = $validated['payment_account_id'];
            } else {
                $expense->status = 'partial';
            }
            $expense->save();

            DB::commit();

            return redirect()->route('expenses.unpaid')->with('success', 'Bill payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    /**
     * Create journal entry for expense
     */

}
