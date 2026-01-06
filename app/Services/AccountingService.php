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
    public function recordExpense(Expense $expense, $user = null)
    {
        if (!$expense->category_id || !$expense->payment_account_id) {
            return;
        }

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

        // Debit: Expense account
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expense->category_id,
            'debit_amount' => $expense->amount,
            'credit_amount' => 0,
            'description' => $expense->description,
            'line_number' => 1,
        ]);

        // Credit: Payment account (Cash/Bank)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expense->payment_account_id,
            'debit_amount' => 0,
            'credit_amount' => $expense->amount,
            'description' => "Payment to {$expense->payee}",
            'line_number' => 2,
        ]);
        
        return $entry;
    }

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
     * Record accounting entry for a POS Sale
     * Handles: Cost of Goods Sold (Dr COGS, Cr Inventory)
     *          Revenue (Dr Cash/Bank/AR, Cr Revenue, Cr VAT)
     */
    public function recordSale($sale, $user = null)
    {
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
    public function recordCapitalInvestment($amount, $accountId, $date, $description, $user = null, $shareholderId = null)
    {
        $capitalAccount = null;

        if ($shareholderId) {
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
        $expenseAccount = ChartOfAccount::where('code', self::ACCOUNT_COMMISSION_EXPENSE)->first();
        $payableAccount = ChartOfAccount::where('code', self::ACCOUNT_COMMISSIONS_PAYABLE)->first();

        // Auto-create accounts if missing
        if (!$expenseAccount) {
            $expenseAccount = ChartOfAccount::create([
                'code' => self::ACCOUNT_COMMISSION_EXPENSE,
                'name' => 'Commission Expense',
                'account_type' => 'Expense',
                'is_active' => true,
                'description' => 'Staff commissions'
            ]);
        }
        if (!$payableAccount) {
            $payableAccount = ChartOfAccount::create([
                'code' => self::ACCOUNT_COMMISSIONS_PAYABLE,
                'name' => 'Commissions Payable',
                'account_type' => 'Liability',
                'is_active' => true,
                'description' => 'Unpaid staff commissions'
            ]);
        }

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
        $method = strtolower(trim($paymentMethod));
        
        // Map payment methods to account codes
        $accountCode = match($method) {
            'cash', 'cash on delivery' => self::ACCOUNT_CASH,
            'm-pesa', 'mpesa' => self::ACCOUNT_MPESA,
            'cheque', 'check' => self::ACCOUNT_CHEQUE,
            'bank', 'bank transfer', 'transfer', 'card', 'credit card', 'debit card' => self::ACCOUNT_BANK,
            default => self::ACCOUNT_BANK, // Fallback to bank
        };
        
        $account = ChartOfAccount::where('code', $accountCode)->first();
        
        // Auto-create if missing (for M-Pesa and Cheque)
        if (!$account && in_array($accountCode, [self::ACCOUNT_MPESA, self::ACCOUNT_CHEQUE])) {
            $account = $this->createPaymentAccount($accountCode);
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
        ];
        
        if (!isset($accountDetails[$accountCode])) {
            return null;
        }
        
        return ChartOfAccount::create([
            'code' => $accountCode,
            'name' => $accountDetails[$accountCode]['name'],
            'account_type' => 'Asset',
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
            $capitalAccount = ChartOfAccount::where('code', self::ACCOUNT_CAPITAL)->first();
            $retainedEarningsAccount = ChartOfAccount::create([
                'code' => self::ACCOUNT_RETAINED_EARNINGS,
                'name' => 'Retained Earnings',
                'account_type' => 'Equity',
                'parent_id' => $capitalAccount?->id,
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
}
