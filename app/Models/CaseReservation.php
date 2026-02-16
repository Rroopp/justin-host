<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CaseReservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_number',
        'patient_name',
        'patient_id',
        'surgeon_name',
        'procedure_name',
        'surgery_date',
        'location_id',
        'customer_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'surgery_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->case_number) {
                // Example: CASE-20240204-ABCD
                $model->case_number = 'CASE-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            }
        });
    }

    public function items()
    {
        return $this->hasMany(CaseReservationItem::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function surgicalSets()
    {
        return $this->belongsToMany(SurgicalSet::class, 'case_reservation_surgical_set')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('surgery_date', '>=', now())
                     ->whereIn('status', ['draft', 'confirmed']);
    }
}
