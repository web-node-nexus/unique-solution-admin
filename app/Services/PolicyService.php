<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandPolicy;
use App\Models\Product;
use App\Models\ProductPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class PolicyService
{
    public function __construct(protected ImageService $imageService) {}

    /**
     * Sync brand policies from admin form payload.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, UploadedFile|null>  $icons keyed by row index
     */
    public function syncBrandPolicies(Brand $brand, array $rows, array $icons = []): void
    {
        $keepIds = [];

        foreach (array_values($rows) as $index => $row) {
            if (! empty($row['remove'])) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $policy = null;
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($id > 0) {
                $policy = BrandPolicy::query()
                    ->where('brand_id', $brand->id)
                    ->whereKey($id)
                    ->first();
            }

            $data = [
                'title' => $title,
                'description' => isset($row['description']) ? (string) $row['description'] : null,
                'sort_order' => $index,
            ];

            $file = $icons[$index] ?? null;
            if ($file instanceof UploadedFile) {
                if ($policy?->icon) {
                    $this->imageService->delete($policy->icon);
                }
                $data['icon'] = $this->imageService->upload($file, 'policies/brands');
            }

            if ($policy) {
                $policy->update($data);
            } else {
                $policy = BrandPolicy::query()->create(array_merge($data, [
                    'brand_id' => $brand->id,
                ]));
            }

            $keepIds[] = $policy->id;
        }

        $toDelete = BrandPolicy::query()
            ->where('brand_id', $brand->id)
            ->when($keepIds !== [], fn ($q) => $q->whereNotIn('id', $keepIds))
            ->when($keepIds === [], fn ($q) => $q)
            ->get();

        foreach ($toDelete as $policy) {
            $this->imageService->delete($policy->icon);
            $policy->delete();
        }
    }

    /**
     * Sync product-specific policies from admin form payload.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, UploadedFile|null>  $icons keyed by row index
     */
    public function syncProductPolicies(Product $product, array $rows, array $icons = []): void
    {
        $keepIds = [];

        foreach (array_values($rows) as $index => $row) {
            if (! empty($row['remove'])) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $policy = null;
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($id > 0) {
                $policy = ProductPolicy::query()
                    ->where('product_id', $product->id)
                    ->whereKey($id)
                    ->first();
            }

            $data = [
                'title' => $title,
                'description' => isset($row['description']) ? (string) $row['description'] : null,
                'sort_order' => $index,
            ];

            $file = $icons[$index] ?? null;
            if ($file instanceof UploadedFile) {
                if ($policy?->icon) {
                    $this->imageService->delete($policy->icon);
                }
                $data['icon'] = $this->imageService->upload($file, 'policies/products');
            }

            if ($policy) {
                $policy->update($data);
            } else {
                $policy = ProductPolicy::query()->create(array_merge($data, [
                    'product_id' => $product->id,
                ]));
            }

            $keepIds[] = $policy->id;
        }

        $toDelete = ProductPolicy::query()
            ->where('product_id', $product->id)
            ->when($keepIds !== [], fn ($q) => $q->whereNotIn('id', $keepIds))
            ->get();

        foreach ($toDelete as $policy) {
            $this->imageService->delete($policy->icon);
            $policy->delete();
        }
    }

    /**
     * Policies shown on product detail in the app.
     *
     * @return list<array{id: int, title: string, description: string|null, icon_url: string|null, source: string}>
     */
    public function resolvedForProduct(Product $product): array
    {
        $items = collect();

        if ($product->use_brand_policies && $product->brand_id) {
            $brandPolicies = $product->relationLoaded('brand') && $product->brand?->relationLoaded('policies')
                ? $product->brand->policies
                : BrandPolicy::query()
                    ->where('brand_id', $product->brand_id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

            foreach ($brandPolicies as $policy) {
                $items->push($policy->toApiArray());
            }
        }

        $productPolicies = $product->relationLoaded('policies')
            ? $product->policies
            : ProductPolicy::query()
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

        foreach ($productPolicies as $policy) {
            $items->push($policy->toApiArray());
        }

        return $items->values()->all();
    }

    /**
     * @param  Collection<int, BrandPolicy|ProductPolicy>|iterable<BrandPolicy|ProductPolicy>  $policies
     * @return list<array{id: int, title: string, description: string|null, icon_url: string|null, source: string}>
     */
    public function mapCollection(iterable $policies, string $source): array
    {
        $out = [];
        foreach ($policies as $policy) {
            $row = $policy->toApiArray();
            $row['source'] = $source;
            $out[] = $row;
        }

        return $out;
    }
}
