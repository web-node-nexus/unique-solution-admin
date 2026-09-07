<?php

namespace App\Providers;

use App\Events\OrderStatusUpdated;
use App\Listeners\SendOrderStatusNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            OrderStatusUpdated::class,
            SendOrderStatusNotification::class
        );

        Route::bind('staff', function (string $value): User {
            return User::query()
                ->role(['Super Admin', 'Manager', 'Staff', 'Support'])
                ->findOrFail($value);
        });

        Route::bind('customer', function (string $value): User {
            return User::query()
                ->customers()
                ->findOrFail($value);
        });

        $appUrl = (string) config('app.url');
        $appPath = parse_url($appUrl, PHP_URL_PATH) ?: '';
        // Only pin generated URLs when APP_URL includes a subdirectory (live /unique-solution).
        // Local artisan serve must follow the current host/port, otherwise CSS/JS 404.
        if ($appUrl !== '' && $appPath !== '' && $appPath !== '/') {
            \Illuminate\Support\Facades\URL::forceRootUrl(rtrim($appUrl, '/'));
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if (is_string($scheme) && $scheme !== '') {
                \Illuminate\Support\Facades\URL::forceScheme($scheme);
            }
        }
    }
}
