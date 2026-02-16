<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    // Default Account Codes (should ideally be config/settings)
    const ACCOUNT_CASH = '1000';
    const ACCOUNT_BANK = '1010';
    const ACCOUNT_INVENTORY = '1200';
    const ACCOUNT_COGS = '5000'; // Cost of Goods Sold / Inventory Expense
    const ACCOUNT_SALARIES = '5300';
    const ACCOUNT_OTHER_INCOME = '4100';
    const ACCOUNT_ACCOUNTS_RECEIVABLE = '1100';
    const ACCOUNT_ACCOUNTS_PAYABLE = '2100'; // Accounts Payable (Liability)
    const ACCOUNT_REVENUE = '4000'; // Sales Revenue
    const ACCOUNT_VAT_LIABILITY = '2000'; // VAT Payable
    const ACCOUNT_CAPITAL = '3000'; // Owner/Capital
    const ACCOUNT_RETAINED_EARNINGS = '3100'; // Retained Earnings
    const ACCOUNT_COMMISSION_EXPENSE = '6100'; // Commission Expense
    const ACCOUNT_COMMISSIONS_PAYABLE = '2200'; // Commissions Payable (Liability)
    const ACCOUNT_MPESA = '1020'; // M-Pesa Account
    const ACCOUNT_CHEQUE = '1030'; // Cheques Receivable

    /**
     * Record accounting entry for a Payroll Run
     */
    public function recordPayrollExpense(PayrollRun $run, $user = null)
    {
        // Debit: Salaries Expense
        // Credit: Bank Account (Assuming direct transfer)
        
        $salariesAccount = ChartOfAccount::where('code', self::ACCOUNT_SALARIES)->first();
        $bankAccount = ChartOfAccount::where('code', self::ACCOUNT_BANK)->first() 
                    ?? ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();

        if (!$salariesAccount || !$bankAccount) {
            Log::warning('AccountingService: Missing default accounts for payroll.');
            return;
        }

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber($run->created_at),
            'entry_date' => $run->period_end, // Use period end or created_at? Period end usually better for accrual.
            'description' => "Payroll Run #{$run->id} ({$run->period_start->format('Y-m-d')} to {$run->period_end->format('Y-m-d')})",
            'reference_type' => 'PAYROLL',
            'reference_id' => $run->id,
            'total_debit' => $run->total_net, // Using Net for simplicity if tax handled separately, or Gross? 
                                              // Let's use Total Net as "Cash Out" and ignore tax liability accrual for MVP simplicity
                                              // Real logic: Dr Salaries (Gross), Cr Tax Payable, Cr Cash (Net).
                                              // For now: Dr Salaries (Net), Cr Cash (Net) - User asked for "deducts the money".
            'total_credit' => $run->total_net,
            'status' => 'POSTED',
            'created_by' => $user ? $user->username : 'system',
        ]);

        // Debit Salaries (Expense)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $salariesAccount->id,
            'debit_amount' => $run->total_net,
            'credit_amount' => 0,
            'description' => 'Staff Salaries Payment',
            'line_number' => 1,
        ]);

        // Credit Bank (Asset)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $bankAccount->id,
            'debit_amount' => 0,
            'credit_amount' => $run->total_net,
            'description' => 'Payroll Disbursement',
            'line_number' => 2,
        ]);
        
        return $entry;
    }

    /**
     * Record accounting entry for Inventory Adjustment
     */
    public function recordInventoryAdjustment(InventoryAdjustment $adjustment, $user = null)
    {
        // Increase: Dr Inventory, Cr COGS/Income
        // Decrease: Dr COGS/Expense, Cr Inventory
        
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        $cogsAccount = ChartOfAccount::where('code', self::ACCOUNT_COGS)->first();

        if (!$inventoryAccount || !$cogsAccount) {
            Log::warning('AccountingService: Missing default accounts for inventory adjustment.');
            return;
        }

        // We need a value. Adjustment only has quantity.
        // We need to fetch the cost price from Inventory.
        $inventoryItm = $adjustment->inventory; // Logic assumes relation exists
        if (!$inventoryItm) return;
        
        $costPrice = $inventoryItm->price; // Buying price
        $totalValue = $adjustment->quantity * $costPrice;

        if ($totalValue <= 0) return;

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber($adjustment->created_at),
            'entry_date' => $adjustment->created_at,
            'description' => "Stock Adjustment: {$inventoryItm->product_name} ({$adjustment->adjustment_type}) - {$adjustment->reason}",
            'reference_type' => 'INVENTORY_ADJ',
            'reference_id' => $adjustment->id,
            'total_debit' => $totalValue,
            'total_credit' => $totalValue,
            'status' => 'POSTED',
            'created_by' => $user ? $user->username : 'system',
        ]);

        if ($adjustment->adjustment_type === 'increase') {
            // Debit Inventory (Asset Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => $totalValue,
                'credit_amount' => 0,
                'description' => "Stock Increase: {$adjustment->reason}",
                'line_number' => 1,
            ]);

            // Credit COGS (Expense Reduction)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cogsAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $totalValue,
                'description' => "Cost Adjustment",
                'line_number' => 2,
            ]);
        } else {
            // DECREASE (Damaged, Expired, etc.)
            // Debit COGS (Expense Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cogsAccount->id,
                'debit_amount' => $totalValue,
                'credit_amount' => 0,
                'description' => "Stock Loss: {$adjustment->reason}",
                'line_number' => 1,
            ]);

            // Credit Inventory (Asset Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $totalValue,
                'description' => "Inventory Reduction",
                'line_number' => 2,
            ]);
        }
        
        return $entry;
    }

    /**
     * Record accounting entry for Expense
     */
    /**
     * Record accounting entry for Expense
     */
    public function recordExpense(Expense $expense, $user = null)
    {
        // Legacy alias or direct implementation
        return $this->recordDirectExpense($expense, $user);
    }

    /**
     * Record as Direct Expense (Paid Immediately)
     * Dr Expense Category
     * Cr Payment Account (Asset)
     */


    /**
     * Update accounting entry for Expense
     */
    public function updateExpenseAccounting(\App\Models\Expense $expense, $user = null)
    {
        $entry = JournalEntry::where('reference_type', 'EXPENSE')
            ->where('reference_id', $expense->id)
            ->first();

        if (!$entry) {
            return $this->recordExpense($expense, $user);
        }

        if (!$expense->category_id || !$expense->payment_account_id) {
            return null;
        }

        DB::beginTransaction();
        try {
            $entry->update([
                'entry_date' => $expense->expense_date,
                'description' => "Expense: {$expense->description} - {$expense->payee}",
                'total_debit' => $expense->amount,
                'total_credit' => $expense->amount,
            ]);

            $entry->lines()->delete();

            // Debit: Expense
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $expense->category_id,
                'debit_amount' => $expense->amount,
                'credit_amount' => 0,
                'description' => $expense->description,
                'line_number' => 1,
            ]);

            // Credit: Payment
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $expense->payment_account_id,
                'debit_amount' => 0,
                'credit_amount' => $expense->amount,
                'description' => "Payment to {$expense->payee}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to update expense accounting #{$expense->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ensure critical default accounts exist.
     * Self-healing method to prevent transaction failures on fresh installs.
     */
    public function ensureDefaultAccountsExist()
    {
        // 1. Create Root Accounts (Categories)
        $roots = [
            'Asset' => ['code' => '100', 'name' => 'Assets'],
            'Liability' => ['code' => '200', 'name' => 'Liabilities'],
            'Equity' => ['code' => '300', 'name' => 'Equity'],
            'Income' => ['code' => '400', 'name' => 'Income'],
            'Expense' => ['code' => '500', 'name' => 'Expenses'],
        ];

        $rootMap = [];
        foreach ($roots as $type => $data) {
            $root = ChartOfAccount::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'account_type' => $type,
                    'parent_id' => null,
                    'is_active' => true,
                    'description' => "System Root Account for {$type}"
                ]
            );
            $rootMap[$type] = $root->id;
        }

        // 2. Default Operational Accounts
        $accounts = [
            // ASSETS
            ['code' => self::ACCOUNT_CASH, 'name' => 'Cash on Hand', 'account_type' => 'Asset'],
            ['code' => self::ACCOUNT_BANK, 'name' => 'Bank Account', 'account_type' => 'Asset'],
            ['code' => self::ACCOUNT_INVENTORY, 'name' => 'Inventory', 'account_type' => 'Asset'],
            ['code' => self::ACCOUNT_ACCOUNTS_RECEIVABLE, 'name' => 'Accounts Receivable', 'account_type' => 'Asset'],

            // LIABILITIES
            ['code' => self::ACCOUNT_VAT_LIABILITY, 'name' => 'VAT Payable', 'account_type' => 'Liability'],
            ['code' => self::ACCOUNT_ACCOUNTS_PAYABLE, 'name' => 'Accounts Payable', 'account_type' => 'Liability'],
            ['code' => self::ACCOUNT_COMMISSIONS_PAYABLE, 'name' => 'Commissions Payable', 'account_type' => 'Liability'],

            // EQUITY
            ['code' => self::ACCOUNT_CAPITAL, 'name' => 'Owner/Capital', 'account_type' => 'Equity'],
            ['code' => self::ACCOUNT_RETAINED_EARNINGS, 'name' => 'Retained Earnings', 'account_type' => 'Equity'],

            // INCOME
            ['code' => self::ACCOUNT_REVENUE, 'name' => 'Sales Revenue', 'account_type' => 'Income'],
            ['code' => self::ACCOUNT_OTHER_INCOME, 'name' => 'Other Income', 'account_type' => 'Income'],

            // EXPENSES
            ['code' => self::ACCOUNT_COGS, 'name' => 'Cost of Goods Sold (COGS)', 'account_type' => 'Expense'],
            ['code' => self::ACCOUNT_SALARIES, 'name' => 'Salaries & Wages', 'account_type' => 'Expense'],
            ['code' => self::ACCOUNT_COMMISSION_EXPENSE, 'name' => 'Commission Expense', 'account_type' => 'Expense'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'account_type' => $acc['account_type'],
                    'parent_id' => $rootMap[$acc['account_type']] ?? null,
                    'is_active' => true,
                    'description' => 'System created default account',
                ]
            );
        }
    }

    /**
     * Helper to get the System Root Parent for a type
     */
    public function getSystemParentAccount($type)
    {
        // Ensure roots exist first
        if (!ChartOfAccount::where('code', '100')->exists()) {
            $this->ensureDefaultAccountsExist();
        }

        $code = match($type) {
            'Asset' => '100',
            'Liability' => '200',
            'Equity' => '300',
            'Income' => '400',
            'Expense' => '500',
            default => null
        };

        if ($code) {
            return ChartOfAccount::where('code', $code)->first();
        }
        return null;
    }

    /**
     * Record accounting entry for a POS Sale
     * Handles: Cost of Goods Sold (Dr COGS, Cr Inventory)
     *          Revenue (Dr Cash/Bank/AR, Cr Revenue, Cr VAT)
     */
    public function recordSale($sale, $user = null)
    {
        // 0. Self-heal: Ensure accounts exist
        $this->ensureDefaultAccountsExist();

        // 1. Identify Necessary Accounts
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        $cogsAccount = ChartOfAccount::where('code', self::ACCOUNT_COGS)->first();
        $revenueAccount = ChartOfAccount::where('code', self::ACCOUNT_REVENUE)->first();
        $vatAccount = ChartOfAccount::where('code', self::ACCOUNT_VAT_LIABILITY)->first();
        $arAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_RECEIVABLE)->first();

        // Check Critical Accounts (others are conditional)
        if (!$inventoryAccount || !$cogsAccount || !$revenueAccount) {
            Log::warning('AccountingService: Missing default accounts for Sale Recording (Inventory, COGS, or Revenue).');
            return null; // Should likely throw exception or silent fail based on strictness
        }

        // 2. Calculate Total Cost (COGS)
        $totalCost = 0;
        if (!empty($sale->sale_items)) {
            foreach ($sale->sale_items as $item) {
                // Determine product ID
                $productId = $item['product_id'] ?? ($item['id'] ?? null);
                
                // Skip COGS for Rentals (Asset remains on books, we just rent it out)
                $type = $item['type'] ?? 'sale';
                if ($type === 'rental') {
                    continue; 
                }

                if ($productId) {
                    $inventoryItem = \App\Models\Inventory::find($productId);
                    if ($inventoryItem) {
                        $costPrice = $inventoryItem->price; // 'price' is Cost Price
                        $qty = $item['quantity'] ?? 0;
                        $totalCost += ($costPrice * $qty);
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            // 3. Create Journal Entry Header
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($sale->created_at),
                'entry_date' => $sale->created_at,
                'description' => "POS Sale #{$sale->invoice_number} - {$sale->customer_name}",
                'reference_type' => 'SALE',
                'reference_id' => $sale->id,
                'total_debit' => $sale->total + $totalCost, // COGS + Asset In (Cash/AR)
                'total_credit' => $sale->total + $totalCost, // Inventory Out + Revenue + VAT
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            $lineNumber = 1;

            // --- PART A: COST OF GOODS SOLD ---
            if ($totalCost > 0) {
                // Dr COGS
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cogsAccount->id,
                    'debit_amount' => $totalCost,
                    'credit_amount' => 0,
                    'description' => "Cost of Goods Sold",
                    'line_number' => $lineNumber++,
                ]);

                // Cr Inventory
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCost,
                    'description' => "Inventory Reduction",
                    'line_number' => $lineNumber++,
                ]);
            }

            // --- PART B: REVENUE & ASSETS ---
            
            // Determine Debit Asset Account (Cash/Bank vs AR)
            $debitAccountId = null;
            $debitAccountName = "Payment";

            if ($sale->payment_status === 'Paid') {
                // Use helper method for payment account mapping
                $acc = $this->getPaymentAccount($sale->payment_method ?? 'cash');
                $debitAccountId = $acc ? $acc->id : null;
                $debitAccountName = $acc ? $acc->name : "Payment";
            } else {
                // Pending/Credit -> Accounts Receivable
                $debitAccountId = $arAccount ? $arAccount->id : null;
                $debitAccountName = "Accounts Receivable";
            }

            // Fallback if payment account not found
            if (!$debitAccountId) {
                 // Try Cash as ultimate fallback
                 $cashAcc = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
                 $debitAccountId = $cashAcc ? $cashAcc->id : null;
            }

            if ($debitAccountId) {
                // Dr Asset (Cash/Bank/AR) - Full Amount (Inc VAT)
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $debitAccountId,
                    'debit_amount' => $sale->total,
                    'credit_amount' => 0,
                    'description' => $debitAccountName . " - Sale #{$sale->invoice_number}",
                    'line_number' => $lineNumber++,
                ]);
            } else {
                Log::error("AccountingService: Could not determine debit account for Sale #{$sale->id}");
                throw new \Exception("Missing Asset Account for Sale Recording");
            }

            // Cr Revenue (Excl VAT)
            // Note: Sale model has 'vat' field.
            $revenueAmount = $sale->total - ($sale->vat ?? 0);
            
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $revenueAmount,
                'description' => "Sales Revenue",
                'line_number' => $lineNumber++,
            ]);

            // Cr VAT Liability (If VAT exists)
            if (($sale->vat ?? 0) > 0 && $vatAccount) {
                 JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $vatAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $sale->vat,
                    'description' => "VAT Output Tax",
                    'line_number' => $lineNumber++,
                ]);
            }

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record sale #{$sale->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record accounting entry for a Purchase Order (when received)
     * Handles: Inventory Increase (Dr Inventory, Cr Accounts Payable)
     */
    public function recordPurchaseOrder($order, $user = null)
    {
        // 1. Identify Necessary Accounts
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        $apAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_PAYABLE)->first();

        // Check Critical Accounts
        if (!$inventoryAccount || !$apAccount) {
            Log::warning('AccountingService: Missing default accounts for PO Recording (Inventory or AP).');
            return null;
        }

        DB::beginTransaction();
        try {
            // 2. Create Journal Entry Header
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($order->created_at),
                'entry_date' => $order->actual_delivery_date ?? now(),
                'description' => "Purchase Order #{$order->order_number} - {$order->supplier_name}",
                'reference_type' => 'PURCHASE_ORDER',
                'reference_id' => $order->id,
                'total_debit' => $order->total_amount,
                'total_credit' => $order->total_amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Inventory (Asset Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => $order->total_amount,
                'credit_amount' => 0,
                'description' => "Goods Received - PO #{$order->order_number}",
                'line_number' => 1,
            ]);

            // Cr Accounts Payable (Liability Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $apAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $order->total_amount,
                'description' => "Payable to {$order->supplier_name}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record PO #{$order->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record accounting entry for a Purchase Order Payment
     * Handles: Dr Accounts Payable, Cr Cash/Bank
     */
    public function recordPurchaseOrderPayment(\App\Models\PurchaseOrder $order, $amount, $paymentMethod, $user = null)
    {
        // 1. Identify Accounts
        $apAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_PAYABLE)->first();
        
        // Use helper method for payment account mapping
        $assetAccount = $this->getPaymentAccount($paymentMethod ?? 'cash');

        // Fallback to cash if still null
        if (!$assetAccount) {
            $assetAccount = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
        }

        if (!$apAccount || !$assetAccount) {
            Log::warning('AccountingService: Missing accounts for PO Payment.');
            return null;
        }

        // Check Period Lock
        if (!$this->checkPeriodStatus(now())) {
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(),
                'description' => "Payment for PO #{$order->order_number} to {$order->supplier_name}",
                'reference_type' => 'PURCHASE_ORDER_PAYMENT',
                'reference_id' => $order->id,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Accounts Payable (Liability Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $apAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => "Payment to {$order->supplier_name}",
                'line_number' => 1,
            ]);

            // Cr Asset (Cash/Bank Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => "Paid via {$paymentMethod}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record PO Payment #{$order->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record accounting entry for an Invoice Payment
     * Handles: Dr Cash/Bank, Cr Accounts Receivable
     */
    public function recordInvoicePayment($payment, $user = null)
    {
        // 1. Identify Accounts
        $arAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_RECEIVABLE)->first();
        
        // Use helper method for payment account mapping
        $assetAccount = $this->getPaymentAccount($payment->payment_method ?? 'cash');

        // Fallback to cash if still null
        if (!$assetAccount) {
            $assetAccount = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
        }

        if (!$arAccount || !$assetAccount) {
            Log::warning('AccountingService: Missing accounts for Invoice Payment.');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($payment->created_at),
                'entry_date' => $payment->payment_date ?? now(),
                'description' => "Payment for Invoice #{$payment->pos_sale_id} via {$payment->payment_method}",
                'reference_type' => 'PosSalePayment',
                'reference_id' => $payment->id,
                'total_debit' => $payment->amount,
                'total_credit' => $payment->amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Asset (Cash/Bank Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'debit_amount' => $payment->amount,
                'credit_amount' => 0,
                'description' => "Received {$payment->payment_method}",
                'line_number' => 1,
            ]);

            // Cr Accounts Receivable (Asset Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $arAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $payment->amount,
                'description' => "Invoice #{$payment->pos_sale_id} Payment",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Payment #{$payment->id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Record Capital Investment (Owner Injection)
     * Debit: Bank/Cash, Credit: Equity (Capital)
     */
    public function recordCapitalInvestment($amount, $accountId, $date, $description, $user = null, $shareholderId = null, $specificEquityAccountId = null)
    {
        $capitalAccount = null;

        if ($specificEquityAccountId) {
            $capitalAccount = ChartOfAccount::find($specificEquityAccountId);
        }

        if (!$capitalAccount && $shareholderId) {
            $shareholder = \App\Models\Shareholder::find($shareholderId);
            if ($shareholder && $shareholder->capital_account_id) {
                $capitalAccount = ChartOfAccount::find($shareholder->capital_account_id);
            }
        }

        // Fallback to generic Capital account if no shareholder specific one found
        if (!$capitalAccount) {
            $capitalAccount = ChartOfAccount::where('code', self::ACCOUNT_CAPITAL)->first();
        }

        $assetAccount = ChartOfAccount::find($accountId);

        if (!$capitalAccount || !$assetAccount) {
            Log::warning('AccountingService: Missing accounts for Capital Investment.');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($date),
                'entry_date' => $date,
                'description' => "Capital Investment: {$description}",
                'reference_type' => 'CAPITAL_INVESTMENT',
                'reference_id' => $shareholderId, // Link to shareholder if possible
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Asset (Bank/Cash Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => $description,
                'line_number' => 1,
            ]);

            // Cr Equity (Capital Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $capitalAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => "Owner Capital Injection" . ($shareholderId ? " (Shareholder ID: $shareholderId)" : ""),
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Capital Investment: " . $e->getMessage());
            return null;
        }
    }



    /**
     * Get or Create Commission Accounts (Expense and Payable)
     * Returns array ['expense_id' => int, 'payable_id' => int]
     */
    public function getCommissionAccounts()
    {
        $parentExpense = $this->getSystemParentAccount('Expense');
        $expenseAccount = ChartOfAccount::firstOrCreate(
            ['code' => self::ACCOUNT_COMMISSION_EXPENSE],
            [
                'name' => 'Commission Expense',
                'account_type' => 'Expense',
                'parent_id' => $parentExpense?->id,
                'is_active' => true,
                'description' => 'Staff commissions'
            ]
        );

        $parentLiability = $this->getSystemParentAccount('Liability');
        $payableAccount = ChartOfAccount::firstOrCreate(
            ['code' => self::ACCOUNT_COMMISSIONS_PAYABLE],
            [
                'name' => 'Commissions Payable',
                'account_type' => 'Liability',
                'parent_id' => $parentLiability?->id,
                'is_active' => true,
                'description' => 'Unpaid staff commissions'
            ]
        );

        return [
            'expense_id' => $expenseAccount->id,
            'payable_id' => $payableAccount->id
        ];
    }
    
    /**
     * Record accounting entry for Commission Payment
     * Debit: Commissions Payable, Credit: Cash/Bank
     */
    public function recordCommissionPayment(\App\Models\Commission $commission, $user = null)
    {
        $payableAccount = ChartOfAccount::where('code', self::ACCOUNT_COMMISSIONS_PAYABLE)->first();
        // Assume Bank for payments, or fallback to Cash
        $assetAccount = ChartOfAccount::where('code', self::ACCOUNT_BANK)->first() 
                     ?? ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();

        if (!$payableAccount || !$assetAccount) {
            Log::warning('AccountingService: Missing accounts for Commission Payment.');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(),
                'description' => "Commission Payment: {$commission->description} (Staff #{$commission->staff_id})",
                'reference_type' => 'COMMISSION_PAYMENT',
                'reference_id' => $commission->id,
                'total_debit' => $commission->amount,
                'total_credit' => $commission->amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Commissions Payable (Liability Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $payableAccount->id,
                'debit_amount' => $commission->amount,
                'credit_amount' => 0,
                'description' => "Settlement of Payable",
                'line_number' => 1,
            ]);

            // Cr Cash/Bank (Asset Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $commission->amount,
                'description' => "Payment Disbursed to Staff",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Commission Payment #{$commission->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the appropriate Chart of Account for a payment method
     * @param string $paymentMethod
     * @return ChartOfAccount|null
     */
    private function getPaymentAccount($paymentMethod)
    {
        $method = trim($paymentMethod); // Keep original case for name if needed
        $normalized = strtolower($method);
        
        // Map common payment methods to default account codes
        $accountCode = match($normalized) {
            'cash', 'cash on delivery' => self::ACCOUNT_CASH,
            'm-pesa', 'mpesa' => self::ACCOUNT_MPESA,
            'cheque', 'check' => self::ACCOUNT_CHEQUE,
            // 'bank' etc maps to explicit bank, but "Equity Bank" should not map to "Bank 1010" implicitly unless we want it to.
            // Let's keep specific generic terms mapping to default Bank
            'bank', 'bank transfer', 'transfer' => self::ACCOUNT_BANK,
            default => null, // No default code mapping
        };
        
        $account = null;
        
        if ($accountCode) {
            $account = ChartOfAccount::where('code', $accountCode)->first();
            // Auto-create simplified defaults if missing
            if (!$account && in_array($accountCode, [self::ACCOUNT_MPESA, self::ACCOUNT_CHEQUE])) {
                $account = $this->createPaymentAccount($accountCode);
            }
        }
        
        // If still no account found (either not in map, or mapped but db missing), try dynamic lookup/create
        if (!$account) {
            // 1. Try finding by name (Case insensitive)
            $account = ChartOfAccount::where(DB::raw('LOWER(name)'), $normalized)->first();
            
            // 2. If not found, Create it!
            if (!$account) {
                // Generate a pseudo-random code or next available code? 
                // For simplicity, we'll try to find a code range. 
                // Assets usually 1000-1999. Let's use 1100+ for Custom
                
                // Simple hash code or just random for now to avoid collision logic overhead in this snippet
                $code = '11' . rand(10, 99); 
                while(ChartOfAccount::where('code', $code)->exists()) {
                    $code = '11' . rand(10, 99) . rand(0,9);
                }

                $parent = $this->getSystemParentAccount('Asset');

                $account = ChartOfAccount::create([
                    'code' => $code,
                    'name' => ucwords($method), // Use original casing
                    'account_type' => 'Asset',
                    'parent_id' => $parent?->id,
                    'is_active' => true,
                    'description' => "Auto-created account for payment method: {$method}"
                ]);
            }
        }
        
        return $account;
    }

    /**
     * Create a payment account if it doesn't exist
     */
    private function createPaymentAccount($accountCode)
    {
        $accountDetails = [
            self::ACCOUNT_MPESA => ['name' => 'M-Pesa', 'description' => 'M-Pesa mobile money payments'],
            self::ACCOUNT_CHEQUE => ['name' => 'Cheques Receivable', 'description' => 'Uncleared cheques'],
            self::ACCOUNT_CASH => ['name' => 'Cash Account', 'description' => 'Cash on hand'],
        ];
        
        if (!isset($accountDetails[$accountCode])) {
            return null;
        }
        
        $parent = $this->getSystemParentAccount('Asset');
        
        return ChartOfAccount::create([
            'code' => $accountCode,
            'name' => $accountDetails[$accountCode]['name'],
            'account_type' => 'Asset',
            'parent_id' => $parent?->id,
            'is_active' => true,
            'description' => $accountDetails[$accountCode]['description']
        ]);
    }

    /**
     * Close Income and Expense accounts to Retained Earnings
     * Should be run manually at the end of each accounting period
     * 
     * @param string $periodEndDate
     * @param \App\Models\User|null $user
     * @return JournalEntry|null
     */
    public function closePeriodToRetainedEarnings($periodEndDate, $user = null)
    {
        // 1. Get or create Retained Earnings account
        $retainedEarningsAccount = ChartOfAccount::where('code', self::ACCOUNT_RETAINED_EARNINGS)->first();
        
        if (!$retainedEarningsAccount) {
            $parent = $this->getSystemParentAccount('Equity');

            $retainedEarningsAccount = ChartOfAccount::create([
                'code' => self::ACCOUNT_RETAINED_EARNINGS,
                'name' => 'Retained Earnings',
                'account_type' => 'Equity',
                'parent_id' => $parent?->id,
                'is_active' => true,
                'description' => 'Accumulated profits retained in the business'
            ]);
        }
        
        // 2. Calculate Net Income for the period
        $income = JournalEntryLine::whereHas('account', fn($q) => $q->where('account_type', 'Income'))
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'POSTED')
                ->whereDate('entry_date', '<=', $periodEndDate))
            ->sum(DB::raw('credit_amount - debit_amount'));
            
        $expenses = JournalEntryLine::whereHas('account', fn($q) => $q->where('account_type', 'Expense'))
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'POSTED')
                ->whereDate('entry_date', '<=', $periodEndDate))
            ->sum(DB::raw('debit_amount - credit_amount'));
        
        $netIncome = $income - $expenses;
        
        if (abs($netIncome) < 0.01) {
            Log::info("Period closing: Net income is zero, no entry needed.");
            return null;
        }
        
        // 3. Create Closing Entry
        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($periodEndDate),
                'entry_date' => $periodEndDate,
                'description' => "Period End Closing - Transfer Net Income to Retained Earnings",
                'reference_type' => 'PERIOD_CLOSING',
                'reference_id' => null,
                'total_debit' => abs($netIncome),
                'total_credit' => abs($netIncome),
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);
            
            if ($netIncome > 0) {
                // Profit: Cr Retained Earnings
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $retainedEarningsAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $netIncome,
                    'description' => 'Net Income for the period',
                    'line_number' => 2,
                ]);
                
                // Dr Income Summary (placeholder - in full implementation would close each I/E account)
                // For MVP, we create a balancing entry to a temporary account or leave it abstract
                // The important part is crediting Retained Earnings
                
            } else {
                // Loss: Dr Retained Earnings
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $retainedEarningsAccount->id,
                    'debit_amount' => abs($netIncome),
                    'credit_amount' => 0,
                    'description' => 'Net Loss for the period',
                    'line_number' => 2,
                ]);
            }
            
            DB::commit();
            Log::info("Period closed successfully. Net Income: {$netIncome}");
            return $entry;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to close period: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record Dividend Distribution
     * Dr Retained Earnings, Cr Cash/Bank
     */
    public function recordDividendDistribution($amount, $paymentAccountId, $date, $description, $user = null, $shareholderId = null)
    {
        $retainedEarningsAccount = ChartOfAccount::where('code', self::ACCOUNT_RETAINED_EARNINGS)->first();
        $paymentAccount = ChartOfAccount::find($paymentAccountId);
        
        if (!$retainedEarningsAccount || !$paymentAccount) {
            Log::warning('Missing accounts for dividend distribution');
            return null;
        }
        
        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($date),
                'entry_date' => $date,
                'description' => "Dividend Distribution: {$description}",
                'reference_type' => 'DIVIDEND',
                'reference_id' => $shareholderId,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);
            
            // Dr Retained Earnings (Equity decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $retainedEarningsAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => 'Dividend distribution',
                'line_number' => 1,
            ]);
            
            // Cr Cash/Bank (Asset decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $paymentAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => 'Dividend payment',
                'line_number' => 2,
            ]);
            
            DB::commit();
            return $entry;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to record dividend: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record accounting entry for a Refund (Reverses original sale)
     */
    public function recordRefund($refund, $sale, $user = null)
    {
        // Refund reverses the original sale entry:
        // 1. Reverse COGS: Cr COGS, Dr Inventory (restore inventory value)
        // 2. Reverse Revenue: Dr Revenue, Dr VAT Liability
        // 3. Reverse Asset: Cr Cash/Bank/AR (return money or reduce receivable)

        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        $cogsAccount = ChartOfAccount::where('code', self::ACCOUNT_COGS)->first();
        $revenueAccount = ChartOfAccount::where('code', self::ACCOUNT_REVENUE)->first();
        $vatAccount = ChartOfAccount::where('code', self::ACCOUNT_VAT_LIABILITY)->first();

        if (!$inventoryAccount || !$cogsAccount || !$revenueAccount) {
            Log::warning('AccountingService: Missing accounts for refund recording.');
            return null;
        }

        // Calculate refund COGS (cost of items being returned)
        $totalCost = 0;
        if (!empty($refund->refund_items)) {
            foreach ($refund->refund_items as $item) {
                $productId = $item['product_id'] ?? null;
                $type = $item['type'] ?? 'sale';
                
                if ($type === 'rental') continue; // Skip rentals
                
                if ($productId) {
                    $inventoryItem = \App\Models\Inventory::find($productId);
                    if ($inventoryItem) {
                        $costPrice = $inventoryItem->price;
                        $qty = $item['quantity'] ?? 0;
                        $totalCost += ($costPrice * $qty);
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(),
                'description' => "Refund {$refund->refund_number} for Sale #{$sale->invoice_number}",
                'reference_type' => 'REFUND',
                'reference_id' => $refund->id,
                'total_debit' => $refund->refund_amount + $totalCost,
                'total_credit' => $refund->refund_amount + $totalCost,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            $lineNumber = 1;

            // --- REVERSE COGS ---
            if ($totalCost > 0) {
                // Cr COGS (reduce expense)
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cogsAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCost,
                    'description' => 'COGS Reversal',
                    'line_number' => $lineNumber++,
                ]);

                // Dr Inventory (restore asset)
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => $totalCost,
                    'credit_amount' => 0,
                    'description' => 'Inventory Restoration',
                    'line_number' => $lineNumber++,
                ]);
            }

            // --- REVERSE REVENUE ---
            $revenueAmount = $refund->refund_amount - ($sale->vat ?? 0);
            
            // Dr Revenue (reduce income)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit_amount' => $revenueAmount,
                'credit_amount' => 0,
                'description' => 'Revenue Reversal',
                'line_number' => $lineNumber++,
            ]);

            // Dr VAT Liability (reduce liability)
            if ($vatAccount && ($sale->vat ?? 0) > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $vatAccount->id,
                    'debit_amount' => $sale->vat,
                    'credit_amount' => 0,
                    'description' => 'VAT Reversal',
                    'line_number' => $lineNumber++,
                ]);
            }

            // --- REVERSE ASSET (Return Money) ---
            $creditAccountId = null;
            $creditAccountName = "Refund Payment";

            // Use refund method if specified, otherwise use original payment method
            $paymentMethod = $refund->refund_method ?? $sale->payment_method;
            
            if ($paymentMethod === 'Credit Note') {
                // Credit Note: Cr Accounts Receivable (reduce what customer owes)
                $arAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_RECEIVABLE)->first();
                $creditAccountId = $arAccount ? $arAccount->id : null;
                $creditAccountName = "Accounts Receivable Reduction";
            } else {
                // Cash refund: Cr Cash/Bank/M-Pesa
                $acc = $this->getPaymentAccount($paymentMethod);
                $creditAccountId = $acc ? $acc->id : null;
                $creditAccountName = $acc ? $acc->name : "Refund Payment";
            }

            if (!$creditAccountId) {
                $cashAcc = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
                $creditAccountId = $cashAcc ? $cashAcc->id : null;
            }

            if ($creditAccountId) {
                // Cr Cash/Bank/AR (reduce asset)
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $creditAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $refund->refund_amount,
                    'description' => $creditAccountName . " - Refund {$refund->refund_number}",
                    'line_number' => $lineNumber++,
                ]);
            } else {
                throw new \Exception("Missing Asset Account for Refund Recording");
            }

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to record refund: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Correct the payment method for a sale (Mistake reversal).
     * This moves the asset from the old account to the new account (or AR).
     */
    public function correctSalePaymentMethod($sale, $oldMethod, $newMethod, $user = null)
    {
        $saleTotal = $sale->total;
        $arAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_RECEIVABLE)->first();

        // 1. Determine "Old" Asset Account (Where money was)
        $oldIsCredit = ($oldMethod === 'Credit');
        $oldAccount = null;
        if ($oldIsCredit) {
            $oldAccount = $arAccount;
        } else {
            $oldAccount = $this->getPaymentAccount($oldMethod) ?? ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
        }

        // 2. Determine "New" Asset Account (Where money should be)
        $newIsCredit = ($newMethod === 'Credit');
        $newAccount = null;
        if ($newIsCredit) {
            $newAccount = $arAccount;
        } else {
            $newAccount = $this->getPaymentAccount($newMethod);
            if (!$newAccount) {
                 // Fallback to Cash if not found/mapped
                 $newAccount = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
            }
        }

        if (!$oldAccount || !$newAccount) {
            Log::error("AccountingService: Cannot correct payment method. Missing accounts.");
            return false;
        }

        if ($oldAccount->id === $newAccount->id) {
            return true; // No accounting change needed
        }

        DB::beginTransaction();
        try {
            // 3. Create Correction Journal Entry
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(),
                'description' => "Correction: Change Payment from {$oldMethod} to {$newMethod} (Sale #{$sale->invoice_number})",
                'reference_type' => 'SALE_CORRECTION',
                'reference_id' => $sale->id,
                'total_debit' => $saleTotal,
                'total_credit' => $saleTotal,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Debit New Account (Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $newAccount->id,
                'debit_amount' => $saleTotal,
                'credit_amount' => 0,
                'description' => "Transfer to {$newMethod}",
                'line_number' => 1,
            ]);

            // Credit Old Account (Decrease/Reverse)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $oldAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $saleTotal,
                'description' => "Reversal from {$oldMethod}",
                'line_number' => 2,
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Correction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record a Direct Expense (Paid Immediately)
     * Dr Expense Account / Cr Bank/Cash
     * 
     * @param Expense $expense - The expense record with status='paid'
     * @param mixed $user
     * @return JournalEntry|null
     */
    public function recordDirectExpense(Expense $expense, $user = null)
    {
        $expenseAccount = $expense->category;
        $paymentAccount = $expense->paymentAccount;

        if (!$expenseAccount || !$paymentAccount) {
            Log::warning('AccountingService: Missing accounts for Direct Expense recording.');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($expense->expense_date),
                'entry_date' => $expense->expense_date,
                'description' => "Expense: {$expense->description} - {$expense->payee}",
                'reference_type' => 'EXPENSE',
                'reference_id' => $expense->id,
                'total_debit' => $expense->amount,
                'total_credit' => $expense->amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Expense (Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $expenseAccount->id,
                'debit_amount' => $expense->amount,
                'credit_amount' => 0,
                'description' => $expense->description,
                'line_number' => 1,
            ]);

            // Cr Payment Account (Asset Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $paymentAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $expense->amount,
                'description' => "Payment to {$expense->payee}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Direct Expense #{$expense->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record a Bill (Unpaid Expense)
     * Dr Expense Account / Cr Accounts Payable
     * 
     * @param Expense $expense - The expense record with status='unpaid'
     * @param mixed $user
     * @return JournalEntry|null
     */
    public function recordBill(Expense $expense, $user = null)
    {
        // Get Accounts Payable
        $apAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_PAYABLE)->first();
        
        // Get Expense Category Account
        $expenseAccount = $expense->category;

        if (!$apAccount || !$expenseAccount) {
            Log::warning('AccountingService: Missing accounts for Bill recording (AP or Expense Category).');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($expense->expense_date),
                'entry_date' => $expense->expense_date,
                'description' => "Bill: {$expense->description} - {$expense->payee}" . ($expense->reference_number ? " (Ref: {$expense->reference_number})" : ""),
                'reference_type' => 'EXPENSE_BILL',
                'reference_id' => $expense->id,
                'total_debit' => $expense->amount,
                'total_credit' => $expense->amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Expense (Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $expenseAccount->id,
                'debit_amount' => $expense->amount,
                'credit_amount' => 0,
                'description' => $expense->description,
                'line_number' => 1,
            ]);

            // Cr Accounts Payable (Liability Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $apAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $expense->amount,
                'description' => "Payable to {$expense->payee}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Bill #{$expense->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Pay a Bill (Full or Partial)
     * Dr Accounts Payable / Cr Bank/Cash
     * 
     * @param Expense $expense - The bill being paid
     * @param float $amount - Amount being paid
     * @param int $sourceAccountId - Payment source (Bank, Cash, etc.)
     * @param mixed $user
     * @return JournalEntry|null
     */



    /**
     * Pay a Bill (Partial or Full)
     * Dr Accounts Payable
     * Cr Bank/Cash
     */
    public function payBill(Expense $expense, $amount, $paymentAccountId, $user = null)
    {
        $apAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_PAYABLE)->first();
        $paymentAccount = ChartOfAccount::find($paymentAccountId);

        if (!$apAccount || !$paymentAccount) {
             Log::error('AccountingService: Missing AP or Payment accounts for Bill Payment.');
             return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(), // Payment Date
                'description' => "Bill Payment: {$expense->payee} (Ref: {$expense->reference_number})",
                'reference_type' => 'EXPENSE_PAYMENT',
                'reference_id' => $expense->id,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Debit: Accounts Payable (Liability Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $apAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => "Payment for Bill #{$expense->id}",
                'line_number' => 1,
            ]);

            // Credit: Bank/Cash (Asset Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $paymentAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => "Payment via {$paymentAccount->name}",
                'line_number' => 2,
            ]);
            
            DB::commit();
            return $entry;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to pay Bill: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Record Inter-Account Transfer
     * Dr Destination Account / Cr Source Account
     * 
     * @param int $fromAccountId - Source account
     * @param int $toAccountId - Destination account
     * @param float $amount - Transfer amount
     * @param string|null $date - Date of transfer
     * @param string $description - Transfer description
     * @param mixed $user
     * @return JournalEntry|null
     */
    public function recordTransfer(int $fromAccountId, int $toAccountId, float $amount, $date = null, string $description = 'Fund Transfer', $user = null)
    {
        $fromAccount = ChartOfAccount::find($fromAccountId);
        $toAccount = ChartOfAccount::find($toAccountId);
        $date = $date ?? now();

        if (!$fromAccount || !$toAccount) {
            Log::warning('AccountingService: Invalid accounts for transfer.');
            return null;
        }

        if ($fromAccountId === $toAccountId) {
            Log::warning('AccountingService: Cannot transfer to same account.');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($date),
                'entry_date' => $date,
                'description' => $description,
                'reference_type' => 'TRANSFER',
                'reference_id' => null,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Destination (Increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $toAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => "Transfer from {$fromAccount->name}",
                'line_number' => 1,
            ]);

            // Cr Source (Decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $fromAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => "Transfer to {$toAccount->name}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record transfer: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Checks if the accounting period for a given date is open.
     *
     * @param \DateTime|string $date
     * @return bool
     */
    protected function checkPeriodStatus($date): bool
    {
        if (\App\Models\AccountingPeriod::isDateClosed($date)) {
            $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
            Log::warning("AccountingService: Attempted to post to Closed Period for date {$formattedDate}.");
            return false;
        }
        return true;
    }

    /**
     * Post Journal Entries for a completed POS Sale (Revenue + COGS)
     */
    public function postSaleJournal(\App\Models\PosSale $sale)
    {
        // Assuming the user intended to add the import at the top of the file.
        // The provided snippet was syntactically incorrect for an import within the method signature.
        // Also, the instruction mentioned calling `checkPeriodStatus` in `recordJournalEntry`,
        // but `recordJournalEntry` is not present. I'm adding a call here as a placeholder
        // for where such a check might be relevant, assuming it was a general instruction
        // to use the new method.

        // Check if the accounting period is open for the sale date
        if (!$this->checkPeriodStatus($sale->created_at)) {
            Log::error("AccountingService: Cannot post sale #{$sale->id}. Accounting period is not open.");
            return;
        }

        DB::transaction(function () use ($sale) {
            // 1. Identify Accounts
            $revenueAccount = ChartOfAccount::where('code', self::ACCOUNT_REVENUE)->first();
            $vatAccount = ChartOfAccount::where('code', self::ACCOUNT_VAT_LIABILITY)->first();
            $cogsAccount = ChartOfAccount::where('code', self::ACCOUNT_COGS)->first();
            $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
            
            // Determine Debit Account (Cash/Bank/AR)
            $debitAccount = $this->getPaymentAccount($sale->payment_method);

            if (!$revenueAccount || !$debitAccount) {
                Log::error("AccountingService: Missing accounts for Sale #{$sale->id}");
                return;
            }

            // 2. REVENUE ENTRY (Dr Asset / Cr Revenue / Cr VAT)
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($sale->created_at),
                'entry_date' => $sale->created_at,
                'description' => "POS Sale #{$sale->invoice_number} ({$sale->customer_name})",
                'reference_type' => 'SALE',
                'reference_id' => $sale->id,
                'total_debit' => $sale->total,
                'total_credit' => $sale->total,
                'status' => 'POSTED',
                'created_by' => 'system',
            ]);

            // Dr Asset (Cash/Bank)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccount->id,
                'debit_amount' => $sale->total,
                'credit_amount' => 0,
                'description' => "Payment via {$sale->payment_method}",
                'line_number' => 1,
            ]);

            // Cr VAT (if applicable)
            // Use explicit VAT from DB
            $revenueToPost = $sale->total - $sale->vat; // Total collected - Tax collected

            if ($sale->vat > 0 && $vatAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $vatAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $sale->vat,
                    'description' => 'VAT Liability (16%)',
                    'line_number' => 2,
                ]);
            }

            // Cr Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $revenueToPost,
                'description' => 'Sales Revenue',
                'line_number' => 3,
            ]);


            // 3. COGS ENTRY (Dr COGS / Cr Inventory)
            // Calculate Total Cost
            $totalCost = 0;
            if (is_array($sale->sale_items)) {
                foreach ($sale->sale_items as $item) {
                    $qty = $item['quantity'] ?? 0;
                    // Try to get cost from snapshot (historical)
                    $wac = $item['product_snapshot']['moving_average_cost'] ?? 0;
                    $buyingPrice = $item['product_snapshot']['price'] ?? 0;
                    $costPrice = ($wac > 0) ? $wac : $buyingPrice;
                    
                    // If snapshot is missing data, try fetching current (Not ideal, but fallback)
                    if ($costPrice == 0 && !empty($item['product_id'])) {
                        $product = \App\Models\Inventory::find($item['product_id']);
                        if ($product) {
                            $costPrice = ($product->moving_average_cost > 0) ? $product->moving_average_cost : $product->price;
                        }
                    }

                    $totalCost += ($costPrice * $qty);
                }
            }

            if ($totalCost > 0 && $cogsAccount && $inventoryAccount) {
                $cogsEntry = JournalEntry::create([
                    'entry_number' => JournalEntry::generateEntryNumber($sale->created_at) . '-COGS',
                    'entry_date' => $sale->created_at,
                    'description' => "COGS for Sale #{$sale->invoice_number}",
                    'reference_type' => 'SALE_COGS',
                    'reference_id' => $sale->id,
                    'total_debit' => $totalCost,
                    'total_credit' => $totalCost,
                    'status' => 'POSTED',
                    'created_by' => 'system',
                ]);

                // Dr COGS
                JournalEntryLine::create([
                    'journal_entry_id' => $cogsEntry->id,
                    'account_id' => $cogsAccount->id,
                    'debit_amount' => $totalCost,
                    'credit_amount' => 0,
                    'description' => 'Cost of Goods Sold',
                    'line_number' => 1,
                ]);

                // Cr Inventory
                JournalEntryLine::create([
                    'journal_entry_id' => $cogsEntry->id,
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCost,
                    'description' => 'Inventory Asset Reduction',
                    'line_number' => 2,
                ]);
            }
        });
    }

    /**
     * Record Stock Purchase (Direct Receipt without PO)
     * Dr Inventory (Asset)
     * Cr AP (Liability) or Payment Account (Asset)
     */
    public function recordStockPurchase(\App\Models\InventoryAdjustment $adjustment, float $amount, $paymentAccountId = null, $vendorId = null, $user = null)
    {
        // 1. Identify Inventory Account
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        if (!$inventoryAccount) {
            Log::error('AccountingService: Missing Inventory Account for Purchase.');
            return null;
        }

        DB::beginTransaction();
        try {
            // Determine Credit side
            $creditAccountId = null;
            $description = "Stock Purchase: " . $adjustment->inventory->product_name;
            $status = ($paymentAccountId) ? 'PAID' : 'UNPAID';

            if ($paymentAccountId) {
                // Direct Payment (Cash/Bank)
                $creditAccountId = $paymentAccountId;
                $description .= " (Cash Purchase)";
            } else {
                // Credit Purchase (AP)
                $apAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_PAYABLE)->first();
                if (!$apAccount) {
                    throw new \Exception("Missing Accounts Payable Account");
                }
                $creditAccountId = $apAccount->id;
                $description .= " (Credit Purchase)";

                // Create an Expense record purely for tracking the Bill to be paid later
                // The Journal Entry below handles the actual accounting (Dr Inventory, Cr AP)
                // So this Expense record should NOT trigger another GL entry when created (handled by controller usually, but here we do it manually)
                Expense::create([
                    'payee' => $vendorId ? \App\Models\Vendor::find($vendorId)->name : 'Direct Stock Purchase',
                    'description' => $description,
                    'amount' => $amount,
                    'expense_date' => now(),
                    'category_id' => $inventoryAccount->id, // Use Inventory Account as category so it maps correctly
                    'status' => 'unpaid',
                    'vendor_id' => $vendorId,
                    'payment_account_id' => null, // Not paid yet
                    'due_date' => now()->addDays(30), // Default 30 days credit
                    'reference_number' => $adjustment->reference ?? null,
                    'created_by' => $user ? $user->id : null,
                ]);
            }

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($adjustment->created_at),
                'entry_date' => $adjustment->created_at,
                'description' => $description,
                'reference_type' => 'INVENTORY_ADJUSTMENT',
                'reference_id' => $adjustment->id,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Inventory
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => "Stock In: " . $adjustment->inventory->product_name,
                'line_number' => 1,
            ]);

            // Cr Payment/AP
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccountId,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => "Purchase Payment / Payable",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Stock Purchase: " . $e->getMessage());
            // We might want to rethrow or handle gracefully.
            // Since Inventory is already updated, we should probably fail hard or log/alert.
            // Rethrowing allows Controller to rollback DB transaction (if calling within transaction).
            throw $e;
        }
    }

    /**
     * Record Rental Revenue (when rental is created/invoiced)
     * Dr Accounts Receivable / Cr Rental Revenue
     */
    public function recordRental($rental, $user = null)
    {
        $arAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_RECEIVABLE)->first();
        $revenueAccount = ChartOfAccount::firstOrCreate(
            ['code' => '4200'],
            [
                'name' => 'Rental Revenue',
                'account_type' => 'Income',
                'description' => 'Revenue from equipment rentals',
                'is_active' => true,
            ]
        );

        if (!$arAccount || !$revenueAccount) {
            Log::warning('AccountingService: Missing accounts for Rental.');
            return null;
        }

        // Calculate total from rental items if not set
        $totalAmount = $rental->total_amount ?? 0;
        if ($totalAmount <= 0) return null;

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($rental->rented_at ?? now()),
                'entry_date' => $rental->rented_at ?? now(),
                'description' => "Rental #{$rental->id}" . ($rental->customer ? " - {$rental->customer->name}" : ""),
                'reference_type' => 'RENTAL',
                'reference_id' => $rental->id,
                'total_debit' => $totalAmount,
                'total_credit' => $totalAmount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr AR
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $arAccount->id,
                'debit_amount' => $totalAmount,
                'credit_amount' => 0,
                'description' => 'Rental Invoice',
                'line_number' => 1,
            ]);

            // Cr Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $totalAmount,
                'description' => 'Rental Revenue',
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Rental #{$rental->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record Rental Payment (when customer pays)
     * Dr Cash/Bank / Cr Accounts Receivable
     */
    public function recordRentalPayment($rental, $amount, $paymentMethod, $user = null)
    {
        $arAccount = ChartOfAccount::where('code', self::ACCOUNT_ACCOUNTS_RECEIVABLE)->first();
        $assetAccount = $this->getPaymentAccount($paymentMethod);

        if (!$assetAccount) {
            $assetAccount = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
        }

        if (!$arAccount || !$assetAccount) {
            Log::warning('AccountingService: Missing accounts for Rental Payment.');
            return null;
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(now()),
                'entry_date' => now(),
                'description' => "Rental Payment #{$rental->id}",
                'reference_type' => 'RENTAL_PAYMENT',
                'reference_id' => $rental->id,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Cash/Bank
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => "Payment via {$paymentMethod}",
                'line_number' => 1,
            ]);

            // Cr AR
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $arAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => 'Rental Payment Received',
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Rental Payment #{$rental->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record Stock Transfer (Memo Entry)
     * Dr Inventory (Destination) / Cr Inventory (Source)
     */
    public function recordStockTransfer($transfer, $user = null)
    {
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        
        if (!$inventoryAccount) {
            Log::warning('AccountingService: Missing Inventory account for Stock Transfer.');
            return null;
        }

        // Calculate total value if not set
        $totalValue = $transfer->total_value ?? 0;
        if ($totalValue <= 0) return null;

        DB::beginTransaction();
        try {
            $fromLocation = $transfer->fromLocation ?? $transfer->from_location ?? null;
            $toLocation = $transfer->toLocation ?? $transfer->to_location ?? null;
            
            $fromName = $fromLocation ? $fromLocation->name : 'Unknown';
            $toName = $toLocation ? $toLocation->name : 'Unknown';

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($transfer->transfer_date ?? now()),
                'entry_date' => $transfer->transfer_date ?? now(),
                'description' => "Stock Transfer #{$transfer->id} - {$fromName} → {$toName}",
                'reference_type' => 'STOCK_TRANSFER',
                'reference_id' => $transfer->id,
                'total_debit' => $totalValue,
                'total_credit' => $totalValue,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            // Dr Inventory (Destination)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => $totalValue,
                'credit_amount' => 0,
                'description' => "Transfer IN - {$toName}",
                'line_number' => 1,
            ]);

            // Cr Inventory (Source)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $totalValue,
                'description' => "Transfer OUT - {$fromName}",
                'line_number' => 2,
            ]);

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Stock Transfer #{$transfer->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record Consignment Reconciliation
     * Dr Cash/Bank / Cr Revenue
     * Dr COGS / Cr Inventory (if cost data available)
     */
    public function recordConsignmentReconciliation($consignment, $user = null)
    {
        $revenueAccount = ChartOfAccount::where('code', self::ACCOUNT_REVENUE)->first();
        $assetAccount = $this->getPaymentAccount($consignment->payment_method ?? 'cash');
        $cogsAccount = ChartOfAccount::where('code', self::ACCOUNT_COGS)->first();
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();

        if (!$assetAccount) {
            $assetAccount = ChartOfAccount::where('code', self::ACCOUNT_CASH)->first();
        }

        if (!$revenueAccount || !$assetAccount || !$cogsAccount || !$inventoryAccount) {
            Log::warning('AccountingService: Missing accounts for Consignment Reconciliation.');
            return null;
        }

        $totalAmount = $consignment->total_amount ?? 0;
        $totalCost = $consignment->total_cost ?? 0;

        if ($totalAmount <= 0) return null;

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($consignment->reconciled_at ?? now()),
                'entry_date' => $consignment->reconciled_at ?? now(),
                'description' => "Consignment Reconciliation #{$consignment->id}",
                'reference_type' => 'CONSIGNMENT',
                'reference_id' => $consignment->id,
                'total_debit' => $totalAmount + $totalCost,
                'total_credit' => $totalAmount + $totalCost,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
            ]);

            $lineNumber = 1;

            // Dr Cash/Bank
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'debit_amount' => $totalAmount,
                'credit_amount' => 0,
                'description' => 'Consignment Payment',
                'line_number' => $lineNumber++,
            ]);

            // Cr Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $totalAmount,
                'description' => 'Consignment Revenue',
                'line_number' => $lineNumber++,
            ]);

            // Only record COGS if we have cost data
            if ($totalCost > 0) {
                // Dr COGS
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cogsAccount->id,
                    'debit_amount' => $totalCost,
                    'credit_amount' => 0,
                    'description' => 'Cost of Consignment Goods',
                    'line_number' => $lineNumber++,
                ]);

                // Cr Inventory
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCost,
                    'description' => 'Inventory Reduction',
                    'line_number' => $lineNumber++,
                ]);
            }

            DB::commit();
            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to record Consignment Reconciliation #{$consignment->id}: " . $e->getMessage());
            return null;
        }
    }

}
