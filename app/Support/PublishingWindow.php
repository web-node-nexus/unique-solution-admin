<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class PublishingWindow
{
    /**
     * @return 'deactive'|'scheduled'|'live'|'expired'
     */
    public static function state(bool $active, mixed $startsAt, mixed $endsAt, ?CarbonInterface $now = null): string
    {
        if (! $active) {
            return 'deactive';
        }

        $now = $now ? Carbon::instance($now) : now();
        $start = self::asCarbon($startsAt, false);
        $end = self::asCarbon($endsAt, true);

        if ($start && $start->gt($now)) {
            return 'scheduled';
        }

        if ($end && $end->lt($now)) {
            return 'expired';
        }

        return 'live';
    }

    public static function isVisibleOnApp(bool $active, mixed $startsAt, mixed $endsAt, ?CarbonInterface $now = null): bool
    {
        return self::state($active, $startsAt, $endsAt, $now) === 'live';
    }

    public static function label(string $state): string
    {
        return match ($state) {
            'live' => 'Live on app',
            'scheduled' => 'Scheduled',
            'expired' => 'Ended',
            default => 'Deactive',
        };
    }

    public static function badgeClass(string $state): string
    {
        return match ($state) {
            'live' => 'success',
            'scheduled' => 'info',
            'expired' => 'dark',
            default => 'secondary',
        };
    }

    public static function asCarbon(mixed $value, bool $endOfDay = false): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            $carbon = Carbon::instance($value);
        } else {
            try {
                $carbon = Carbon::parse((string) $value);
            } catch (\Throwable) {
                return null;
            }
        }

        if ($endOfDay && strlen((string) $value) <= 10 && ! str_contains((string) $value, ':')) {
            $carbon = $carbon->endOfDay();
        }

        return $carbon;
    }
}
