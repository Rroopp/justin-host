<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Sale extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'inventory_id',
        'product_id',
        'quantity',
        'total',
        'seller_username',
        'product_snapshot',
        'date',
    ];
    protected $casts = [
        'quantity' => 'integer',
        'total' => 'decimal:2',
        'product_snapshot' => 'array',
        'date' => 'date',
    /**
     * Get the inventory item for this sale
     */
    ];


    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}