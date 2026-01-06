<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Expense extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'payee',
        'description',
        'amount',
        'expense_date',
        'category_id',
        'payment_account_id',
        'created_by',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    /**
     * Get the category account for this expense
     */
    ];


    public function category()
    {
        return $this->belongsTo(ChartOfAccount::class, 'category_id');
    }
     /**
     * Get the payment account for this expense
     */
    public function paymentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }
}