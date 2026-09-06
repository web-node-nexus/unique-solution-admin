<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'image',
        'sale_banner',
        'sale_title',
        'sale_subtitle',
        'sale_active',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sale_active' => 'boolean',
            'sort_order' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'category_attributes')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_category')
            ->withTimestamps();
    }

    /**
     * Selected category + ancestors + nested descendants.
     * Used so "Android Phones" still matches brands mapped to "Mobile Phones".
     *
     * @return list<int>
     */
    public static function relatedIds(int $categoryId): array
    {
        $ids = static::treeIds($categoryId);
        $parentId = static::query()->whereKey($categoryId)->value('parent_id');

        while ($parentId) {
            $ids[] = (int) $parentId;
            $parentId = static::query()->whereKey($parentId)->value('parent_id');
        }

        return array_values(array_unique($ids));
    }

    /**
     * This category id plus all nested descendants (breadth-first).
     *
     * @return list<int>
     */
    public static function treeIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $frontier = [$categoryId];

        while ($frontier !== []) {
            $children = static::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();
            $frontier = $children;
            foreach ($children as $childId) {
                $ids[] = (int) $childId;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function imageUrl(): CastAttribute
    {
        return CastAttribute::get(function (): ?string {
            return $this->resolvePublicUrl($this->image);
        });
    }

    protected function saleBannerUrl(): CastAttribute
    {
        return CastAttribute::get(function (): ?string {
            return $this->resolvePublicUrl($this->sale_banner);
        });
    }

    /**
     * Whether this category should show a sale banner in the app.
     */
    public function hasActiveSaleBanner(): bool
    {
        return $this->sale_active && filled($this->sale_banner);
    }

    private function resolvePublicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
