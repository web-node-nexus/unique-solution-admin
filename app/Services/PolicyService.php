<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandPolicy;
use App\Models\Product;
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
     * Attach selected brand policies to a product (select / unselect only — no product-owned policies).
     *
     * @param  list<int|string>  $brandPolicyIds
     */
    public function syncProductBrandPolicies(Product $product, array $brandPolicyIds): void
    {
        $ids = collect($brandPolicyIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if (! $product->brand_id || $ids->isEmpty()) {
            $product->brandPolicies()->sync([]);

            return;
        }

        $validIds = BrandPolicy::query()
            ->where('brand_id', $product->brand_id)
            ->whereIn('id', $ids->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        $sync = [];
        foreach ($validIds as $index => $policyId) {
            $sync[$policyId] = ['sort_order' => $index];
        }

        $product->brandPolicies()->sync($sync);
    }

    /**
     * Policies shown on product detail in the app.
     *
     * @return list<array{id: int, title: string, description: string|null, icon_url: string|null, source: string}>
     */
    public function resolvedForProduct(Product $product): array
    {
        $policies = $product->relationLoaded('brandPolicies')
            ? $product->brandPolicies
            : $product->brandPolicies()->get();

        return $policies
            ->map(fn (BrandPolicy $policy) => $policy->toApiArray())
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, BrandPolicy>|iterable<BrandPolicy>  $policies
     * @return list<array{id: int, title: string, description: string|null, icon_url: string|null, source: string}>
     */
    public function mapCollection(iterable $policies, string $source = 'brand'): array
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
