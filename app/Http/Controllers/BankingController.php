<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankingController extends Controller
{
    protected $accountingService;
    protected $auditService;

    public function __construct(AccountingService $accountingService, \App\Services\AuditService $auditService)
    {
        $this->accountingService = $accountingService;
        $this->auditService = $auditService;
    }

    /**
     * Display Banking Dashboard
     * Lists all Bank and Cash accounts with current balances
     */
    public function index()
    {
        // 1. Fetch Asset accounts flagged as 'Bank' or 'Cash'
        // Since we don't have a specific 'sub_type' for Bank yet relying on name or account_type 'Asset'
        // Improved: Fetch all Assets, filter by name simply for now, or fetch specific IDs.
        // Best approach for MVP: Fetch all 'Asset' accounts, filter for those typically cash/bank.
        // Or assume user configured them. Let's fetch all Assets and let view filter or show all Liquid assets.
        
        $accounts = ChartOfAccount::where('account_type', 'Asset')
            ->where('is_active', true)
            ->get();
            
        // Filter mainly for cash/bank equivalents if possible. 
        // For now, show all Assets but highlight Bank/Cash based on name keywords
        $bankAccounts = $accounts->filter(function($acc) {
            return str_contains(strtolower($acc->name), 'bank') || 
                   str_contains(strtolower($acc->name), 'cash') || 
                   str_contains(strtolower($acc->name), 'mpesa') ||
                   str_contains(strtolower($acc->name), 'money');
        });

        // Calculate balances (if not already dynamic attribute)
        // ChartOfAccount model has getBalanceAttribute? Yes it seems so based on previous context.
        
        return view('banking.index', compact('bankAccounts'));
    }

    /**
     * Show Statement / Ledger for specific account
     */
    public function show(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);
        
        $query = JournalEntryLine::where('account_id', $id)
            ->with('journalEntry')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.entry_date', 'desc')
            ->select('journal_entry_lines.*');

        if ($request->has('date_from')) {
            $query->whereDate('journal_entries.entry_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('journal_entries.entry_date', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);

        return view('banking.show', compact('account', 'transactions'));
    }

    /**
     * Store Deposit (Top Up)
     */
    public function storeDeposit(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string',
            'source_account_id' => 'required|exists:chart_of_accounts,id', // Equity or Revenue
        ]);

        try {
            // Debit Bank (Asset), Credit Source (Equity/Revenue)
            DB::beginTransaction();

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($validated['date']),
                'entry_date' => $validated['date'],
                'description' => $validated['description'],
                'reference_type' => 'DEPOSIT',
                'total_debit' => $validated['amount'],
                'total_credit' => $validated['amount'],
                'status' => 'POSTED',
                'created_by' => $request->user()->username,
            ]);

            // Dr Bank
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $validated['account_id'],
                'debit_amount' => $validated['amount'],
                'credit_amount' => 0,
                'description' => 'Deposit/Top-up',
                'line_number' => 1,
            ]);

            // Cr Source
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $validated['source_account_id'],
                'debit_amount' => 0,
                'credit_amount' => $validated['amount'],
                'description' => 'Source of Funds',
                'line_number' => 2,
            ]);

            DB::commit();

            $this->auditService->log(
                $request->user(),
                'deposit',
                'banking',
                $entry->id,
                "Deposit of " . number_format($validated['amount'], 2) . " to account #{$validated['account_id']}",
                ChartOfAccount::class,
                null,
                $validated
            );

            return redirect()->back()->with('success', 'Deposit recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Deposit failed: ' . $e->getMessage());
        }
    }

    /**
     * Store Transfer (Between Accounts)
     */
    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:chart_of_accounts,id',
            'to_account_id' => 'required|exists:chart_of_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        try {
            $this->accountingService->recordTransfer(
                $validated['from_account_id'],
                $validated['to_account_id'],
                $validated['amount'],
                $validated['date'],
                $validated['description'] ?? 'Fund Transfer',
                $request->user()
            );

            $this->auditService->log(
                $request->user(),
                'transfer',
                'banking',
                null, 
                "Transfer of " . number_format($validated['amount'], 2) . " from #{$validated['from_account_id']} to #{$validated['to_account_id']}",
                ChartOfAccount::class,
                null,
                $validated
            );

            return redirect()->back()->with('success', 'Transfer recorded successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }
    /**
     * Store Direct Expense (Spend Money)
     */
    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'payment_account_id' => 'required|exists:chart_of_accounts,id', // Asset (Bank/Petty Cash)
            'expense_account_id' => 'required|exists:chart_of_accounts,id', // Expense Account
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // 1. Create Journal Entry
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($validated['date']),
                'entry_date' => $validated['date'],
                'description' => $validated['description'],
                'reference_type' => 'DIRECT_EXPENSE', // New type for direct spend
                'total_debit' => $validated['amount'],
                'total_credit' => $validated['amount'],
                'status' => 'POSTED',
                'created_by' => $request->user()->username,
            ]);

            // 2. Dr Expense Account (Increase Expense)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $validated['expense_account_id'],
                'debit_amount' => $validated['amount'],
                'credit_amount' => 0,
                'description' => $validated['description'],
                'line_number' => 1,
            ]);

            // 3. Cr Payment Account (Decrease Asset - Bank/Cash)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $validated['payment_account_id'],
                'debit_amount' => 0,
                'credit_amount' => $validated['amount'],
                'description' => 'Payment for Expense',
                'line_number' => 2,
            ]);

            DB::commit();

            $this->auditService->log(
                $request->user(),
                'expense',
                'banking',
                $entry->id,
                "Direct Expense of " . number_format($validated['amount'], 2) . ": {$validated['description']}",
                JournalEntry::class,
                null,
                $validated
            );

            return redirect()->back()->with('success', 'Expense recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record expense: ' . $e->getMessage());
        }
    }
}
