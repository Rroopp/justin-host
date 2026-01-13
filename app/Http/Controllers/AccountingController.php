<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Shareholder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * Display chart of accounts.
     */
    public function index(Request $request)
    {
        $query = ChartOfAccount::query();

        if ($request->has('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query
            ->with('parent')
            ->orderBy('code')
            ->get();

        // Calculate balances
        foreach ($accounts as $account) {
            $account->balance = $account->balance;
        }

        if ($request->expectsJson()) {
            return response()->json($accounts);
        }

        return view('accounting.index', compact('accounts'));
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code',
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:Asset,Liability,Equity,Income,Expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $account = ChartOfAccount::create($validated);

        if ($request->expectsJson()) {
            return response()->json($account, 201);
        }

        return redirect()->route('accounting.index')->with('success', 'Account created successfully');
    }

    /**
     * Update an existing account.
     */
    public function updateAccount(Request $request, ChartOfAccount $chartOfAccount)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code,' . $chartOfAccount->id,
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:Asset,Liability,Equity,Income,Expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id|not_in:' . $chartOfAccount->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        // Avoid circular parenting (basic guard: parent cannot be one of its descendants)
        if (!empty($validated['parent_id'])) {
            $descendantIds = $chartOfAccount->children()->pluck('id')->toArray();
            if (in_array((int) $validated['parent_id'], $descendantIds, true)) {
                return response()->json(['message' => 'Invalid parent account selected.'], 422);
            }
        }

        $chartOfAccount->update($validated);

        return response()->json($chartOfAccount->fresh('parent'));
    }

    /**
     * Toggle active status for an account.
     */
    public function toggleAccountActive(Request $request, ChartOfAccount $chartOfAccount)
    {
        $chartOfAccount->update(['is_active' => !$chartOfAccount->is_active]);
        return response()->json($chartOfAccount);
    }

    /**
     * Delete an account (only if it has no children and no journal activity).
     */
    public function destroyAccount(Request $request, ChartOfAccount $chartOfAccount)
    {
        if ($chartOfAccount->children()->exists()) {
            return response()->json(['message' => 'Cannot delete an account that has child accounts.'], 400);
        }
        if ($chartOfAccount->journalEntryLines()->exists()) {
            return response()->json(['message' => 'Cannot delete an account that has journal activity. Deactivate it instead.'], 400);
        }

        $chartOfAccount->delete();
        return response()->json(['message' => 'Account deleted successfully']);
    }

    /**
     * Display journal entries.
     */
    public function journalEntries(Request $request)
    {
        $query = JournalEntry::with('lines.account');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        $entries = $query->orderBy('entry_date', 'desc')->orderBy('id', 'desc')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($entries);
        }

        return view('accounting.journal-entries', compact('entries'));
    }

    /**
     * Store a newly created journal entry.
     */
    public function storeJournalEntry(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit_amount' => 'required_without:lines.*.credit_amount|numeric|min:0',
            'lines.*.credit_amount' => 'required_without:lines.*.debit_amount|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        // Calculate totals
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['lines'] as $line) {
            $totalDebit += $line['debit_amount'] ?? 0;
            $totalCredit += $line['credit_amount'] ?? 0;
        }

        // Validate balanced entry
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json([
                'error' => 'Journal entry must be balanced. Debits: ' . $totalDebit . ', Credits: ' . $totalCredit
            ], 400);
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($validated['entry_date']),
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'DRAFT',
                'created_by' => $request->user() ? $request->user()->username : 'system',
            ]);

            foreach ($validated['lines'] as $index => $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'description' => $line['description'] ?? '',
                    'line_number' => $index + 1,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json($entry->load('lines.account'), 201);
            }

            return redirect()->route('accounting.journal-entries')->with('success', 'Journal entry created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create journal entry: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to create journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Post a journal entry
     */
    public function postJournalEntry(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status === 'POSTED') {
            return response()->json(['error' => 'Entry already posted'], 400);
        }

        if (!$journalEntry->isBalanced()) {
            return response()->json(['error' => 'Entry is not balanced'], 400);
        }

        $journalEntry->update(['status' => 'POSTED']);

        if ($request->expectsJson()) {
            return response()->json($journalEntry);
        }

        return redirect()->back()->with('success', 'Journal entry posted successfully');
    }

    /**
     * Unpost a journal entry (set back to DRAFT)
     */
    public function unpostJournalEntry(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'POSTED') {
            return response()->json(['error' => 'Only POSTED entries can be unposted'], 400);
        }

        $journalEntry->update(['status' => 'DRAFT']);
        return response()->json($journalEntry);
    }

    /**
     * Cancel a journal entry (set to CANCELLED)
     */
    public function cancelJournalEntry(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status === 'CANCELLED') {
            return response()->json(['error' => 'Entry already cancelled'], 400);
        }

        $journalEntry->update(['status' => 'CANCELLED']);
        return response()->json($journalEntry);
    }

    /**
     * Update a draft journal entry (replaces lines)
     */
    public function updateJournalEntry(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'DRAFT') {
            return response()->json(['error' => 'Only DRAFT entries can be edited'], 400);
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit_amount' => 'required_without:lines.*.credit_amount|numeric|min:0',
            'lines.*.credit_amount' => 'required_without:lines.*.debit_amount|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['lines'] as $line) {
            $totalDebit += $line['debit_amount'] ?? 0;
            $totalCredit += $line['credit_amount'] ?? 0;
        }
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json(['error' => 'Journal entry must be balanced.'], 400);
        }

        DB::beginTransaction();
        try {
            $journalEntry->update([
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            $journalEntry->lines()->delete();
            foreach ($validated['lines'] as $index => $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $line['account_id'],
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'description' => $line['description'] ?? '',
                    'line_number' => $index + 1,
                ]);
            }

            DB::commit();
            return response()->json($journalEntry->fresh()->load('lines.account'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update journal entry: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get trial balance
     */
    public function trialBalance(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        if (!$request->expectsJson()) {
            return view('accounting.trial-balance', ['date' => $date]);
        }

        $accounts = ChartOfAccount::where('is_active', true)->get(['id', 'code', 'name', 'account_type']);
        $sums = JournalEntryLine::query()
            ->selectRaw('journal_entry_lines.account_id as account_id, SUM(journal_entry_lines.debit_amount) as debits, SUM(journal_entry_lines.credit_amount) as credits')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'POSTED')
            ->whereDate('journal_entries.entry_date', '<=', $date)
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = (float) ($sums[$account->id]->debits ?? 0);
            $credits = (float) ($sums[$account->id]->credits ?? 0);

            if ($debits > 0 || $credits > 0) {
                $balance = 0;
                if (in_array($account->account_type, ['Asset', 'Expense'])) {
                    $balance = $debits - $credits;
                    $totalDebits += $balance;
                } else {
                    $balance = $credits - $debits;
                    $totalCredits += $balance;
                }

                $trialBalance[] = [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->account_type,
                    'debit' => in_array($account->account_type, ['Asset', 'Expense']) ? $balance : 0,
                    'credit' => in_array($account->account_type, ['Liability', 'Equity', 'Income']) ? $balance : 0,
                ];
            }
        }

        if ($request->has('export')) {
            $exportData = collect($trialBalance)->map(function($row) {
                return [
                    'Code' => $row['account_code'],
                    'Name' => $row['account_name'],
                    'Type' => $row['account_type'],
                    'Debit' => $row['debit'],
                    'Credit' => $row['credit'],
                ];
            });
            // Append totals row
            $exportData->push([
                'Code' => '', 'Name' => 'TOTALS', 'Type' => '',
                'Debit' => $totalDebits, 'Credit' => $totalCredits
            ]);

            return $this->streamCsv('trial_balance.csv', ['Code', 'Name', 'Type', 'Debit', 'Credit'], $exportData, "Trial Balance - As of $date");
        }

        return response()->json([
            'date' => $date,
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
        ]);
    }

    /**
     * Get financial statements
     */
    public function financialStatements(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        if (!$request->expectsJson()) {
            return view('accounting.financial-statements', ['date' => $date]);
        }

        $accounts = ChartOfAccount::where('is_active', true)->get(['id', 'account_type']);
        $sums = JournalEntryLine::query()
            ->selectRaw('journal_entry_lines.account_id as account_id, SUM(journal_entry_lines.debit_amount) as debits, SUM(journal_entry_lines.credit_amount) as credits')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'POSTED')
            ->whereDate('journal_entries.entry_date', '<=', $date)
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $totals = [
            'Asset' => 0.0,
            'Liability' => 0.0,
            'Equity' => 0.0,
            'Income' => 0.0,
            'Expense' => 0.0,
        ];

        foreach ($accounts as $acc) {
            $debits = (float) ($sums[$acc->id]->debits ?? 0);
            $credits = (float) ($sums[$acc->id]->credits ?? 0);
            $balance = in_array($acc->account_type, ['Asset', 'Expense']) ? ($debits - $credits) : ($credits - $debits);
            $totals[$acc->account_type] += $balance;
        }

        $income = $totals['Income'];
        $expenses = $totals['Expense'];
        $netIncome = $income - $expenses;

        $assets = $totals['Asset'];
        $liabilities = $totals['Liability'];
        $equity = $totals['Equity'];

        return response()->json([
            'profit_loss' => [
                'income' => $income,
                'expenses' => $expenses,
                'net_income' => $netIncome,
            ],
            'balance_sheet' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity + $netIncome,
                'total_liabilities_equity' => $liabilities + $equity + $netIncome,
            ],
            'date' => $date,
        ]);
    }

    /**
     * Get accounts receivable aging report
     */
    public function agingReport(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now());
        
        // Get all unpaid or partially paid invoices (credit sales)
        $invoices = \App\Models\PosSale::query()
            ->with('customer')
            ->whereNotNull('due_date')
            ->where('payment_status', '!=', 'paid')
            ->get();

        $customerTotals = [];
        
        foreach ($invoices as $invoice) {
            $customerName = $invoice->customer 
                ? $invoice->customer->name 
                : ($invoice->customer_name ?? 'Walk-in Customer');
            
            if (!isset($customerTotals[$customerName])) {
                $customerTotals[$customerName] = [
                    'name' => $customerName,
                    '0-30' => 0,
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                    'total' => 0,
                ];
            }
            
            // Calculate amount due (consider partial payments)
            $totalPaid = $invoice->payments()->sum('amount');
            $amountDue = $invoice->total - $totalPaid;
            
            if ($amountDue <= 0) {
                continue;
            }
            
            // Calculate days overdue
            $dueDate = \Carbon\Carbon::parse($invoice->due_date);
            $daysOverdue = $dueDate->diffInDays($asOfDate, false);
            
            // Categorize into aging buckets
            if ($daysOverdue <= 30) {
                $customerTotals[$customerName]['0-30'] += $amountDue;
            } elseif ($daysOverdue <= 60) {
                $customerTotals[$customerName]['31-60'] += $amountDue;
            } elseif ($daysOverdue <= 90) {
                $customerTotals[$customerName]['61-90'] += $amountDue;
            } else {
                $customerTotals[$customerName]['90+'] += $amountDue;
            }
            
            $customerTotals[$customerName]['total'] += $amountDue;
        }
        
        // Sort by total descending
        usort($customerTotals, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        return view('accounting.aging', compact('customerTotals'));
    }
    /**
     * Get general ledger
     */
    public function ledger(Request $request, $account)
    {
        if (!($account instanceof ChartOfAccount)) {
            $account = ChartOfAccount::findOrFail($account);
        }

        $query = JournalEntryLine::query()
            ->with(['account', 'journalEntry'])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->select('journal_entry_lines.*')
            ->where('journal_entry_lines.account_id', $account->id)
            ->orderBy('journal_entries.entry_date', 'desc');

        if ($request->has('date_from')) {
            $query->whereDate('journal_entries.entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('journal_entries.entry_date', '<=', $request->date_to);
        }

        if ($request->has('export')) {
            $lines = $query->get();
            $data = $lines->map(function ($line) {
                return [
                    'Date' => $line->journalEntry->entry_date,
                    'Description' => $line->journalEntry->description . ($line->description ? " - {$line->description}" : ''),
                    'Reference' => $line->journalEntry->entry_number,
                    'Account' => $line->account->name,
                    'Debit' => $line->debit_amount,
                    'Credit' => $line->credit_amount,
                ];
            });

            return $this->streamCsv('ledger_export.csv', ['Date', 'Description', 'Reference', 'Account', 'Debit', 'Credit'], $data, 'General Ledger - ' . $account->name);
        }

        $lines = $query->paginate(50);
        
        return view('accounting.ledger', compact('lines', 'account'));
    }

    /**
     * Get cash flow statement
     */
    public function cashFlow(Request $request)
    {
        // Simplistic Direct/Indirect Cash Flow estimation
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->endOfMonth()->toDateString());

        // 1. Calculate Net Income (Income - Expenses)
        $income = JournalEntryLine::join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('chart_of_accounts.account_type', 'Income')
            ->where('journal_entries.status', 'POSTED')
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->sum(DB::raw('credit_amount - debit_amount'));

        $expenses = JournalEntryLine::join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('chart_of_accounts.account_type', 'Expense')
            ->where('journal_entries.status', 'POSTED')
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->sum(DB::raw('debit_amount - credit_amount'));

        $netIncome = $income - $expenses;

        // 2. Identify Cash Accounts
        $cashAccounts = ChartOfAccount::where('account_type', 'Asset')
            ->where(function($q) {
                $q->where('name', 'like', '%Cash%')
                  ->orWhere('name', 'like', '%Bank%');
            })
            ->pluck('id');

        // 3. Analyze Cash Movements
        $operatingCashFlow = 0;
        $investingCashFlow = 0;
        $financingCashFlow = 0;
        $operatingAdjustments = [];

        // Analyze journal lines affecting cash accounts
        $lines = JournalEntryLine::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('entry_date', [$dateFrom, $dateTo])
                  ->where('status', 'POSTED');
            })
            ->with('journalEntry')
            ->get();

        foreach ($lines as $line) {
            // Debit to cash = Inflow (+), Credit to cash = Outflow (-)
            $amount = ($line->debit_amount - $line->credit_amount);
            
            $desc = $line->description ?: $line->journalEntry->description;

            if (stripos($desc, 'Loan') !== false || stripos($desc, 'Equity') !== false || stripos($desc, 'Capital') !== false) {
                $financingCashFlow += $amount;
            } elseif (stripos($desc, 'Asset') !== false || stripos($desc, 'Equipment') !== false || stripos($desc, 'Vehicle') !== false) {
                $investingCashFlow += $amount;
            } else {
                // Determine if this is Operating (Sales, Expenses)
                // Since we start with Net Income, we need to adjust valid cash items.
                // However, the view expects "Indirect Method" style: Net Income + Adjustments.
                // For this MVP, we will try to reconcile the two approaches simply.
                // Let's assume Net Income is distinct from cash movements and treat "Operating Cash Flow"
                // as the calculated cash movement for operations.
                $operatingCashFlow += $amount;
            }
        }
        
        // Improve Operating Adjustments for display (e.g. AP/AR changes - placeholders for now)
        // If Operating Cash Flow != Net Income, the difference is "Adjustments"
        $adjustmentAmount = $operatingCashFlow - $netIncome;
        if (abs($adjustmentAmount) > 0.01) {
            $operatingAdjustments['Working Capital Changes'] = $adjustmentAmount;
        }

        $netCashChange = $operatingCashFlow + $investingCashFlow + $financingCashFlow;

        return view('accounting.cash-flow', compact(
            'netIncome', 
            'operatingAdjustments', 
            'operatingCashFlow', 
            'investingCashFlow', 
            'financingCashFlow', 
            'netCashChange', 
            'dateFrom', 
            'dateTo'
        ));
    }

    /**
     * Store Capital Investment
     */
    /**
     * Store Capital Investment
     */
    public function storeCapitalInvestment(Request $request, \App\Services\AccountingService $accountingService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'shareholder_id' => 'nullable|exists:shareholders,id',
            'equity_account_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $entry = $accountingService->recordCapitalInvestment(
            $validated['amount'],
            $validated['account_id'],
            $validated['date'],
            $validated['description'],
            $request->user(),
            $validated['shareholder_id'] ?? null,
            $validated['equity_account_id'] ?? null
        );

        if ($entry) {
            return redirect()->back()->with('success', 'Capital Investment recorded successfully');
        } else {
            return redirect()->back()->with('error', 'Failed to record Capital Investment');
        }
    }

    /**
     * Shareholder Management & Dividends
     */

    public function indexShareholders(Request $request)
    {
        // Auto-seed default Asset accounts if none exist (User Experience improvement)
        if (!ChartOfAccount::where('account_type', 'Asset')->exists()) {
            ChartOfAccount::create([
                'code' => '1000',
                'name' => 'Cash Account',
                'account_type' => 'Asset',
                'description' => 'Default Cash Account',
                'is_active' => true,
            ]);
            ChartOfAccount::create([
                'code' => '1010',
                'name' => 'Bank Account',
                'account_type' => 'Asset',
                'description' => 'Default Bank Account',
                'is_active' => true,
            ]);
        }
        
        // Fetch accounts for the dropdown
        $assetAccounts = ChartOfAccount::where('account_type', 'Asset')
            ->where('is_active', true)
            ->get();

        $shareholders = \App\Models\Shareholder::with('capitalAccount')->get();
        // Calculate current capital balance for each
        foreach ($shareholders as $shareholder) {
            $shareholder->capital_balance = $shareholder->capitalAccount ? $shareholder->capitalAccount->balance : 0;
        }

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json($shareholders);
        }

        return view('accounting.shareholders.index', compact('shareholders', 'assetAccounts'));
    }

    public function storeShareholder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create unique Capital Account for them
            $capitalCode = '300' . ( \App\Models\Shareholder::count() + 1 ); // e.g. 3001, 3002
            // Ensure uniqueness or better generatation strategy later.
            // For MVP, simplistic code generation.
            
            // Check if code exists, increment util found
            while(ChartOfAccount::where('code', $capitalCode)->exists()) {
                $capitalCode++;
            }

            $accountingService = app(\App\Services\AccountingService::class);
            $parent = $accountingService->getSystemParentAccount('Equity');

            $account = ChartOfAccount::create([
                'code' => $capitalCode,
                'name' => "Capital - {$validated['name']}",
                'account_type' => 'Equity',
                'parent_id' => $parent?->id,
                'description' => "Capital account for shareholder {$validated['name']}",
                'is_active' => true,
            ]);

            \App\Models\Shareholder::create([
                'name' => $validated['name'],
                'ownership_percentage' => $validated['ownership_percentage'],
                'capital_account_id' => $account->id,
                'staff_id' => $validated['staff_id'] ?? null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Shareholder created and Capital Account generated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating shareholder: ' . $e->getMessage());
        }
    }

    public function updateShareholder(Request $request, \App\Models\Shareholder $shareholder)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $shareholder->update($validated);
        // also update account name if needed
        if ($shareholder->capitalAccount) {
            $shareholder->capitalAccount->update(['name' => "Capital - {$validated['name']}"]);
        }

        return redirect()->back()->with('success', 'Shareholder updated.');
    }

    public function destroyShareholder(\App\Models\Shareholder $shareholder)
    {
        if ($shareholder->capitalAccount && $shareholder->capitalAccount->balance != 0) {
            return redirect()->back()->with('error', 'Cannot delete shareholder with non-zero capital balance.');
        }
        $shareholder->delete();
        // Soft delete or keep account? Usually keep account for historical integrity.
        return redirect()->back()->with('success', 'Shareholder deleted.');
    }

    /**
     * Preview Dividend Distribution
     */
    public function dividendPreview()
    {
        // 1. Calculate Net Income (Retained Earnings available)
        // Re-use logic from financialStatements but simpler
        // Actually, we usually distribute based on a specific period's profit or Accumulated Retained Earnings.
        // For simplicity, let's use "Total Net Income to Date".
        
        $income = JournalEntryLine::whereHas('account', fn($q) => $q->where('account_type', 'Income'))
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'POSTED'))
            ->sum(DB::raw('credit_amount - debit_amount'));
            
        $expenses = JournalEntryLine::whereHas('account', fn($q) => $q->where('account_type', 'Expense'))
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'POSTED'))
            ->sum(DB::raw('debit_amount - credit_amount'));

        $netIncome = $income - $expenses;

        // 2. Get Shareholders
        $shareholders = \App\Models\Shareholder::all();
        $distribution = [];

        foreach ($shareholders as $sh) {
            $share = $netIncome * ($sh->ownership_percentage / 100);
            $distribution[] = [
                'name' => $sh->name,
                'percentage' => $sh->ownership_percentage,
                'amount' => $share
            ];
        }

        return response()->json([
            'total_net_income' => $netIncome,
            'distribution' => $distribution
        ]);
    }

    /**
     * Get staff list for API calls
     */
    public function getStaff()
    {
        $staff = \App\Models\Staff::select('id', 'full_name', 'username')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();
        
        return response()->json($staff);
    }
}


