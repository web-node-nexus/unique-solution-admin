<?php

namespace App\Services;

use App\Models\SplashScreen;
use Illuminate\Support\Facades\Schema;

class SplashScreenService
{
    public function current(): ?SplashScreen
    {
        if (! Schema::hasTable('splash_screens')) {
            return null;
        }

        $today = SplashScreen::today();

        $scheduled = SplashScreen::query()
            ->where('is_default', false)
            ->where('is_enabled', true)
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();

        if ($scheduled) {
            return $scheduled;
        }

        return SplashScreen::query()->where('is_default', true)->first();
    }

    public function overlapping(string $startsOn, string $endsOn, ?int $ignoreId = null): ?SplashScreen
    {
        return SplashScreen::query()
            ->where('is_default', false)
            ->where('is_enabled', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('starts_on', '<=', $endsOn)
            ->whereDate('ends_on', '>=', $startsOn)
            ->orderBy('starts_on')
            ->first();
    }
}
