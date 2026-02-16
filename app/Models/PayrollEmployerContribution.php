<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEmployerContribution extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'contribution_type_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function contributionType()
    {
        return $this->belongsTo(PayrollContributionType::class);
    }
}
