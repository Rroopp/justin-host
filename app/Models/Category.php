<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Category extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'description',
    ];
    /**
     * Get subcategories for this category
     */
    


    


    


    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
     /**
     * Get inventory items in this category
     */
    public function inventoryItems()
    {
        return Inventory::where('category', $this->name)->get();
     /**
     * Get dynamic attributes for this category
     */
    }

    public function attributes()
    {
        return $this->belongsToMany(ProductAttribute::class, 'category_product_attribute')
                    ->withPivot('sort_order')
                    ->orderByPivot('sort_order');
    }
}