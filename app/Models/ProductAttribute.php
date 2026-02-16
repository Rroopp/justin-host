<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class ProductAttribute extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'slug',
        'type',
        'unit',
        'is_required',
    ];
    protected $casts = [
        'is_required' => 'boolean',
    /**
     * Get the options for this attribute (if type is select)
     */
    ];


    public function options()
    {
        return $this->hasMany(ProductAttributeOption::class)->orderBy('sort_order');
    }
     /**
     * Get categories that use this attribute
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product_attribute');
    }
}