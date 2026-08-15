<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'discount_price',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'variant_attribute_values',
            'variant_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(VariantImage::class, 'variant_id');
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'variant_id');
    }

    protected function displayName(): CastAttribute
    {
        return CastAttribute::get(function (): string {
            $productName = $this->relationLoaded('product')
                ? ($this->product?->name ?? '')
                : ($this->product()->value('name') ?? '');

            $values = $this->relationLoaded('attributeValues')
                ? $this->attributeValues
                : $this->attributeValues()->get();

            $parts = $values
                ->pluck('value')
                ->filter()
                ->values()
                ->all();

            if ($parts === []) {
                return $productName !== '' ? $productName : ($this->sku ?? 'Variant');
            }

            $joined = implode(' / ', $parts);

            return $productName !== '' ? "{$productName} — {$joined}" : $joined;
        });
    }
}
