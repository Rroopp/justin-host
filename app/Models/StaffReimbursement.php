<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class StaffReimbursement extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'staff_id',
        'reference_number',
        'description',
        'category',
        'amount',
        'expense_date',
        'receipt_file_path',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'paid_by',
        'paid_at',
        'payment_method',
        'payroll_run_id',
        'payment_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(Staff::class, 'paid_by');
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function paymentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Auto-generate reference number on creation
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($reimbursement) {
            if (!$reimbursement->reference_number) {
                $year = now()->year;
                $lastRef = static::where('reference_number', 'like', "REI-{$year}-%")
                    ->orderByDesc('id')
                    ->first();
                
                $nextNum = $lastRef ? (int)substr($lastRef->reference_number, -4) + 1 : 1;
                $reimbursement->reference_number = sprintf("REI-%d-%04d", $year, $nextNum);
            }
        });
    }
}
