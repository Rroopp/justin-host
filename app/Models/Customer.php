<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
class Customer extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $fillable = [
        'customer_code',
        'customer_type',
        'name',
        'facility',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'country',
        'tax_number',
        'payment_terms',
        'credit_limit',
        'current_balance',
        'is_active',
        'patient_name',
        'patient_number',
        'patient_type',
    ];
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];


    protected static function booted(): void
    {
        static::created(function (Customer $customer) {
            // Don't break if migration hasn't been applied yet
            if (!Schema::hasColumn('customers', 'customer_code')) {
                return;
            }
            if (!$customer->customer_code) {
                $customer->customer_code = 'CUST-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT);
                $customer->saveQuietly();
            }
        });
    }
    /**
     * Get sales for this customer
     */
    
    public function sales()
    {
        return $this->hasMany(PosSale::class);
    }

    public function lpos()
    {
        return $this->hasMany(Lpo::class);
    }
}