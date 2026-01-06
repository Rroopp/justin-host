<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PayrollRun extends Model
{
    use HasFactory, Auditable;
    protected $guarded = [];
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];
    public function items()
    {
        return $this->hasMany(PayrollItem::class, 'run_id');
    }
}