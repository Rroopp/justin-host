<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PackageItem extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'package_id',
        'inventory_id',
        'quantity',
    ];
    protected $casts = [
        'quantity' => 'decimal:2',
    ];


    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id'); // Using Inventory model for inventory_master
    }
}