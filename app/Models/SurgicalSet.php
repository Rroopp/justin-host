<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurgicalSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'asset_id',
        'location_id',
        'status', // available, in_surgery, in_transit, maintenance, incomplete
        'sterilization_status', // sterile, non_sterile, expired
        'last_service_date',
        'responsible_staff_id',
        'notes',
    ];

    protected $casts = [
        'last_service_date' => 'date',
    ];

    // Status Constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_IN_SURGERY = 'in_surgery';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DIRTY = 'dirty';
    const STATUS_STERILIZING = 'sterilizing';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_INCOMPLETE = 'incomplete';

    public function instruments()
    {
        return $this->hasMany(SetInstrument::class);
    }

    public function movements()
    {
        return $this->hasMany(SetMovement::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
    
    public function responsibleStaff()
    {
        return $this->belongsTo(User::class, 'responsible_staff_id');
    }

    public function caseReservations()
    {
        return $this->belongsToMany(CaseReservation::class, 'case_reservation_surgical_set')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    /**
     * Helper Methods
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isSterile(): bool
    {
        return $this->sterilization_status === 'sterile';
    }

    public function isDispatched(): bool
    {
        return in_array($this->status, [self::STATUS_DISPATCHED, self::STATUS_IN_SURGERY]);
    }
}
