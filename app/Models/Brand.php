<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'logo',
        'warranty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'brand_category')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(BrandPolicy::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Category ids this brand is mapped to (pivot, with legacy category_id fallback).
     *
     * @return list<int>
     */
    public function mappedCategoryIds(): array
    {
        $ids = $this->relationLoaded('categories')
            ? $this->categories->pluck('id')->all()
            : $this->categories()->pluck('categories.id')->all();

        if ($ids === [] && $this->category_id) {
            $ids = [(int) $this->category_id];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Brands linked to this category, its ancestors, or its descendants.
     */
    public function scopeForCategory(Builder $query, ?int $categoryId): Builder
    {
        if (! $categoryId) {
            return $query->whereRaw('0 = 1');
        }

        $ids = Category::relatedIds($categoryId);

        return $query->where(function (Builder $inner) use ($ids) {
            $inner->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $ids))
                ->orWhereIn('category_id', $ids);
        });
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        $this->categories()->sync($categoryIds);
        $this->update(['category_id' => $categoryIds[0] ?? null]);
    }

    /**
     * True when another brand with the same name is already mapped to any of these categories.
     *
     * @param  list<int>  $categoryIds
     */
    public static function nameTakenInCategories(string $name, array $categoryIds, ?int $ignoreBrandId = null): bool
    {
        return self::firstCategoryConflictingWithName($name, $categoryIds, $ignoreBrandId) !== null;
    }

    /**
     * Returns the first category id where this brand name is already used, or null.
     *
     * @param  list<int>  $categoryIds
     */
    public static function firstCategoryConflictingWithName(string $name, array $categoryIds, ?int $ignoreBrandId = null): ?int
    {
        $name = trim($name);
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if ($name === '' || $categoryIds === []) {
            return null;
        }

        foreach ($categoryIds as $categoryId) {
            $exists = static::query()
                ->when($ignoreBrandId, fn (Builder $q) => $q->where('id', '!=', $ignoreBrandId))
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->where(function (Builder $q) use ($categoryId) {
                    $q->whereHas('categories', fn (Builder $c) => $c->where('categories.id', $categoryId))
                        ->orWhere('category_id', $categoryId);
                })
                ->exists();

            if ($exists) {
                return $categoryId;
            }
        }

        return null;
    }
}
