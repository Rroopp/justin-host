<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Asset extends Model
{
    use HasFactory, Auditable;
    protected $guarded = [];
    protected $casts = [
        'purchase_date' => 'date',
        'allocation_date' => 'date',
    ];
    public function calculateDepreciation()
    {
        // Simple straight line depreciation
        if ($this->depreciation_method === 'straight_line') {
            $cost = $this->purchase_price;
            $salvage = $this->salvage_value;
            $life = $this->useful_life_years;
            
            if ($life <= 0) return 0;
            return ($cost - $salvage) / $life; // Annual depreciation
        }
        return 0;
    }
}