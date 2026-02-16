<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'name',
        'code',
        'description',
        'category_id',
        'base_price',
        'tax_rate_id',
        'duration_minutes',
        'requires_equipment',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'requires_equipment' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * Get the tax rate
     */
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Get bookings for this service
     */
    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class);
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax()
    {
        if (!$this->taxRate) {
            return 0;
        }
        
        if ($this->taxRate->type === 'percentage') {
            return ($this->base_price * $this->taxRate->rate) / 100;
        }
        
        return $this->taxRate->rate;
    }

    /**
     * Get price including tax
     */
    public function getPriceWithTax()
    {
        return $this->base_price + $this->calculateTax();
    }
}
