<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
class Inventory extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'inventory_master';
    
    protected static function booted()
    {
        static::creating(function ($inventory) {
            if (empty($inventory->code)) {
                $inventory->code = 'INV-' . strtoupper(\Illuminate\Support\Str::random(8));
                // Ensure uniqueness could be added here if needed, but random(8) is fairly collision resistant for small scale
            }
        });
    }

    protected $fillable = [
        'product_name',
        'category',
        'subcategory',
        'type',
        'size',
        'size_unit',
        'code',
        'unit',
        'quantity_in_stock',
        'min_stock_level',
        'max_stock',
        'reorder_threshold',
        'expiry_date',
        'batch_number',
        'is_rentable',
        'requires_serial_tracking',
        'country_of_manufacture',
        'packaging_unit',
        'price',
        'selling_price',
        'profit',
        'manufacturer',
        'description',
        'attributes',
        'moving_average_cost',
        'is_vatable',
        'tax_rate_id',
    ];
    protected $casts = [
        'quantity_in_stock' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock' => 'integer',
        'reorder_threshold' => 'integer',
        'is_rentable' => 'boolean',
        'is_vatable' => 'boolean',
        'expiry_date' => 'date',
        'price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'moving_average_cost' => 'decimal:2',
        'attributes' => 'array',
    /**
     * Check if stock is low
     */
    ];


    public function isLowStock(?int $threshold = null): bool
    {
        $effectiveThreshold = $threshold ?? ($this->min_stock_level ?? 10);
        return $this->quantity_in_stock <= $effectiveThreshold;
    }
    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity_in_stock <= 0;
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if expiring soon (e.g. within 30 days)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && 
               $this->expiry_date->isFuture() && 
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Get sales for this product
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'inventory_id');
    }

    /**
     * Get the tax rate for this inventory item
     */
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function assetProfile()
    {
        return $this->belongsTo(Asset::class, 'asset_profile_id');
    }

    /**
     * Calculate tax amount based on selling price
     */
    public function calculateTax()
    {
        if (!$this->taxRate) {
            return 0;
        }
        
        if ($this->taxRate->type === 'percentage') {
            return ($this->selling_price * $this->taxRate->rate) / 100;
        }
        
        return $this->taxRate->rate; // Fixed amount
    }

    /**
     * Get price including tax
     */
    public function getPriceWithTax()
    {
        return $this->selling_price + $this->calculateTax();
    }

    public function rentals()
    {
        return $this->belongsToMany(Rental::class, 'rental_items')
                    ->withPivot(['quantity', 'condition_out', 'condition_in', 'price_at_rental', 'notes'])
                    ->withTimestamps();
    }

    public function activeRentals()
    {
        return $this->rentals()->where(function($query) {
            $query->where('status', 'active')
                  ->orWhere('status', 'overdue');
        });
    }

    public function getRentedQuantityAttribute()
    {
        // Calculate total quantity currently out on rent (active or overdue)
        return $this->activeRentals()->sum('rental_items.quantity');
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->quantity_in_stock - $this->rented_quantity;
    }

    /**
     * Get batches for this product
     */
    public function batches()
    {
        return $this->hasMany(Batch::class, 'inventory_id')->orderBy('expiry_date', 'asc');
    }
}