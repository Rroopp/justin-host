<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class BudgetForecast extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'budget_id',
        'category',
        'historical_data',
        'projected_amount',
        'confidence_level',
        'growth_rate',
        'seasonality_factor',
    ];
    protected $casts = [
        'historical_data' => 'array',
        'projected_amount' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'seasonality_factor' => 'decimal:2',
    // Relationships
    ];


    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
}