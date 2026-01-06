<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Commission extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'staff_id',
        'pos_sale_id',
        'type',
        'amount',
        'status',
        'description',
        'paid_at',
        'expense_id'
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];


    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
    public function sale()
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }
}