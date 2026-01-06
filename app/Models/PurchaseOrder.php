<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class PurchaseOrder extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'supplier_name',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'payment_terms',
        'delivery_address',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    /**
     * Get the supplier for this order
     */
    


    


    


    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get order items
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'order_id');
    }

    /**
     * Generate order number
     */
    public static function generateOrderNumber(): string
    {
        $count = self::count() + 1;
        return 'PO-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}