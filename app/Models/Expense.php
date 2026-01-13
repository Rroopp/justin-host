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
        'vendor_id',
        'status',          // paid, unpaid, partial
        'due_date',
        'reference_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Get the category account for this expense
     */
    public function category()
    {
        return $this->belongsTo(ChartOfAccount::class, 'category_id');
    }

    /**
     * Get the payment account for this expense (Source of Funds)
     * e.g., Bank, Cash, Petty Cash.
     * Can be null if Status is 'unpaid'.
     */
    public function paymentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }

    /**
     * Get the vendor for this expense (if Bill)
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}