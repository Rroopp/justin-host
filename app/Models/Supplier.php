<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Supplier extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'tax_id',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    /**
     * Get purchase orders for this supplier
     */
    ];


    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}