<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'link_type',
        'link_value',
        'sort_order',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    protected function imageUrl(): CastAttribute
    {
        return CastAttribute::get(function (): ?string {
            if (! $this->image_path) {
                return null;
            }

            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                return $this->image_path;
            }

            return Storage::disk('public')->url($this->image_path);
        });
    }

    /**
     * App-friendly deep-link payload.
     *
     * @return array{type: string, value: string|null, label: string|null}
     */
    public function deepLink(): array
    {
        $label = null;

        if ($this->link_type === 'category' && $this->link_value) {
            $label = Category::query()->whereKey($this->link_value)->value('name');
        }

        if ($this->link_type === 'brand' && $this->link_value) {
            $label = Brand::query()->whereKey($this->link_value)->value('name');
        }

        if ($this->link_type === 'product' && $this->link_value) {
            $label = Product::query()->whereKey($this->link_value)->value('name');
        }

        return [
            'type' => $this->link_type ?: 'none',
            'value' => $this->link_value,
            'label' => $label,
        ];
    }
}
