<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get a setting by key, with optional default.
     * Uses caching to reduce DB queries.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $value = DB::table('settings')->where('key', $key)->value('value');
            
            if ($value === null) {
                return $default;
            }

            // Attempt to decode JSON if it looks like an array/object
            // However, our current settings are mostly simple strings. 
            // If we strictly follow the controller logic, we might not need complex decoding here 
            // unless we start saving arrays.
            
            return $value;
        });
    }

    /**
     * Clear settings cache (call this when updating settings)
     */
    public static function clearCache(string $key)
    {
        Cache::forget("setting_{$key}");
    }

    /**
     * Helper to get currency symbol
     */
    public static function currencySymbol()
    {
        return self::get('currency_symbol', 'KSh');
    }

    /**
     * Helper to get currency code
     */
    public static function currencyCode()
    {
        return self::get('currency_code', 'KSH');
    }

    /**
     * Helper to get default tax rate
     */
    public static function defaultTaxRate()
    {
        return (float) self::get('default_tax_rate', 16);
    }

    /**
     * Helper to get invoice prefix
     */
    public static function invoicePrefix()
    {
        return self::get('invoice_prefix', 'INV-');
    }
}
