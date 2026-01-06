<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Support\Carbon;
class JournalEntry extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
    ];
    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'reference_id' => 'integer',
    ];

    /**
     * Get journal entry lines
     */
    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    /**
     * Generate entry number
     */
    public static function generateEntryNumber($date = null): string
    {
        $date = $date ?: now();
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        $dateStr = $date->format('Y-m-d');
        
        // Get the count of entries for this date
        $count = self::whereDate('entry_date', $dateStr)->count() + 1;
        // Generate base entry number
        $entryNumber = "JE-{$dateStr}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        // Check if it already exists (race condition protection)
        $attempts = 0;
        while (self::where('entry_number', $entryNumber)->exists() && $attempts < 10) {
            $count++;
            $entryNumber = "JE-{$dateStr}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        // If still duplicate after 10 attempts, add microseconds
        if (self::where('entry_number', $entryNumber)->exists()) {
            $entryNumber = "JE-{$dateStr}-" . str_pad($count, 4, '0', STR_PAD_LEFT) . '-' . substr((string)(microtime(true) * 10000), -4);
        }
        return $entryNumber;
    }

    /**
     * Check if entry is balanced
     */
    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }
}