<?php

namespace App\Services;

use App\Models\ConsignmentTransaction;
use App\Models\PosSale;
use App\Models\PosSalePayment;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Batch;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ConsignmentBillingService
 * 
 * Handles billing for USED consignment items only.
 * Unused items (returned) are NOT billed - they return to inventory.
 * 
 * Flow:
 * 1. Items placed at consignment location (not billed)
 * 2. Items used in surgery (tracked as 'used' transactions)
 * 3. Used items are billed to customer/hospital
 * 4. Unused items returned to inventory (no billing)
 * 
 * Feature Flag: 'auto_bill_consignment' (default: false for safe rollout)
 */
class ConsignmentBillingService
{
    /**
     * Check if auto-billing is enabled via feature flag
     */
    public static function isEnabled(): bool
    {
        return settings('auto_bill_consignment', false) === '1' 
            || settings('auto_bill_consignment', false) === true;
    }
    
    /**
     * Create a POS sale from unbilled consignment transactions
     * 
     * This generates an invoice for USED items only.
     * 
     * @param array $transactionIds Array of consignment transaction IDs to bill
     * @param Customer|null $customer Customer to bill (optional, can be derived from location)
     * @param array $saleData Additional sale data (patient_name, surgeon_name, etc.)
     * @param mixed $user User creating the sale
     * @return PosSale|null The created POS sale or null if no billable items
     */
    public function createSaleFromConsignment(
        array $transactionIds,
        ?Customer $customer = null,
        array $saleData = [],
        $user = null
    ): ?PosSale {
        // Feature flag check
        if (!self::isEnabled()) {
            Log::info('ConsignmentBillingService: Auto-billing disabled via feature flag');
            return null;
        }
        
        // Get unbilled 'used' transactions only (not placed, not returned)
        $transactions = ConsignmentTransaction::whereIn('id', $transactionIds)
            ->where('transaction_type', 'used')  // ONLY bill used items
            ->where('billed', false)
            ->with(['inventory', 'batch', 'location'])
            ->get();
            
        if ($transactions->isEmpty()) {
            Log::warning('ConsignmentBillingService: No unbilled used transactions found', [
                'transaction_ids' => $transactionIds
            ]);
            return null;
        }
        
        return DB::transaction(function () use ($transactions, $customer, $saleData, $user) {
            // Build sale items from transactions
            $saleItems = [];
            $subtotal = 0;
            $totalCost = 0;
            
            foreach ($transactions as $transaction) {
                $inventory = $transaction->inventory;
                $batch = $transaction->batch;
                
                if (!$inventory) {
                    Log::warning('ConsignmentBillingService: Inventory not found for transaction', [
                        'transaction_id' => $transaction->id
                    ]);
                    continue;
                }
                
                // Use batch selling price or inventory selling price
                $unitPrice = $batch?->selling_price ?? $inventory->selling_price ?? 0;
                $costPrice = $batch?->cost_price ?? $inventory->price ?? 0;
                $quantity = $transaction->quantity;
                $itemTotal = $unitPrice * $quantity;
                $itemCost = $costPrice * $quantity;
                
                $saleItems[] = [
                    'product_id' => $inventory->id,
                    'product_name' => $inventory->product_name,
                    'product_code' => $inventory->code,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                    'batch_number' => $batch?->batch_number,
                    'batch_id' => $batch?->id,
                    'location_id' => $transaction->location_id,
                    'from_consignment' => true,
                    'consignment_transaction_id' => $transaction->id,
                ];
                
                $subtotal += $itemTotal;
                $totalCost += $itemCost;
            }
            
            if (empty($saleItems)) {
                Log::warning('ConsignmentBillingService: No valid sale items created');
                return null;
            }
            
            // Calculate totals
            $vatRate = settings('default_tax_rate', 16);
            $vat = $subtotal * ($vatRate / 100);
            $total = $subtotal + $vat;
            
            // Determine customer
            $customerId = $customer?->id;
            $customerName = $customer?->name ?? $saleData['customer_name'] ?? 'Consignment Customer';
            $customerPhone = $customer?->phone ?? $saleData['customer_phone'] ?? null;
            
            // If no customer provided, try to get from location's linked customer
            if (!$customerId && $transactions->first()->location) {
                $locationCustomer = $this->getLocationCustomer($transactions->first()->location);
                if ($locationCustomer) {
                    $customerId = $locationCustomer->id;
                    $customerName = $locationCustomer->name;
                    $customerPhone = $locationCustomer->phone;
                }
            }
            
            // Generate invoice number
            $invoicePrefix = settings('invoice_prefix', 'INV-');
            $invoiceNumber = $this->generateInvoiceNumber($invoicePrefix);
            
            // Create the POS sale
            $posSale = PosSale::create([
                'sale_type' => 'consignment_bill',
                'sale_items' => $saleItems,
                'payment_method' => $saleData['payment_method'] ?? 'credit', // Consignment bills are typically credit initially
                'payment_status' => 'pending',
                'sale_status' => 'completed', // Completed but payment pending
                'subtotal' => $subtotal,
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'vat' => $vat,
                'total' => $total,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_snapshot' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ] : null,
                'seller_username' => $user ? $user->username : 'system',
                'timestamp' => now(),
                'invoice_number' => $invoiceNumber,
                'document_type' => 'invoice',
                'patient_name' => $saleData['patient_name'] ?? null,
                'patient_number' => $saleData['patient_number'] ?? null,
                'surgeon_name' => $saleData['surgeon_name'] ?? null,
                'facility_name' => $saleData['facility_name'] ?? $transactions->first()->location?->name,
                'is_reconciled' => true, // Consignment bills are reconciled at creation
                'reconciled_at' => now(),
            ]);
            
            // Mark transactions as billed
            $billingReference = $invoiceNumber;
            foreach ($transactions as $transaction) {
                $transaction->markAsBilled($billingReference);
            }
            
            // Post to accounting
            try {
                $accountingService = new AccountingService();
                $accountingService->recordSale($posSale, $user);
                
                Log::info('ConsignmentBillingService: Sale created and accounting posted', [
                    'pos_sale_id' => $posSale->id,
                    'invoice_number' => $invoiceNumber,
                    'transaction_count' => $transactions->count(),
                    'total' => $total
                ]);
            } catch (\Exception $e) {
                Log::error('ConsignmentBillingService: Accounting post failed', [
                    'pos_sale_id' => $posSale->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail - accounting can be reconciled later
            }
            
            return $posSale;
        });
    }
    
    /**
     * Auto-bill all unbilled used transactions for a location
     * 
     * @param Location $location Consignment location
     * @param array $saleData Sale metadata
     * @param mixed $user User creating the sale
     * @return PosSale|null
     */
    public function autoBillLocation(Location $location, array $saleData = [], $user = null): ?PosSale
    {
        if (!$location->isConsignment()) {
            Log::warning('ConsignmentBillingService: Location is not a consignment location', [
                'location_id' => $location->id
            ]);
            return null;
        }
        
        // Get all unbilled USED transactions for this location
        $transactions = ConsignmentTransaction::unbilled()
            ->where('location_id', $location->id)
            ->get();
            
        if ($transactions->isEmpty()) {
            Log::info('ConsignmentBillingService: No unbilled transactions for location', [
                'location_id' => $location->id
            ]);
            return null;
        }
        
        $transactionIds = $transactions->pluck('id')->toArray();
        
        // Get customer from location if available
        $customer = $this->getLocationCustomer($location);
        
        return $this->createSaleFromConsignment($transactionIds, $customer, $saleData, $user);
    }
    
    /**
     * Get billing summary for a location
     * 
     * @param Location $location
     * @return array
     */
    public function getLocationBillingSummary(Location $location): array
    {
        if (!$location->isConsignment()) {
            return [
                'error' => 'Not a consignment location'
            ];
        }
        
        // Only count USED items (not placed, not returned)
        $unbilled = ConsignmentTransaction::unbilled()
            ->where('location_id', $location->id)
            ->with(['inventory', 'batch'])
            ->get();
            
        $billed = ConsignmentTransaction::billed()
            ->where('location_id', $location->id)
            ->where('transaction_type', 'used')
            ->with(['inventory', 'batch'])
            ->get();
            
        return [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'unbilled_count' => $unbilled->count(),
            'unbilled_value' => $unbilled->sum('value'),
            'billed_count' => $billed->count(),
            'billed_value' => $billed->sum('value'),
            'unbilled_transactions' => $unbilled,
            'billed_transactions' => $billed,
        ];
    }
    
    /**
     * Get customer linked to a consignment location
     */
    protected function getLocationCustomer(Location $location): ?Customer
    {
        // Check if location has a linked customer
        if ($location->customer_id) {
            return Customer::find($location->customer_id);
        }
        
        // Check if location name matches a customer name
        $customer = Customer::where('name', 'like', '%' . $location->name . '%')
            ->orWhere('facility_name', 'like', '%' . $location->name . '%')
            ->first();
            
        return $customer;
    }
    
    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}CON-{$date}-{$random}";
    }
    
