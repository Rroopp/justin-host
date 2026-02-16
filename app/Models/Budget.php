<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Budget extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'reference_number',
        'name',
        'description',
        'period_type',
        'start_date',
        'end_date',
        'total_allocated',
        'total_spent',
        'total_remaining',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'total_allocated' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_remaining' => 'decimal:2',
    // Relationships
    ];


    public function lineItems()
    {
        return $this->hasMany(BudgetLineItem::class);
    }
    public function forecasts()
    {
        return $this->hasMany(BudgetForecast::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    // Scopes
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPeriod($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                     ->where('end_date', '>=', now())
                     ->where('status', 'active');
    // Methods
    }

    public function calculateTotals()
    {
        $this->total_allocated = $this->lineItems()->sum('allocated_amount');
        $this->total_spent = $this->lineItems()->sum('spent_amount');
        $this->total_remaining = $this->total_allocated - $this->total_spent;
        $this->save();
    }

    public function getVariance()
    {
        return $this->total_spent - $this->total_allocated;
    }

    public function getVariancePercentage()
    {
        if ($this->total_allocated == 0) {
            return 0;
        }
        return ($this->getVariance() / $this->total_allocated) * 100;
    }

    public function isOverBudget()
    {
        return $this->total_spent > $this->total_allocated;
    }

    public function getUtilizationPercentage()
    {
        return ($this->total_spent / $this->total_allocated) * 100;
    }

    public function canEdit()
    {
        return in_array($this->status, ['draft', 'active']);
    }

    public function canApprove()
    {
        return $this->status === 'draft';
    }

    public function approve($userId)
    {
        $this->status = 'active';
        $this->approved_by = $userId;
        $this->approved_at = now();
    }

    public function complete()
    {
        $this->status = 'completed';
    }

    public function archive()
    {
        $this->status = 'archived';
    // Generate unique reference number
    }

    public static function generateReferenceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return "BUD-{$year}{$month}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}