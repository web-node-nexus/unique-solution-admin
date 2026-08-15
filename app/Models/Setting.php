<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'settings.'.$key;

        $value = Cache::rememberForever($cacheKey, function () use ($key) {
            return static::query()->where('key', $key)->value('value');
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): Setting
    {
        $stored = is_array($value) || is_object($value)
            ? json_encode($value)
            : (string) $value;

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored]
        );

        Cache::forget('settings.'.$key);
        Cache::forever('settings.'.$key, $setting->value);

        return $setting;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public static function getMany(array $keys): array
    {
        $settings = static::query()->whereIn('key', $keys)->pluck('value', 'key');

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $settings->get($key);
        }

        return $result;
    }
}
