<?php

use App\Services\SettingsService;

if (!function_exists('format_currency')) {
    /**
     * Format number as currency string
     */
    function format_currency($value)
    {
        $symbol = SettingsService::currencySymbol();
        // Assuming number_format preferences might come later, for now standard English
        return $symbol . ' ' . number_format((float)$value, 2);
    }
}

if (!function_exists('settings')) {
    /**
     * Get a setting value
     */
    function settings($key, $default = null)
    {
        return SettingsService::get($key, $default);
    }
}
