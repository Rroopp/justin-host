<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\PosSale;
use Exception;

/**
 * PostingEngine - The Translator of Business Events
 * 
 * Converts business transactions (Invoices, POS Sales, Bills, etc.) 
 * into proper double-entry journal entries.
 */
class PostingEngine
{
    protected JournalEntryService $journalService;
    
    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }
    
    /**
     * Post a POS Sale
     * 
     * Debits: Cash/Bank, Accounts Receivable (if credit sale)
     * Credits: Revenue, VAT Payable, Inventory (COGS)
     */
    public function postPosSale(PosSale $sale)
    {
        $lines = [];
        
        // Get system accounts
        $cashAccount = $this->getSystemAccount('CASH');
        $arAccount = $this->getSystemAccount('ACCOUNTS_RECEIVABLE');
        $revenueAccount = $this->getSystemAccount('SALES_REVENUE');
        $vatPayableAccount = $this->getSystemAccount('VAT_PAYABLE');
        $inventoryAccount = $this->getSystemAccount('INVENTORY');
        $cogsAccount = $this->getSystemAccount('COST_OF_GOODS_SOLD');
        
        // 1. Revenue Recognition
        $subtotal = $sale->subtotal ?? 0;
        $vatAmount = $sale->tax_amount ?? 0;
        $total = $sale->total_amount;
        
        // Debit: Cash or AR (depending on payment method)
        if ($sale->payment_method === 'CREDIT') {
            $lines[] = [
                'account_id' => $arAccount->id,
                'debit' => $total,
                'credit' => 0,
                'description' => "AR for Sale #{$sale->id}",
            ];
        } else {
            $lines[] = [
                'account_id' => $cashAccount->id,
                'debit' => $total,
                'credit' => 0,
                'description' => "Cash from Sale #{$sale->id}",
            ];
        }
        
        // Credit: Revenue
        $lines[] = [
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => $subtotal,
            'description' => "Revenue from Sale #{$sale->id}",
        ];
        
        // Credit: VAT Payable (if applicable)
        if ($vatAmount > 0) {
            $lines[] = [
                'account_id' => $vatPayableAccount->id,
                'debit' => 0,
                'credit' => $vatAmount,
                'description' => "VAT on Sale #{$sale->id}",
            ];
        }
        
        // 2. Inventory & COGS (if items sold)
        // This would require iterating through sale items and calculating COGS
        // For now, placeholder logic
        $cogsAmount = $this->calculateCOGS($sale);
        
        if ($cogsAmount > 0) {
            // Debit: COGS
            $lines[] = [
                'account_id' => $cogsAccount->id,
                'debit' => $cogsAmount,
                'credit' => 0,
                'description' => "COGS for Sale #{$sale->id}",
            ];
            
            // Credit: Inventory
            $lines[] = [
                'account_id' => $inventoryAccount->id,
                'debit' => 0,
                'credit' => $cogsAmount,
                'description' => "Inventory reduction for Sale #{$sale->id}",
            ];
        }
        
        // Create the journal entry
        return $this->journalService->createEntry([
            'entry_date' => $sale->sale_date,
            'source' => 'POS',
            'source_id' => $sale->id,
            'description' => "POS Sale #{$sale->id} - {$sale->customer_name}",
            'lines' => $lines,
        ]);
    }
    
    /**
     * Post a Bill/Expense
     * 
     * Debits: Expense Account, VAT Receivable
     * Credits: Accounts Payable
     */
    public function postBill(array $billData)
    {
        $lines = [];
        
        $apAccount = $this->getSystemAccount('ACCOUNTS_PAYABLE');
        $vatReceivableAccount = $this->getSystemAccount('VAT_RECEIVABLE');
        $expenseAccount = ChartOfAccount::find($billData['expense_account_id']);
        
        $amount = $billData['amount'];
        $vatAmount = $billData['vat_amount'] ?? 0;
        $total = $amount + $vatAmount;
        
        // Debit: Expense
        $lines[] = [
            'account_id' => $expenseAccount->id,
            'debit' => $amount,
            'credit' => 0,
            'description' => $billData['description'],
        ];
        
        // Debit: VAT Receivable (if applicable)
        if ($vatAmount > 0) {
            $lines[] = [
                'account_id' => $vatReceivableAccount->id,
                'debit' => $vatAmount,
                'credit' => 0,
                'description' => "VAT on " . $billData['description'],
            ];
        }
        
        // Credit: Accounts Payable
        $lines[] = [
            'account_id' => $apAccount->id,
            'debit' => 0,
            'credit' => $total,
            'description' => "Payable: " . $billData['description'],
        ];
        
        return $this->journalService->createEntry([
            'entry_date' => $billData['date'],
            'source' => 'BILL',
            'source_id' => $billData['id'] ?? null,
            'description' => $billData['description'],
            'lines' => $lines,
        ]);
    }
    
    /**
     * Post a Payment (clears AR or AP)
     */
    public function postPayment(array $paymentData)
    {
        $lines = [];
        
        $cashAccount = $this->getSystemAccount('CASH');
        
        if ($paymentData['type'] === 'RECEIVED') {
            // Payment received (clears AR)
            $arAccount = $this->getSystemAccount('ACCOUNTS_RECEIVABLE');
            
            // Debit: Cash
            $lines[] = [
                'account_id' => $cashAccount->id,
                'debit' => $paymentData['amount'],
                'credit' => 0,
                'description' => "Payment received",
            ];
            
            // Credit: AR
            $lines[] = [
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $paymentData['amount'],
                'description' => "AR cleared",
            ];
            
        } else {
            // Payment made (clears AP)
            $apAccount = $this->getSystemAccount('ACCOUNTS_PAYABLE');
            
            // Debit: AP
            $lines[] = [
                'account_id' => $apAccount->id,
                'debit' => $paymentData['amount'],
                'credit' => 0,
                'description' => "AP cleared",
            ];
            
            // Credit: Cash
            $lines[] = [
                'account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $paymentData['amount'],
                'description' => "Payment made",
            ];
        }
        
        return $this->journalService->createEntry([
            'entry_date' => $paymentData['date'],
            'source' => 'PAYMENT',
            'source_id' => $paymentData['id'] ?? null,
            'description' => $paymentData['description'] ?? 'Payment',
            'lines' => $lines,
        ]);
    }
    
    /**
     * Get a system account by code
     */
    private function getSystemAccount(string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)
            ->where('is_system', true)
            ->first();
            
        if (!$account) {
            throw new Exception("System account '{$code}' not found. Please run Chart of Accounts seeder.");
        }
        
        return $account;
    }
    
    /**
     * Calculate COGS for a sale based on captured cost price
     */
    private function calculateCOGS(PosSale $sale): float
    {
        $totalCOGS = 0;
        
        // Use sale_items attribute which is cast to array
        $saleItems = $sale->sale_items ?? [];
        
        foreach ($saleItems as $item) {
            // Skip services/fees (type='service' or no inventory_id?)
            // Usually valid inventory items have product_id
            
            $costPrice = $item['cost_price'] ?? 0;
            $quantity = $item['quantity'] ?? 0;
            
            // Basic validation
            if ($quantity > 0 && $costPrice > 0) {
                $totalCOGS += ($costPrice * $quantity);
            }
        }
        
        return round($totalCOGS, 2);
    }
}
