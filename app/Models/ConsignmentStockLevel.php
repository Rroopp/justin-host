<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentStockLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'inventory_id',
        'batch_id',
        'quantity_placed',
        'quantity_used',
        'quantity_available',
        'last_placed_date',
        'last_used_date',
        'days_at_location',
    ];

    protected $casts = [
        'last_placed_date' => 'date',
        'last_used_date' => 'date',
        'quantity_placed' => 'integer',
        'quantity_used' => 'integer',
        'quantity_available' => 'integer',
        'days_at_location' => 'integer',
    ];

    /**
     * Relationships
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Scopes
     */
    public function scopeAging($query, $days = 90)
    {
        return $query->where('days_at_location', '>', $days);
    }

    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->where('quantity_available', '<=', $threshold)
                     ->where('quantity_available', '>', 0);
    }

    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    /**
     * Update stock levels from transactions
     */
    public function updateLevels()
    {
        $placed = ConsignmentTransaction::where('location_id', $this->location_id)
            ->where('inventory_id', $this->inventory_id)
            ->where('batch_id', $this->batch_id)
            ->placed()
            ->sum('quantity');

        $used = ConsignmentTransaction::where('location_id', $this->location_id)
            ->where('inventory_id', $this->inventory_id)
            ->where('batch_id', $this->batch_id)
            ->used()
            ->sum('quantity');

        $returned = ConsignmentTransaction::where('location_id', $this->location_id)
            ->where('inventory_id', $this->inventory_id)
            ->where('batch_id', $this->batch_id)
            ->returned()
            ->sum('quantity');

        $lastPlaced = ConsignmentTransaction::where('location_id', $this->location_id)
            ->where('inventory_id', $this->inventory_id)
            ->where('batch_id', $this->batch_id)
            ->placed()
            ->latest('transaction_date')
            ->first();

        $lastUsed = ConsignmentTransaction::where('location_id', $this->location_id)
            ->where('inventory_id', $this->inventory_id)
            ->where('batch_id', $this->batch_id)
            ->used()
            ->latest('transaction_date')
            ->first();

        $this->update([
            'quantity_placed' => $placed - $returned,
            'quantity_used' => $used,
            'quantity_available' => ($placed - $returned) - $used,
            'last_placed_date' => $lastPlaced?->transaction_date,
            'last_used_date' => $lastUsed?->transaction_date,
            'days_at_location' => $lastPlaced ? $lastPlaced->transaction_date->diffInDays(now()) : 0,
        ]);
    }

    /**
     * Get aging status color for UI
     */
    public function getAgingColorAttribute()
    {
        if ($this->days_at_location > 365) {
            return 'red';
        } elseif ($this->days_at_location > 180) {
            return 'orange';
        } elseif ($this->days_at_location > 90) {
            return 'yellow';
        }
        return 'green';
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationRateAttribute()
    {
        if ($this->quantity_placed == 0) {
            return 0;
        }

        return round(($this->quantity_used / $this->quantity_placed) * 100, 2);
    }

    /**
     * Calculate stock value
     */
    public function getValueAttribute()
    {
        if (!$this->inventory) {
            return 0;
        }

        $price = $this->batch?->selling_price ?? $this->inventory->selling_price ?? 0;
        return $price * $this->quantity_available;
    }
}
