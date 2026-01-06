<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
class Lpo extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $fillable = [
        'customer_id',
        'lpo_number',
        'amount',
        'remaining_balance',
        'valid_from',
        'valid_until',
        'description',
        'document_path',
        'status',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function sales()
    {
        return $this->hasMany(PosSale::class);
    }
}