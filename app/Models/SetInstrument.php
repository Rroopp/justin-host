<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SetInstrument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'surgical_set_id',
        'name',
        'inventory_id', // Link to product definition if it exists
        'serial_number',
        'quantity',
        'condition', // good, damaged, missing, maintenance
        'status', // good, damaged, missing, maintenance
        'current_location_id',
        'notes',
    ];

    public function surgicalSet()
    {
        return $this->belongsTo(SurgicalSet::class);
    }

    public function inventory() // The product definition
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function currentLocation()
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Scopes
     */
    public function scopeMissing($query)
    {
        return $query->where('status', 'missing');
    }

    public function scopeInSet($query)
    {
        // An instrument is in a set if it has a surgical_set_id AND current_location_id matches the set's location (or is null, implying it's with the set)
        // For simplicity, let's assume if attached to set and status is good, it's in the set.
        return $query->whereNotNull('surgical_set_id')->where('status', 'good');
    }

    public function scopeLoose($query)
    {
        return $query->whereNull('surgical_set_id');
    }
}
