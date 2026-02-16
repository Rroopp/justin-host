<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollEarningType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_taxable',
        'is_recurring',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function recurringEarnings()
    {
        return $this->hasMany(RecurringEarning::class, 'earning_type_id');
    }
}
