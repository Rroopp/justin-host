<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Batch;

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

    // Accessors for Dashboard
    public function getCurrentQuantityAttribute()
    {
        if (!$this->location_id || !$this->inventory_id) return 0;
        
        return Batch::where('location_id', $this->location_id)
            ->where('inventory_id', $this->inventory_id)
            ->sum('quantity');
    }

    public function getMissingQuantityAttribute()
    {
        $current = $this->getCurrentQuantityAttribute();
        $standard = $this->standard_quantity;
        return max(0, $standard - $current);
    }
}
