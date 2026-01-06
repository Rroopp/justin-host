<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Subcategory extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'category_id',
        'description',
    ];
    /**
     * Get the category that owns this subcategory
     */
    


    


    


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
     /**
     * Get inventory items in this subcategory
     */
    public function inventoryItems()
    {
        return Inventory::where('subcategory', $this->name)->get();
    }
}