<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryUsageItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_usage_id',
        'inventory_id',
        'batch_id',
        'quantity',
        'from_set'
    ];

    protected $casts = [
        'from_set' => 'boolean'
    ];

    public function surgeryUsage()
    {
        return $this->belongsTo(SurgeryUsage::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
