<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PayrollRun extends Model
{
    use HasFactory, Auditable;
    protected $guarded = [];
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_gross' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_employer_contributions' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PayrollItem::class, 'run_id');
    }

    public function earnings()
    {
        return $this->hasMany(PayrollEarning::class, 'payroll_run_id');
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class, 'payroll_run_id');
    }

    public function employerContributions()
    {
        return $this->hasMany(PayrollEmployerContribution::class, 'payroll_run_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function paymentJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'payment_journal_entry_id');
    }
}