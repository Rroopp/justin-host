<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'manufacturer_id',
        'batch_number',
        'serial_number',
        'is_serialized',
        'expiry_date',
        'quantity',
        'cost_price',
        'selling_price',
        'location_id',
        'status',
        'ownership_type',
        'recall_status',
        'recall_date',
        'recall_reason',
        'sold_to_customer_id',
        'sold_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'sold_date' => 'date',
        'recall_date' => 'date',
        'quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_serialized' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Supplier::class, 'manufacturer_id');
    }

    public function soldToCustomer()
    {
        return $this->belongsTo(Customer::class, 'sold_to_customer_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'batch_id');
    }

    /**
     * Scopes
     */
    public function scopeSerialized($query)
    {
        return $query->where('is_serialized', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeRecalled($query)
    {
        return $query->where('recall_status', '!=', 'none');
    }

    public function scopeExpiringSoon($query, $days = 90)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<=', now()->addDays($days))
                     ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now());
    }

    /**
     * Helper Methods
     */
    public function isRecalled()
    {
        return $this->recall_status !== 'none';
    }

    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    public function isAvailable()
    {
        return $this->status === 'available' && !$this->isRecalled() && !$this->isExpired();
    }

    public function markAsRecalled($reason, $date = null)
    {
        $this->update([
            'recall_status' => 'active',
            'recall_date' => $date ?? now(),
            'recall_reason' => $reason,
            'status' => 'recalled',
        ]);
    }

    public function markAsSold($customerId = null, $date = null)
    {
        $this->update([
            'status' => 'sold',
            'sold_to_customer_id' => $customerId,
            'sold_date' => $date ?? now(),
        ]);
    }

    public function markAsReturned()
    {
        $this->update([
            'status' => 'returned',
        ]);
    }

    public function resolveRecall($notes = null)
    {
        $this->update([
            'recall_status' => 'resolved',
            'recall_reason' => $this->recall_reason . ($notes ? "\n\nResolution: " . $notes : ''),
        ]);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'available' => 'green',
            'reserved' => 'yellow',
            'sold' => 'blue',
            'recalled' => 'red',
            'expired' => 'gray',
            'damaged' => 'orange',
            'returned' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get recall status badge color for UI
     */
    public function getRecallColorAttribute()
    {
        return match($this->recall_status) {
            'none' => 'green',
            'pending' => 'yellow',
            'active' => 'red',
            'resolved' => 'blue',
            default => 'gray',
        };
    }
}
