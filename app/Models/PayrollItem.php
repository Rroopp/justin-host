<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PayrollItem extends Model
{
    use HasFactory, Auditable;
    protected $guarded = [];
    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'run_id');
    }
    public function employee()
    {
        return $this->belongsTo(Staff::class, 'employee_id');
    }
}