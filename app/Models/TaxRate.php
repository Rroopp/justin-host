<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'type',
        'is_default',
        'is_active',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default tax rate
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get VAT rate (16%)
     */
    public static function getVAT()
    {
        return static::where('rate', 16.00)->where('is_active', true)->first();
    }

    /**
     * Get zero rate
     */
    public static function getZeroRate()
    {
        return static::where('rate', 0.00)->where('is_default', true)->where('is_active', true)->first();
    }
}
