<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class InventoryAdjustment extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'inventory_id',
        'staff_id',
        'adjustment_type',
        'quantity',
        'old_quantity',
        'new_quantity',
        'reason',
        'notes',
    ];
    protected $casts = [
        'quantity' => 'integer',
        'old_quantity' => 'integer',
        'new_quantity' => 'integer',
    ];


    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}