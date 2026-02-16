<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffTimeOff extends Model
{
    use HasFactory;

    protected $table = 'staff_time_off';

    protected $fillable = [
        'staff_id',
        'start_date',
        'end_date',
        'reason',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    /**
     * Check if date is within time off period
     */
    public function coversDate($date)
    {
        return $date >= $this->start_date && $date <= $this->end_date;
    }
}
