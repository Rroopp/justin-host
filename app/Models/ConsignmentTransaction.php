<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'inventory_id',
        'batch_id',
        'transaction_type',
        'quantity',
        'transaction_date',
        'reference_type',
        'reference_id',
        'billed',
        'billed_date',
        'billing_reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'billed_date' => 'date',
        'billed' => 'boolean',
        'quantity' => 'integer',
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

    public function createdBy()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Polymorphic relationship to reference (PosSale, SurgeryUsage, etc.)
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopePlaced($query)
    {
        return $query->where('transaction_type', 'placed');
    }

    public function scopeUsed($query)
    {
        return $query->where('transaction_type', 'used');
    }

    public function scopeReturned($query)
    {
        return $query->where('transaction_type', 'returned');
    }

    public function scopeUnbilled($query)
    {
        return $query->where('billed', false)->where('transaction_type', 'used');
    }

    public function scopeBilled($query)
    {
        return $query->where('billed', true);
    }

    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForProduct($query, $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Helper Methods
     */
    public function markAsBilled($invoiceReference = null)
    {
        $this->update([
            'billed' => true,
            'billed_date' => now(),
            'billing_reference' => $invoiceReference,
        ]);
    }

    public function isAging($days = 90)
    {
        if ($this->transaction_type !== 'placed') {
            return false;
        }

        return $this->transaction_date->diffInDays(now()) > $days;
    }

    /**
     * Get transaction type badge color for UI
     */
    public function getTypeColorAttribute()
    {
        return match($this->transaction_type) {
            'placed' => 'blue',
            'used' => 'green',
            'returned' => 'yellow',
            'expired' => 'red',
            'damaged' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Calculate value of transaction
     */
    public function getValueAttribute()
    {
        if (!$this->inventory) {
            return 0;
        }

        $price = $this->batch?->selling_price ?? $this->inventory->selling_price ?? 0;
        return $price * $this->quantity;
    }
}
