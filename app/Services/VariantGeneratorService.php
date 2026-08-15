<?php

namespace App\Services;

use App\Models\AttributeValue;
use Illuminate\Support\Str;

class VariantGeneratorService
{
    /**
     * Build the cartesian product of attribute value ID groups.
     *
     * @param  array<int, array<int, int|string>>  $attributeValueGroups
     * @return array<int, array<int, int>>
     */
    public function generateCombinations(array $attributeValueGroups): array
    {
        $groups = array_values(array_filter(
            array_map(
                fn (array $group): array => array_values(array_map('intval', $group)),
                $attributeValueGroups
            ),
            fn (array $group): bool => $group !== []
        ));

        if ($groups === []) {
            return [];
        }

        $combinations = [[]];

        foreach ($groups as $group) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($group as $valueId) {
                    $next[] = [...$combination, $valueId];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * Suggest a SKU like US-SAMSUNG-GALAXY-RED-128GB.
     *
     * @param  array<int, string>  $attributeValueLabels
     */
    public function suggestSku(string $productName, array $attributeValueLabels = []): string
    {
        $parts = ['US'];

        $nameParts = preg_split('/[\s\-_\/]+/', $productName) ?: [];
        foreach ($nameParts as $part) {
            $slug = $this->skuSegment($part);
            if ($slug !== '') {
                $parts[] = $slug;
            }
        }

        foreach ($attributeValueLabels as $label) {
            $slug = $this->skuSegment((string) $label);
            if ($slug !== '') {
                $parts[] = $slug;
            }
        }

        $sku = implode('-', array_filter($parts));

        return Str::upper($sku !== '' ? $sku : 'US-PRODUCT');
    }

    /**
     * Build editable variant rows from selected attribute value IDs grouped by attribute ID.
     *
     * @param  array<int|string, array<int, int|string>>  $selectedValuesByAttributeId
     * @return array<int, array{attribute_value_ids: array<int, int>, labels: array<int, string>, suggested_sku: string, label: string}>
     */
    public function buildVariantRows(array $selectedValuesByAttributeId, string $productName = ''): array
    {
        $groups = [];

        foreach ($selectedValuesByAttributeId as $valueIds) {
            $ids = array_values(array_filter(array_map('intval', (array) $valueIds)));
            if ($ids !== []) {
                $groups[] = $ids;
            }
        }

        $combinations = $this->generateCombinations($groups);

        if ($combinations === []) {
            return [];
        }

        $allIds = collect($combinations)->flatten()->unique()->values()->all();

        $values = AttributeValue::query()
            ->with('attribute')
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($combinations as $combination) {
            $labels = [];

            foreach ($combination as $valueId) {
                $attributeValue = $values->get($valueId);
                $labels[] = $attributeValue?->value ?? (string) $valueId;
            }

            $suggestedSku = $this->suggestSku($productName, $labels);

            $rows[] = [
                'attribute_value_ids' => array_values($combination),
                'labels' => $labels,
                'label' => implode(' / ', $labels),
                'suggested_sku' => $suggestedSku,
            ];
        }

        return $rows;
    }

    protected function skuSegment(string $value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '';
        $cleaned = trim($cleaned, '-');

        return Str::upper($cleaned);
    }
}
