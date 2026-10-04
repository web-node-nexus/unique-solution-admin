<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SplashScreen extends Model
{
    public const TIMEZONE = 'Asia/Kolkata';

    protected $fillable = [
        'is_default',
        'image_path',
        'starts_on',
        'ends_on',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public static function today(): string
    {
        return now(self::TIMEZONE)->toDateString();
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * live | scheduled | expired | disabled | default
     */
    public function state(?string $today = null): string
    {
        if ($this->is_default) {
            return 'default';
        }

        if (! $this->is_enabled) {
            return 'disabled';
        }

        $today ??= self::today();
        $start = $this->starts_on?->toDateString();
        $end = $this->ends_on?->toDateString();

        if ($start && $today < $start) {
            return 'scheduled';
        }

        if ($end && $today > $end) {
            return 'expired';
        }

        return 'live';
    }
};
