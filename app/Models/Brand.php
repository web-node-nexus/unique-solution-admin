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
}
