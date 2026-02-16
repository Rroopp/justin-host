<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'deduction_type_id',
        'amount',
        'is_statutory',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_statutory' => 'boolean',
    ];

    public function deductionType()
    {
        return $this->belongsTo(PayrollDeductionType::class);
    }
}
