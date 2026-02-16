<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'earning_type_id',
        'amount',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function earningType()
    {
        return $this->belongsTo(PayrollEarningType::class, 'earning_type_id');
    }
}
