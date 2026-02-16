<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class JournalEntryLine extends Model
{
    use HasFactory, Auditable;
    protected $table = 'journal_entry_lines';
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'description',
        'line_number',
        'currency',
        'exchange_rate',
        'debit_base',
        'credit_base',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'debit_base' => 'decimal:2',
        'credit_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'line_number' => 'integer',
    ];

    /**
     * Get the journal entry for this line
     */


    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
     /**
     * Get the account for this line
     */
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}