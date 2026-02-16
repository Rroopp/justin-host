<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
class PosSale extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    // Skip 'created' because POSController logs a richer 'sale_completed' event manually.
    protected $auditEvents = ['updated', 'deleted'];

    protected $table = 'pos_sales';
    protected $fillable = [
        'sale_type',
        'sale_items',
        'payment_method',
        'payment_status',
        'sale_status',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'vat',
        'total',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_id',
        'customer_snapshot',
        'seller_username',
        'timestamp',
        'receipt_generated',
        'receipt_data',
        'document_type',
        'invoice_number',
        'due_date',
        'lpo_number',
        'patient_type',
        'delivery_note_generated',
        'delivery_note_data',
        'payment_date',
        'payment_reference',
        'payment_notes',
        'patient_name',
        'patient_number',
        'facility_name',
        'surgeon_name',
        'nurse_name',
        'is_reconciled',
        'reconciled_at',
    ];
    protected $casts = [
        'sale_items' => 'array',
        'customer_snapshot' => 'array',
        'receipt_data' => 'array',
        'delivery_note_data' => 'array',
        'subtotal' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'receipt_generated' => 'boolean',
        'delivery_note_generated' => 'boolean',
        'due_date' => 'date',
        'payment_date' => 'date',
        'timestamp' => 'datetime',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function payments()
    {
        return $this->hasMany(PosSalePayment::class, 'pos_sale_id');
    }

    public function rental()
    {
        return $this->hasOne(Rental::class);
    }

    public function lpo()
    {
        return $this->belongsTo(Lpo::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'pos_sale_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'pos_sale_id');
    }
}