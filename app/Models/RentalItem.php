<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class RentalItem extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'rental_id',
        'inventory_id',
        'quantity',
        'condition_out',
        'condition_in',
        'price_at_rental',
        'notes',
    ];
    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}