<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class ProductAttributeOption extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'product_attribute_id',
        'value',
        'sort_order',
    ];
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}