    /**
     * Reverse billing for a consignment sale (for corrections)
     * 
     * @param PosSale $sale The consignment sale to reverse
     * @param string $reason Reason for reversal
     * @param mixed $user User performing the reversal
     * @return bool
     */
    public function reverseBilling(PosSale $sale, string $reason = 'Correction', $user = null): bool
    {
        if ($sale->sale_type !== 'consignment_bill') {
            Log::warning('ConsignmentBillingService: Cannot reverse non-consignment sale', [
                'pos_sale_id' => $sale->id
            ]);
            return false;
        }
        
        return DB::transaction(function () use ($sale, $reason, $user) {
            // Find related consignment transactions by invoice number
            $transactions = ConsignmentTransaction::where('billing_reference', $sale->invoice_number)
                ->where('billed', true)
                ->get();
                
            // Unmark transactions as billed
            foreach ($transactions as $transaction) {
                $transaction->update([
                    'billed' => false,
                    'billed_date' => null,
                    'billing_reference' => null,
                ]);
            }
            
            // Mark sale as cancelled/reversed
            $sale->update([
                'sale_status' => 'cancelled',
                'notes' => ($sale->notes ?? '') . "\nReversed: {$reason} by " . ($user ? $user->username : 'system'),
            ]);
            
            // Reverse accounting entry
            try {
                $accountingService = new AccountingService();
                // The AccountingService would need a reverse method
                // For now, we rely on the sale status change
                
                Log::info('ConsignmentBillingService: Billing reversed', [
                    'pos_sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'transactions_unbilled' => $transactions->count()
                ]);
            } catch (\Exception $e) {
                Log::error('ConsignmentBillingService: Accounting reversal failed', [
                    'pos_sale_id' => $sale->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return true;
        });
    }
    
    /**
     * Get aging report for consignment stock
     * 
     * @param int $days Number of days to consider aging
     * @return array
     */
    public function getAgingReport(int $days = 90): array
    {
        $agingDate = now()->subDays($days);
        
        // Get placed items that haven't been used or returned
        $agingPlaced = ConsignmentTransaction::where('transaction_type', 'placed')
            ->where('transaction_date', '<', $agingDate)
            ->whereNotIn('id', function ($query) {
                $query->select('id')
                    ->from('consignment_transactions')
                    ->whereIn('transaction_type', ['used', 'returned']);
            })
            ->with(['location', 'inventory'])
            ->get();
            
        // Get used but unbilled items (also aging concern)
        $agingUnbilled = ConsignmentTransaction::unbilled()
            ->where('transaction_date', '<', $agingDate)
            ->with(['location', 'inventory'])
            ->get();
            
        return [
            'aging_days' => $days,
            'aging_date' => $agingDate->format('Y-m-d'),
            'placed_not_used_count' => $agingPlaced->count(),
            'placed_not_used_value' => $agingPlaced->sum(function ($t) {
                $price = $t->batch?->selling_price ?? $t->inventory?->selling_price ?? 0;
                return $price * $t->quantity;
            }),
            'used_unbilled_count' => $agingUnbilled->count(),
            'used_unbilled_value' => $agingUnbilled->sum('value'),
            'placed_items' => $agingPlaced,
            'unbilled_items' => $agingUnbilled,
        ];
    }
}
