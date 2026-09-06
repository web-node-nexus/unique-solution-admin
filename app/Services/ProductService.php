<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VariantImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductService
{
    public function __construct(
        protected ImageService $imageService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Product
    {
        return DB::transaction(function () use ($data, $user) {
            $product = Product::query()->create([
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['name']),
                'description' => $data['description'] ?? null,
                'base_price' => $data['base_price'],
                'sale_price' => $data['sale_price'] ?? null,
                'warranty_info' => $data['warranty_info'] ?? null,
                'status' => $data['status'] ?? 'active',
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncProductImages($product, $data['images'] ?? [], $data['gallery_order'] ?? []);
            $this->createVariants($product, $data['variants'] ?? []);

            activity_log('created', 'products', "Created product #{$product->id}: {$product->name}");

            return $product->fresh(['images', 'variants.attributeValues', 'variants.images']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'brand_id' => array_key_exists('brand_id', $data) ? $data['brand_id'] : $product->brand_id,
                'name' => $data['name'] ?? $product->name,
                'slug' => isset($data['slug']) || isset($data['name'])
                    ? $this->uniqueSlug($data['slug'] ?? $data['name'] ?? $product->name, $product->id)
                    : $product->slug,
                'description' => array_key_exists('description', $data) ? $data['description'] : $product->description,
                'base_price' => $data['base_price'] ?? $product->base_price,
                'sale_price' => array_key_exists('sale_price', $data) ? $data['sale_price'] : $product->sale_price,
                'warranty_info' => array_key_exists('warranty_info', $data) ? $data['warranty_info'] : $product->warranty_info,
                'status' => $data['status'] ?? $product->status,
                'is_featured' => array_key_exists('is_featured', $data)
                    ? (bool) $data['is_featured']
                    : $product->is_featured,
                'meta_title' => array_key_exists('meta_title', $data) ? $data['meta_title'] : $product->meta_title,
                'meta_description' => array_key_exists('meta_description', $data)
                    ? $data['meta_description']
                    : $product->meta_description,
            ]);

            if (array_key_exists('images', $data)
                || array_key_exists('remove_image_ids', $data)
                || array_key_exists('primary_image_id', $data)
                || array_key_exists('gallery_order', $data)) {
                $this->syncProductGallery(
                    $product,
                    $data['images'] ?? [],
                    $data['remove_image_ids'] ?? [],
                    isset($data['primary_image_id']) ? (int) $data['primary_image_id'] : null,
                    $data['gallery_order'] ?? []
                );
            }

            if (array_key_exists('variants', $data)) {
                $this->syncVariants($product, $data['variants'] ?? []);
            }

            activity_log('updated', 'products', "Updated product #{$product->id}: {$product->name}");

            return $product->fresh(['images', 'variants.attributeValues', 'variants.images']);
        });
    }

    public function clone(Product $product, User $user, bool $copyStock = false): Product
    {
        $product->loadMissing(['images', 'variants.attributeValues', 'variants.images']);

        return DB::transaction(function () use ($product, $user, $copyStock) {
            $name = $product->name.' (Copy)';

            $clone = Product::query()->create([
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'description' => $product->description,
                'base_price' => $product->base_price,
                'sale_price' => $product->sale_price,
                'warranty_info' => $product->warranty_info,
                'status' => $product->status,
                'is_featured' => false,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'created_by' => $user->id,
            ]);

            foreach ($product->images as $image) {
                ProductImage::query()->create([
                    'product_id' => $clone->id,
                    'image_path' => $image->image_path,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                ]);
            }

            foreach ($product->variants as $variant) {
                $newSku = $this->uniqueSku($variant->sku.'-COPY');

                $newVariant = ProductVariant::query()->create([
                    'product_id' => $clone->id,
                    'sku' => $newSku,
                    'price' => $variant->price,
                    'discount_price' => $variant->discount_price,
                    'stock_quantity' => $copyStock ? $variant->stock_quantity : 0,
                    'low_stock_threshold' => $variant->low_stock_threshold,
                    'weight' => $variant->weight,
                    'status' => $variant->status,
                ]);

                $attributeValueIds = $variant->attributeValues->pluck('id')->all();
                if ($attributeValueIds !== []) {
                    $newVariant->attributeValues()->sync($attributeValueIds);
                }

                foreach ($variant->images as $variantImage) {
                    VariantImage::query()->create([
                        'variant_id' => $newVariant->id,
                        'image_path' => $variantImage->image_path,
                    ]);
                }
            }

            activity_log(
                'cloned',
                'products',
                "Cloned product #{$product->id} into #{$clone->id}: {$clone->name}"
            );

            return $clone->fresh(['images', 'variants.attributeValues', 'variants.images']);
        });
    }

    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $product->delete();

            activity_log('deleted', 'products', "Soft deleted product #{$product->id}: {$product->name}");

            return true;
        });
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkStatus(array $ids, string $status): int
    {
        $allowed = ['active', 'inactive', 'draft'];
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException("Invalid product status [{$status}].");
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        $updated = Product::query()->whereIn('id', $ids)->update(['status' => $status]);

        activity_log(
            'bulk_status',
            'products',
            "Set status [{$status}] on {$updated} product(s)."
        );

        return $updated;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        $products = Product::query()->whereIn('id', $ids)->get();

        foreach ($products as $product) {
            $product->delete();
        }

        $count = $products->count();

        activity_log('bulk_deleted', 'products', "Soft deleted {$count} product(s).");

        return $count;
    }

    /**
     * @param  array<int, mixed>  $images
     * @param  array<int, string>  $galleryOrder
     */
    protected function syncProductImages(Product $product, array $images, array $galleryOrder = []): void
    {
        $created = [];

        foreach (array_values($images) as $index => $image) {
            $path = $this->resolveImagePath($image, 'products');

            if (! $path) {
                continue;
            }

            $created[$index] = ProductImage::query()->create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $index === 0,
                'sort_order' => $index,
            ]);
        }

        $this->applyGalleryOrder($product, $galleryOrder, $created);
    }

    /**
     * Append new images, soft-delete selected existing ones, set primary and sort.
     *
     * @param  array<int, mixed>  $newImages
     * @param  array<int, int|string>  $removeIds
     * @param  array<int, string>  $galleryOrder
     */
    protected function syncProductGallery(
        Product $product,
        array $newImages,
        array $removeIds = [],
        ?int $primaryImageId = null,
        array $galleryOrder = []
    ): void {
        $product->loadMissing('images');

        $removeIds = array_map('intval', $removeIds);

        foreach ($product->images as $existing) {
            if (in_array((int) $existing->id, $removeIds, true)) {
                $this->imageService->delete($existing->image_path);
                $existing->delete();
            }
        }

        $created = [];
        $startOrder = (int) ($product->images()->max('sort_order') ?? -1);

        foreach (array_values($newImages) as $index => $image) {
            $path = $this->resolveImagePath($image, 'products');

            if (! $path) {
                continue;
            }

            $created[$index] = ProductImage::query()->create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => false,
                'sort_order' => $startOrder + $index + 1,
            ]);
        }

        $this->applyGalleryOrder($product, $galleryOrder, $created, $primaryImageId);
    }

    /**
     * @param  array<int, string>  $galleryOrder
     * @param  array<int, ProductImage>  $newByIndex
     */
    protected function applyGalleryOrder(
        Product $product,
        array $galleryOrder,
        array $newByIndex = [],
        ?int $primaryImageId = null
    ): void {
        $product->unsetRelation('images');
        $images = $product->images()->orderBy('sort_order')->orderBy('id')->get();

        if ($images->isEmpty()) {
            return;
        }

        $ordered = [];
        $seen = [];

        foreach ($galleryOrder as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            $image = null;
            if (str_starts_with($token, 'existing:')) {
                $id = (int) substr($token, 9);
                $image = $images->firstWhere('id', $id);
            } elseif (str_starts_with($token, 'new:')) {
                $index = (int) substr($token, 4);
                $image = $newByIndex[$index] ?? null;
            }

            if ($image && ! isset($seen[$image->id])) {
                $ordered[] = $image;
                $seen[$image->id] = true;
            }
        }

        foreach ($images as $image) {
            if (! isset($seen[$image->id])) {
                $ordered[] = $image;
            }
        }

        foreach ($ordered as $index => $image) {
            $image->update(['sort_order' => $index]);
        }

        $orderedIds = collect($ordered)->pluck('id')->all();

        if ($primaryImageId && in_array($primaryImageId, $orderedIds, true)) {
            foreach ($ordered as $image) {
                $image->update(['is_primary' => (int) $image->id === $primaryImageId]);
            }

            return;
        }

        foreach ($ordered as $index => $image) {
            $image->update(['is_primary' => $index === 0]);
        }
    }

    /**
     * @param  array<int, mixed>  $images
     */
    protected function replaceProductImages(Product $product, array $images): void
    {
        $product->loadMissing('images');

        foreach ($product->images as $existing) {
            $this->imageService->delete($existing->image_path);
            $existing->delete();
        }

        $this->syncProductImages($product, $images);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function createVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variantData) {
            $this->storeVariant($product, $variantData);
        }
    }

    /**
     * Carefully sync variants: update by id, create new, remove missing.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function syncVariants(Product $product, array $variants): void
    {
        $product->loadMissing(['variants.images', 'variants.attributeValues']);

        $keptIds = [];

        foreach ($variants as $variantData) {
            $variantId = isset($variantData['id']) ? (int) $variantData['id'] : null;
            $existing = $variantId
                ? $product->variants->firstWhere('id', $variantId)
                : null;

            if ($existing) {
                $this->updateExistingVariant($existing, $variantData);
                $keptIds[] = $existing->id;
                continue;
            }

            $created = $this->storeVariant($product, $variantData);
            $keptIds[] = $created->id;
        }

        $toDelete = $product->variants->whereNotIn('id', $keptIds);

        foreach ($toDelete as $variant) {
            foreach ($variant->images as $image) {
                $this->imageService->delete($image->image_path);
                $image->delete();
            }

            $variant->attributeValues()->detach();
            $variant->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    protected function storeVariant(Product $product, array $variantData): ProductVariant
    {
        $sku = $this->uniqueSku((string) ($variantData['sku'] ?? Str::upper(Str::random(8))));

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => $sku,
            'price' => $variantData['price'] ?? $product->base_price,
            'discount_price' => $variantData['discount_price'] ?? null,
            'stock_quantity' => (int) ($variantData['stock_quantity'] ?? 0),
            'low_stock_threshold' => (int) ($variantData['low_stock_threshold'] ?? 5),
            'weight' => $variantData['weight'] ?? null,
            'status' => array_key_exists('status', $variantData)
                ? (bool) $variantData['status']
                : true,
        ]);

        $attributeValueIds = array_values(array_map(
            'intval',
            (array) ($variantData['attribute_value_ids'] ?? [])
        ));

        if ($attributeValueIds !== []) {
            $variant->attributeValues()->sync($attributeValueIds);
        }

        $this->attachVariantImage($variant, $variantData['image'] ?? null);

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    protected function updateExistingVariant(ProductVariant $variant, array $variantData): void
    {
        $sku = (string) ($variantData['sku'] ?? $variant->sku);

        $variant->update([
            'sku' => $this->uniqueSku($sku, $variant->id),
            'price' => $variantData['price'] ?? $variant->price,
            'discount_price' => array_key_exists('discount_price', $variantData)
                ? $variantData['discount_price']
                : $variant->discount_price,
            'stock_quantity' => array_key_exists('stock_quantity', $variantData)
                ? (int) $variantData['stock_quantity']
                : $variant->stock_quantity,
            'low_stock_threshold' => array_key_exists('low_stock_threshold', $variantData)
                ? (int) $variantData['low_stock_threshold']
                : $variant->low_stock_threshold,
            'weight' => array_key_exists('weight', $variantData)
                ? $variantData['weight']
                : $variant->weight,
            'status' => array_key_exists('status', $variantData)
                ? (bool) $variantData['status']
                : $variant->status,
        ]);

        if (array_key_exists('attribute_value_ids', $variantData)) {
            $attributeValueIds = array_values(array_map(
                'intval',
                (array) $variantData['attribute_value_ids']
            ));
            $variant->attributeValues()->sync($attributeValueIds);
        }

        if (array_key_exists('image', $variantData) && $variantData['image']) {
            foreach ($variant->images as $image) {
                $this->imageService->delete($image->image_path);
                $image->delete();
            }

            $this->attachVariantImage($variant, $variantData['image']);
        }
    }

    protected function attachVariantImage(ProductVariant $variant, mixed $image): void
    {
        $path = $this->resolveImagePath($image, 'variants');

        if (! $path) {
            return;
        }

        VariantImage::query()->create([
            'variant_id' => $variant->id,
            'image_path' => $path,
        ]);
    }

    protected function resolveImagePath(mixed $image, string $folder): ?string
    {
        if ($image instanceof UploadedFile) {
            return $this->imageService->upload($image, $folder);
        }

        if (is_array($image)) {
            if (($image['file'] ?? null) instanceof UploadedFile) {
                return $this->imageService->upload($image['file'], $folder);
            }

            if (! empty($image['path']) && is_string($image['path'])) {
                return $image['path'];
            }

            if (! empty($image['image_path']) && is_string($image['image_path'])) {
                return $image['image_path'];
            }
        }

        if (is_string($image) && $image !== '') {
            return $image;
        }

        return null;
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $counter = 1;

        while (
            Product::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function uniqueSku(string $sku, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::slug($sku, '-')) ?: 'SKU';
        $candidate = $base;
        $counter = 1;

        while (
            ProductVariant::query()
                ->where('sku', $candidate)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
