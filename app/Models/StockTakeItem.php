<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class StockTakeItem extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'stock_take_id',
        'inventory_id',
        'system_quantity',
        'physical_quantity',
        'variance',
        'notes',
    ];
    protected $casts = [
        'system_quantity' => 'decimal:2',
        'physical_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    /**
     * Get the stock take this item belongs to
     */


    public function stockTake()
    {
        return $this->belongsTo(StockTake::class);
    }
     /**
     * Get the inventory item
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
     /**
     * Calculate and update variance
     */
    }

    public function calculateVariance()
    {
        if ($this->physical_quantity !== null) {
            $this->variance = $this->physical_quantity - $this->system_quantity;
            $this->save();
    }
        }
     /**
     * Check if this item has been counted
     */
    public function isCounted()
    {
        return $this->physical_quantity !== null;
     /**
     * Check if this item has a variance
     */
    }

    public function hasVariance()
    {
        return $this->variance !== null && $this->variance != 0;
     /**
     * Get variance percentage
     */
    }

    public function getVariancePercentageAttribute()
    {
        if ($this->system_quantity == 0) {
            return $this->physical_quantity > 0 ? 100 : 0;
        }
        
        return ($this->variance / $this->system_quantity) * 100;
    }
}