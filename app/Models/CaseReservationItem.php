<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseReservationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_reservation_id',
        'inventory_id',
        'batch_id',
        'quantity_reserved',
        'quantity_used',
        'status', // pending, used, returned, partial
        'notes',
        'custom_price',
    ];

    public function reservation()
    {
        return $this->belongsTo(CaseReservation::class, 'case_reservation_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'used' => 'green',
            'returned' => 'gray',
            'partial' => 'blue',
            default => 'gray',
        };
    }
}
