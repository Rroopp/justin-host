<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class ChartOfAccount extends Model
{
    use HasFactory, Auditable;
    protected $table = 'chart_of_accounts';
    protected $fillable = [
        'code',
        'name',
        'account_type',
        'sub_type',
        'normal_balance',
        'parent_id',
        'description',
        'is_active',
        'is_system',
        'is_locked',
        'currency',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_locked' => 'boolean',
    ];
    /**
     * Get parent account
     */

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }
     /**
     * Get child accounts
     */
    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
     /**
     * Get journal entry lines for this account
     */
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
     /**
     * Calculate account balance
     */
    }

    public function getBalanceAttribute()
    {
        $debits = $this->journalEntryLines()
            ->whereHas('journalEntry', function($query) {
                $query->where('status', 'POSTED');
            })
            ->sum('debit_amount');
        
        $credits = $this->journalEntryLines()
            ->sum('credit_amount');
        // For Assets and Expenses: Debit - Credit
        // For Liabilities, Equity, Income: Credit - Debit
        if (in_array($this->account_type, ['Asset', 'Expense'])) {
            return $debits - $credits;
        }
        return $credits - $debits;
    }
}