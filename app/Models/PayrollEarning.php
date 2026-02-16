<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEarning extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'basic_salary',
        'allowances',
        'overtime',
        'bonuses',
        'reimbursements',
        'gross_pay',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'overtime' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'reimbursements' => 'decimal:2',
        'gross_pay' => 'decimal:2',
    ];
}
