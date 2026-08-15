<?php

use App\Models\Setting;
use App\Services\ActivityLogService;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('shop_name')) {
    function shop_name(): string
    {
        return (string) setting('shop_name', 'Unique Solution');
    }
}

if (! function_exists('shop_address')) {
    function shop_address(): string
    {
        return (string) setting('shop_address', '');
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return (string) setting('currency_symbol', '₹');
    }
}

if (! function_exists('format_money')) {
    function format_money(mixed $amount): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return currency_symbol().number_format($value, 2, '.', ',');
    }
}

if (! function_exists('activity_log')) {
    function activity_log(string $action, string $module, ?string $description = null): void
    {
        app(ActivityLogService::class)->log($action, $module, $description);
    }
}
