<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class CustomerPackagePricing extends Model
{
    use HasFactory, Auditable;
    protected $table = 'customer_package_pricing';
    protected $fillable = [
        'customer_id',
        'package_id',
        'price',
    ];
    protected $casts = [
        'price' => 'decimal:2',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}