<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PosSalePayment extends Model
{
    use HasFactory, Auditable;
    protected $table = 'pos_sale_payments';
    protected $fillable = [
        'pos_sale_id',
        'amount',
        'payment_method',
        'payment_date',
        'payment_reference',
        'payment_notes',
        'received_by',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];


    public function sale()
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}