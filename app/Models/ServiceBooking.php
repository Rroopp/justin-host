<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ServiceBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'customer_id',
        'staff_id',
        'location_id',
        'booking_date',
        'booking_time',
        'end_time',
        'duration_minutes',
        'status',
        'notes',
        'total_amount',
        'payment_status',
        'reminder_sent',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_amount' => 'decimal:2',
        'duration_minutes' => 'integer',
        'reminder_sent' => 'boolean',
    ];

    /**
     * Boot method to auto-calculate end_time
     */
    protected static function booted()
    {
        static::saving(function ($booking) {
            if ($booking->booking_time && $booking->duration_minutes) {
                $start = Carbon::parse($booking->booking_time);
                $booking->end_time = $start->addMinutes($booking->duration_minutes)->format('H:i:s');
            }
        });
    }

    /**
     * Get the service
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the location
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope for upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
                     ->whereIn('status', ['scheduled', 'confirmed'])
                     ->orderBy('booking_date')
                     ->orderBy('booking_time');
    }

    /**
     * Scope for today's bookings
     */
    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', now()->toDateString());
    }

    /**
     * Get formatted datetime for calendar
     */
    public function getCalendarStartAttribute()
    {
        return $this->booking_date->format('Y-m-d') . 'T' . $this->booking_time;
    }

    /**
     * Get formatted end datetime for calendar
     */
    public function getCalendarEndAttribute()
    {
        return $this->booking_date->format('Y-m-d') . 'T' . $this->end_time;
    }
}
