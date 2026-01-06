<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PurchaseOrderItem extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'quantity_received',
        'received_date',
        'unit_cost',
        'total_cost',
    ];
    protected $casts = [
        'quantity' => 'integer',
        'quantity_received' => 'integer',
        'received_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    /**
     * Get the order for this item
     */
    ];


    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }
     /**
     * Get the product for this item
     */
    public function product()
    {
        return $this->belongsTo(Inventory::class, 'product_id');
    }
}