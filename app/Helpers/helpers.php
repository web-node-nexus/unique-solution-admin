<?php

use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Support\PublishingWindow;

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

if (! function_exists('admin_publish_toggle')) {
    /**
     * List-view on/off switch used across catalog tables.
     */
    function admin_publish_toggle(string $url, bool $active, bool $canToggle = true, ?string $state = null): string
    {
        $checked = $active ? ' checked' : '';
        $disabled = $canToggle ? '' : ' disabled';
        $label = $active ? 'Active' : 'Deactive';
        $stateBadge = '';

        if ($state && $state !== 'deactive' && $state !== 'live') {
            $badgeClass = PublishingWindow::badgeClass($state);
            $stateBadge = ' <span class="badge bg-'.$badgeClass.' publish-state-badge">'.e(PublishingWindow::label($state)).'</span>';
        }

        return '<div class="publish-toggle-cell">'
            .'<label class="publish-switch'.($canToggle ? '' : ' is-disabled').'">'
            .'<input type="checkbox" class="js-publish-toggle" data-url="'.e($url).'"'.($active ? ' data-active="1"' : ' data-active="0"').$checked.$disabled.'>'
            .'<span class="publish-slider"></span>'
            .'</label>'
            .'<span class="publish-toggle-label'.($active ? ' is-on' : '').'">'.$label.'</span>'
            .$stateBadge
            .'</div>';
    }
}

if (! function_exists('admin_duplicate_button')) {
    function admin_duplicate_button(string $url, string $confirm = 'Duplicate this item as a deactive copy?'): string
    {
        return '<form action="'.e($url).'" method="POST" class="d-inline me-1" data-confirm="'.e($confirm).'" data-confirm-title="Duplicate" data-confirm-button="Yes, duplicate">'
            .csrf_field()
            .'<button type="submit" class="btn btn-sm btn-outline-info" title="Duplicate"><i class="bi bi-copy"></i></button>'
            .'</form>';
    }
}
