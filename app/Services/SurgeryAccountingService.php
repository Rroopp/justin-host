<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SurgeryUsage;
use App\Models\SurgeryUsageItem;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SurgeryAccountingService
 * 
 * Handles automatic COGS posting for surgical implant usage.
 * This ensures every implant used in surgery is properly expensed
 * through the accounting system.
 * 
 * Feature Flag: 'auto_post_surgery_cogs' (default: false for safe rollout)
 */
class SurgeryAccountingService
{
    // Default Account Codes (aligned with AccountingService)
    const ACCOUNT_INVENTORY = '1200';
    const ACCOUNT_COGS = '5000'; // Cost of Goods Sold
    
    /**
     * Check if auto-posting is enabled via feature flag
     */
    public static function isEnabled(): bool
    {
        return settings('auto_post_surgery_cogs', false) === '1' 
            || settings('auto_post_surgery_cogs', false) === true;
    }
    
    /**
     * Record COGS for surgery usage
     * 
     * This method posts journal entries for all items used in surgery:
     * - Dr COGS (Expense increase)
     * - Cr Inventory (Asset decrease)
     * 
     * @param SurgeryUsage $usage The surgery usage record
     * @param array|null $specificItemIds Optional: only post for specific items
     * @param mixed $user Optional: user making the entry
     * @return JournalEntry|null The created journal entry or null if skipped
     */
    public function recordSurgeryCogs(SurgeryUsage $usage, ?array $specificItemIds = null, $user = null): ?JournalEntry
    {
        // Feature flag check for safe rollout
        if (!self::isEnabled()) {
            Log::info('SurgeryAccountingService: Auto-posting disabled via feature flag', [
                'surgery_usage_id' => $usage->id
            ]);
            return null;
        }
        
        // Load usage with items
        $usage->load(['items.inventory', 'items.batch']);
        
        // Filter items if specific IDs provided
        $items = $usage->items;
        if ($specificItemIds !== null) {
            $items = $items->whereIn('id', $specificItemIds);
        }
        
        if ($items->isEmpty()) {
            Log::warning('SurgeryAccountingService: No items to process', [
                'surgery_usage_id' => $usage->id
            ]);
            return null;
        }
        
        // Get required accounts
        $accounts = $this->getRequiredAccounts();
        if (!$accounts) {
            return null;
        }
        
        // Calculate total COGS value
        $cogsData = $this->calculateCogsForItems($items);
        
        if ($cogsData['total_value'] <= 0) {
            Log::warning('SurgeryAccountingService: Zero or negative COGS value', [
                'surgery_usage_id' => $usage->id,
                'total_value' => $cogsData['total_value']
            ]);
            return null;
        }
        
        // Check if entry already exists (prevent duplicates)
        $existingEntry = JournalEntry::where('reference_type', 'SURGERY_USAGE')
            ->where('reference_id', $usage->id)
            ->where('status', 'POSTED')
            ->first();
            
        if ($existingEntry) {
            Log::info('SurgeryAccountingService: Entry already exists', [
                'surgery_usage_id' => $usage->id,
                'journal_entry_id' => $existingEntry->id
            ]);
            return $existingEntry;
        }
        
        return DB::transaction(function () use ($usage, $cogsData, $accounts, $user) {
            // Create journal entry header
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber($usage->surgery_date),
                'entry_date' => $usage->surgery_date,
                'source' => 'SURGERY_USAGE',
                'source_id' => $usage->id,
                'description' => $this->buildDescription($usage, $cogsData),
                'total_debit' => $cogsData['total_value'],
                'total_credit' => $cogsData['total_value'],
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
                'period_id' => null, // Will be set by period closing logic if needed
            ]);
            
            // Debit COGS (Expense increase)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accounts['cogs']->id,
                'debit_amount' => $cogsData['total_value'],
                'credit_amount' => 0,
                'description' => "COGS: Surgery implants used ({$cogsData['item_count']} items)",
                'line_number' => 1,
            ]);
            
            // Credit Inventory (Asset decrease)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accounts['inventory']->id,
                'debit_amount' => 0,
                'credit_amount' => $cogsData['total_value'],
                'description' => "Inventory reduction: Surgery #{$usage->id}",
                'line_number' => 2,
            ]);
            
            // Log detailed line items for audit trail
            $this->logCogsDetails($entry->id, $cogsData['items']);
            
            Log::info('SurgeryAccountingService: COGS posted successfully', [
                'surgery_usage_id' => $usage->id,
                'journal_entry_id' => $entry->id,
                'total_value' => $cogsData['total_value'],
                'item_count' => $cogsData['item_count']
            ]);
            
            return $entry;
        });
    }
    
    /**
     * Reverse a surgery COGS entry (for corrections/returns)
     * 
     * @param SurgeryUsage $usage The original surgery usage
     * @param string $reason Reason for reversal
     * @return JournalEntry|null The reversal entry
     */
    public function reverseSurgeryCogs(SurgeryUsage $usage, string $reason = 'Correction', $user = null): ?JournalEntry
    {
        // Find original entry
        $originalEntry = JournalEntry::where('reference_type', 'SURGERY_USAGE')
            ->where('reference_id', $usage->id)
            ->where('status', 'POSTED')
            ->first();
            
        if (!$originalEntry) {
            Log::warning('SurgeryAccountingService: No entry to reverse', [
                'surgery_usage_id' => $usage->id
            ]);
            return null;
        }
        
        // Check if already reversed
        $existingReversal = JournalEntry::where('reference_type', 'SURGERY_USAGE_REVERSAL')
            ->where('reference_id', $usage->id)
            ->where('status', 'POSTED')
            ->first();
            
        if ($existingReversal) {
            Log::info('SurgeryAccountingService: Reversal already exists', [
                'surgery_usage_id' => $usage->id,
                'reversal_entry_id' => $existingReversal->id
            ]);
            return $existingReversal;
        }
        
        return DB::transaction(function () use ($usage, $originalEntry, $reason, $user) {
            // Create reversal entry (swap debits and credits)
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => now(),
                'source' => 'SURGERY_USAGE_REVERSAL',
                'source_id' => $usage->id,
                'description' => "REVERSAL: {$originalEntry->description} - Reason: {$reason}",
                'total_debit' => $originalEntry->total_credit,
                'total_credit' => $originalEntry->total_debit,
                'status' => 'POSTED',
                'created_by' => $user ? $user->username : 'system',
                'reversed_entry_id' => $originalEntry->id,
            ]);
            
            // Get original lines and reverse them
            $originalLines = $originalEntry->lines;
            $lineNumber = 1;
            
            foreach ($originalLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line->account_id,
                    'debit_amount' => $line->credit_amount, // Swap
                    'credit_amount' => $line->debit_amount, // Swap
                    'description' => "Reversal: {$line->description}",
                    'line_number' => $lineNumber++,
                ]);
            }
            
            Log::info('SurgeryAccountingService: COGS reversed successfully', [
                'surgery_usage_id' => $usage->id,
                'original_entry_id' => $originalEntry->id,
                'reversal_entry_id' => $entry->id
            ]);
            
            return $entry;
        });
    }
    
    /**
     * Calculate COGS for a collection of surgery usage items
     */
    protected function calculateCogsForItems($items): array
    {
        $totalValue = 0;
        $itemDetails = [];
        
        foreach ($items as $item) {
            // Get cost price from batch or inventory
            $costPrice = $this->getItemCostPrice($item);
            $itemValue = $costPrice * $item->quantity;
            
            $totalValue += $itemValue;
            
            $itemDetails[] = [
                'surgery_usage_item_id' => $item->id,
                'inventory_id' => $item->inventory_id,
                'batch_id' => $item->batch_id,
                'product_name' => $item->inventory?->product_name ?? 'Unknown',
                'quantity' => $item->quantity,
                'unit_cost' => $costPrice,
                'total_cost' => $itemValue,
            ];
        }
        
        return [
            'total_value' => $totalValue,
            'item_count' => count($itemDetails),
            'items' => $itemDetails,
        ];
    }
    
    /**
     * Get cost price for a surgery usage item
     */
    protected function getItemCostPrice(SurgeryUsageItem $item): float
    {
        // Priority: batch cost_price > inventory moving_average_cost > inventory price
        if ($item->batch && $item->batch->cost_price > 0) {
            return (float) $item->batch->cost_price;
        }
        
        if ($item->inventory) {
            if ($item->inventory->moving_average_cost > 0) {
                return (float) $item->inventory->moving_average_cost;
            }
            
            if ($item->inventory->price > 0) {
                return (float) $item->inventory->price;
            }
        }
        
        Log::warning('SurgeryAccountingService: No cost price found, using zero', [
            'surgery_usage_item_id' => $item->id,
            'inventory_id' => $item->inventory_id,
            'batch_id' => $item->batch_id,
        ]);
        
        return 0;
    }
    
    /**
     * Get required chart of accounts
     */
    protected function getRequiredAccounts(): ?array
    {
        $inventoryAccount = ChartOfAccount::where('code', self::ACCOUNT_INVENTORY)->first();
        $cogsAccount = ChartOfAccount::where('code', self::ACCOUNT_COGS)->first();
        
        if (!$inventoryAccount || !$cogsAccount) {
            Log::error('SurgeryAccountingService: Missing required accounts', [
                'inventory_account' => self::ACCOUNT_INVENTORY,
                'cogs_account' => self::ACCOUNT_COGS,
                'inventory_found' => (bool) $inventoryAccount,
                'cogs_found' => (bool) $cogsAccount,
            ]);
            return null;
        }
        
        return [
            'inventory' => $inventoryAccount,
            'cogs' => $cogsAccount,
        ];
    }
    
    /**
     * Build journal entry description
     */
    protected function buildDescription(SurgeryUsage $usage, array $cogsData): string
    {
        $patientInfo = $usage->patient_name ? "Patient: {$usage->patient_name}" : "Patient: Unknown";
        $surgeonInfo = $usage->surgeon_name ? "Surgeon: {$usage->surgeon_name}" : "";
        
        $description = "Surgery COGS - {$patientInfo}";
        
        if ($surgeonInfo) {
            $description .= " ({$surgeonInfo})";
        }
        
        $description .= " - {$cogsData['item_count']} items, " . 
                       format_currency($cogsData['total_value']);
        
        return $description;
    }
    
    /**
     * Log COGS details for audit trail
     */
    protected function logCogsDetails(int $journalEntryId, array $items): void
    {
        // Store detailed breakdown as JSON in a way that can be retrieved
        // This could be extended to store in a separate table if needed
        Log::info('SurgeryAccountingService: COGS details', [
            'journal_entry_id' => $journalEntryId,
            'items' => $items,
        ]);
    }
    
    /**
     * Get COGS summary for a date range
     * 
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array
     */
    public function getCogsSummary(string $startDate, string $endDate): array
    {
        $entries = JournalEntry::where('reference_type', 'SURGERY_USAGE')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->where('status', 'POSTED')
            ->get();
            
        return [
            'total_cogs' => $entries->sum('total_debit'),
            'entry_count' => $entries->count(),
            'average_per_surgery' => $entries->count() > 0 
                ? $entries->sum('total_debit') / $entries->count() 
                : 0,
            'entries' => $entries,
        ];
    }
}
