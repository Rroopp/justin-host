<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class BudgetLineItem extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'budget_id',
        'category',
        'subcategory',
        'description',
        'allocated_amount',
        'spent_amount',
        'remaining_amount',
        'forecast_basis',
        'notes',
    ];
    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    // Relationships
    ];


    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
    // Methods
    public function updateSpent($amount)
    {
        $this->spent_amount += $amount;
        $this->remaining_amount = $this->allocated_amount - $this->spent_amount;
        $this->save();
        // Update parent budget totals
        $this->budget->calculateTotals();
    }

    public function getUtilizationPercentage()
    {
        if ($this->allocated_amount == 0) {
            return 0;
        }
        return ($this->spent_amount / $this->allocated_amount) * 100;
    }
    public function getVariance()
    {
        return $this->spent_amount - $this->allocated_amount;
    }

    public function isOverBudget()
    {
        return $this->spent_amount > $this->allocated_amount;
    }

    public function hasAvailableFunds($amount)
    {
        return $this->remaining_amount >= $amount;
    }
}