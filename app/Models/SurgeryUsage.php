<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryUsage extends Model
{
    use HasFactory;

    protected $table = 'surgery_usage';

    protected $fillable = [
        'surgery_date',
        'patient_name',
        'patient_number',
        'surgeon_name',
        'facility_name',
        'set_location_id',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'surgery_date' => 'date'
    ];

    public function items()
    {
        return $this->hasMany(SurgeryUsageItem::class, 'surgery_usage_id');
    }

    public function setLocation()
    {
        return $this->belongsTo(Location::class, 'set_location_id');
    }

    public function user()
    {
        return $this->belongsTo(Staff::class, 'user_id');
    }
}
