<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    $this->skipped++;
                    continue;
                }

                $categoryName = trim((string) ($row['category'] ?? ''));
                $brandName = trim((string) ($row['brand'] ?? ''));
                $basePrice = (float) ($row['base_price'] ?? $row['price'] ?? 0);
                $sku = trim((string) ($row['sku'] ?? ''));
                $price = (float) ($row['price'] ?? $basePrice);
                $stock = (int) ($row['stock'] ?? $row['stock_quantity'] ?? 0);

                if ($categoryName === '') {
                    $this->skipped++;
                    continue;
                }

                $category = Category::query()->firstOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    [
                        'name' => $categoryName,
                        'status' => true,
                        'sort_order' => 0,
                    ]
                );

                $brandId = null;
                if ($brandName !== '') {
                    $brand = Brand::query()->firstOrCreate(
                        ['name' => $brandName],
                        ['status' => true]
                    );
                    $brandId = $brand->id;
                }

                $slug = $this->uniqueSlug($name);

                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brandId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $row['description'] ?? null,
                    'base_price' => $basePrice > 0 ? $basePrice : $price,
                    'status' => 'active',
                    'is_featured' => false,
                    'created_by' => Auth::id(),
                ]);

                if ($sku === '') {
                    $sku = 'US-'.Str::upper(Str::slug($name, '-')).'-'.Str::upper(Str::random(4));
                }

                $sku = $this->uniqueSku($sku);

                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'price' => $price > 0 ? $price : $product->base_price,
                    'discount_price' => null,
                    'stock_quantity' => max(0, $stock),
                    'low_stock_threshold' => 5,
                    'status' => true,
                ]);

                $this->imported++;
            }
        });

        if ($this->imported > 0) {
            activity_log(
                'imported',
                'products',
                "Imported {$this->imported} product(s) via Excel ({$this->skipped} skipped)."
            );
        }
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 1;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function uniqueSku(string $sku): string
    {
        $base = Str::upper(Str::slug($sku, '-')) ?: 'SKU';
        $candidate = $base;
        $counter = 1;

        while (ProductVariant::query()->where('sku', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
