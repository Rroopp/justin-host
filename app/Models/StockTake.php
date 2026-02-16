<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class StockTake extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'reference_number',
        'date',
        'category_filter',
        'status',
        'created_by',
        'approved_by',
        'notes',
    ];
    protected $casts = [
        'date' => 'date',
        'category_filter' => 'array',
    ];

    /**
     * Get the items for this stock take
     */


    public function items()
    {
        return $this->hasMany(StockTakeItem::class);
    }
     /**
     * Get the staff member who created this stock take
     */
    public function creator()
    {
        return $this->belongsTo(Staff::class, 'created_by');
     /**
     * Get the staff member who approved this stock take
     */
    }

    public function approver()
    {
        return $this->belongsTo(Staff::class, 'approved_by');
     /**
     * Generate a unique reference number
     */
    }

    public static function generateReferenceNumber()
    {
        $year = date('Y');
        $prefix = "ST-{$year}-";
        
        // Find the last stock take for this year
        $lastStockTake = self::where('reference_number', 'like', "{$prefix}%")
            ->orderBy('reference_number', 'desc')
            ->first();
        if ($lastStockTake) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastStockTake->reference_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
     /**
     * Get count of items with variances
     */
    public function getVarianceCountAttribute()
    {
        return $this->items()->whereNotNull('variance')->where('variance', '!=', 0)->count();
     /**
     * Get total value of variances
     */
    }

    public function getTotalVarianceValueAttribute()
    {
        return $this->items()
            ->join('inventory_master', 'stock_take_items.inventory_id', '=', 'inventory_master.id')
            ->selectRaw('SUM(stock_take_items.variance * inventory_master.price) as total')
            ->value('total') ?? 0;
     /**
     * Check if stock take is editable
     */
    }

    public function isEditable()
    {
        return in_array($this->status, ['draft', 'in_progress']);
     /**
     * Check if stock take can be reconciled
     */
    }

    public function canReconcile()
    {
        return $this->status === 'completed';
    }
}