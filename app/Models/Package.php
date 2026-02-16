<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Package extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'is_active',
    ];
    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];


    public function items()
    {
        return $this->hasMany(PackageItem::class);
    }
    public function customerPricing()
    {
        return $this->hasMany(CustomerPackagePricing::class);
    /**
     * Get price for a specific customer, or base price
     */
    }

    public function getPriceForCustomer($customerId)
    {
        $pricing = $this->customerPricing()->where('customer_id', $customerId)->first();
        return $pricing ? $pricing->price : $this->base_price;
    }
}