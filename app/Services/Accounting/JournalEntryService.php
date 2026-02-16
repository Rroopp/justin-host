<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * JournalEntryService - The Gatekeeper of the General Ledger
 * 
 * Enforces double-entry accounting rules and period controls.
 * NO journal entry may bypass this service.
 */
class JournalEntryService
{
    /**
     * Create a new journal entry (in DRAFT status)
     * 
     * @param array $data
     * @return JournalEntry
     * @throws Exception
     */
    public function createEntry(array $data): JournalEntry
    {
        // Validate required fields
        $this->validateEntryData($data);
        
        // Validate lines balance
        $this->validateBalance($data['lines']);
        
        // Assign period based on date
        $period = $this->assignPeriod($data['entry_date']);
        
        // Check if period allows posting
        if ($period && in_array($period->status, ['CLOSED', 'LOCKED'])) {
            throw new Exception("Cannot create entries in a {$period->status} period.");
        }
        
        DB::beginTransaction();
        try {
            // Generate entry number
            $entryNumber = JournalEntry::generateEntryNumber($data['entry_date']);
            
            // Calculate totals
            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');
            
            // Create journal entry header
            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'],
                'source' => $data['source'] ?? 'MANUAL',
                'source_id' => $data['source_id'] ?? null,
                'description' => $data['description'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'DRAFT',
                'created_by' => Auth::user()->username ?? 'system',
                'period_id' => $period?->id,
                'is_locked' => false,
            ]);
            
            // Create journal entry lines
            foreach ($data['lines'] as $index => $line) {
                $this->createLine($entry, $line, $index + 1);
            }
            
            DB::commit();
            return $entry->fresh('lines');
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to create journal entry: " . $e->getMessage());
        }
    }
    
    /**
     * Post a journal entry (change status from DRAFT to POSTED)
     * 
     * @param JournalEntry $entry
     * @return JournalEntry
     * @throws Exception
     */
    public function postEntry(JournalEntry $entry): JournalEntry
    {
        if ($entry->status === 'POSTED') {
            throw new Exception("Entry is already posted.");
        }
        
        if ($entry->is_locked) {
            throw new Exception("Cannot post a locked entry.");
        }
        
        // Verify balance one more time
        if (!$entry->isBalanced()) {
            throw new Exception("Cannot post unbalanced entry.");
        }
        
        // Check period status
        if ($entry->period && in_array($entry->period->status, ['CLOSED', 'LOCKED'])) {
            throw new Exception("Cannot post entries in a {$entry->period->status} period.");
        }
        
        $entry->update(['status' => 'POSTED']);
        
        return $entry;
    }
    
    /**
     * Reverse a posted journal entry
     * 
     * @param JournalEntry $entry
     * @param string $reason
     * @return JournalEntry The reversal entry
     * @throws Exception
     */
    public function reverseEntry(JournalEntry $entry, string $reason): JournalEntry
    {
        if ($entry->status !== 'POSTED') {
            throw new Exception("Only POSTED entries can be reversed.");
        }
        
        if ($entry->is_locked) {
            throw new Exception("Cannot reverse a locked entry.");
        }
        
        if ($entry->reversed_entry_id) {
            throw new Exception("This entry has already been reversed.");
        }
        
        DB::beginTransaction();
        try {
            // Create reversal lines (swap debit/credit)
            $reversalLines = [];
            foreach ($entry->lines as $line) {
                $reversalLines[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit_amount, // Swap
                    'credit' => $line->debit_amount, // Swap
                    'description' => "Reversal: " . $line->description,
                    'currency' => $line->currency ?? 'KES',
                    'exchange_rate' => $line->exchange_rate ?? 1,
                ];
            }
            
            // Create the reversal entry
            $reversalEntry = $this->createEntry([
                'entry_date' => now()->toDateString(),
                'source' => 'ADJUSTMENT',
                'source_id' => $entry->id,
                'description' => "REVERSAL: {$reason} (Original: {$entry->entry_number})",
                'lines' => $reversalLines,
            ]);
            
            // Post it immediately
            $this->postEntry($reversalEntry);
            
            // Link the original to the reversal
            $entry->update(['reversed_entry_id' => $reversalEntry->id]);
            
            DB::commit();
            return $reversalEntry;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to reverse entry: " . $e->getMessage());
        }
    }
    
    /**
     * Validate entry data structure
     */
    private function validateEntryData(array $data): void
    {
        if (empty($data['entry_date'])) {
            throw new Exception("Entry date is required.");
        }
        
        if (empty($data['description'])) {
            throw new Exception("Description is required.");
        }
        
        if (empty($data['lines']) || !is_array($data['lines'])) {
            throw new Exception("Journal entry must have at least one line.");
        }
        
        if (count($data['lines']) < 2) {
            throw new Exception("Journal entry must have at least two lines (double-entry).");
        }
    }
    
    /**
     * Validate that debits equal credits
     */
    private function validateBalance(array $lines): void
    {
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($lines as $line) {
            $debit = $line['debit'] ?? 0;
            $credit = $line['credit'] ?? 0;
            
            // A line must have either debit OR credit, not both
            if ($debit > 0 && $credit > 0) {
                throw new Exception("A journal line cannot have both debit and credit.");
            }
            
            if ($debit == 0 && $credit == 0) {
                throw new Exception("A journal line must have either debit or credit.");
            }
            
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
        
        // Allow small rounding difference (0.01)
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception("Journal entry is not balanced. Debits: {$totalDebit}, Credits: {$totalCredit}");
        }
    }
    
    /**
     * Assign accounting period based on entry date
     */
    private function assignPeriod(string $date): ?AccountingPeriod
    {
        return AccountingPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
    
    /**
     * Create a journal entry line
     */
    private function createLine(JournalEntry $entry, array $lineData, int $lineNumber): JournalEntryLine
    {
        $debit = $lineData['debit'] ?? 0;
        $credit = $lineData['credit'] ?? 0;
        $currency = $lineData['currency'] ?? 'KES';
        $exchangeRate = $lineData['exchange_rate'] ?? 1;
        
        return JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $lineData['account_id'],
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'description' => $lineData['description'] ?? $entry->description,
            'line_number' => $lineNumber,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'debit_base' => $debit * $exchangeRate,
            'credit_base' => $credit * $exchangeRate,
        ]);
    }
}
