<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'inventory_id',
        'standard_quantity',
        'notes'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
