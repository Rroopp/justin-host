<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeductionType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_statutory',
        'liability_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_statutory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'liability_account_id');
    }
}
