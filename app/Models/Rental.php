<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Rental extends Model
{
    protected $fillable = [
        'customer_id',
        'rented_at',
        'expected_return_at',
        'returned_at',
        'status',
        'items',
        'notes',
    ];
    protected $casts = [
        'rented_at' => 'datetime',
        'expected_return_at' => 'datetime',
        'returned_at' => 'datetime',
        'items' => 'array',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function posSale()
    {
        return $this->belongsTo(PosSale::class);
    }

    public function rentalItems()
    {
        return $this->hasMany(RentalItem::class);
    }

    public function inventoryItems()
    {
        return $this->belongsToMany(Inventory::class, 'rental_items')
                    ->withPivot(['quantity', 'condition_out', 'condition_in', 'price_at_rental', 'notes'])
                    ->withTimestamps();
    }
}