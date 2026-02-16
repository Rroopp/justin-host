<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SetMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'surgical_set_id',
        'case_reservation_id',
        'location_id', // Destination
        'dispatched_at',
        'returned_at',
        'dispatched_by',
        'received_by',
        'status', // dispatched, returned, reconciled
        'notes',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function surgicalSet()
    {
        return $this->belongsTo(SurgicalSet::class);
    }

    public function caseReservation()
    {
        return $this->belongsTo(CaseReservation::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
