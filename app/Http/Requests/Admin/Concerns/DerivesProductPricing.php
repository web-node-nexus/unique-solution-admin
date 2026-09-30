<?php

namespace App\Http\Requests\Admin\Concerns;

trait DerivesProductPricing
{
    /**
     * Product-level MRP / sale come from the cheapest-MRP variant.
     */
    protected function derivePricingFromVariants(): void
    {
        $variants = $this->input('variants', []);
        if (! is_array($variants) || $variants === []) {
            return;
        }

        $bestMrp = null;
        $bestSale = null;
        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                continue;
            }
            if (! isset($variant['price']) || $variant['price'] === '' || ! is_numeric($variant['price'])) {
                continue;
            }
            $mrp = (float) $variant['price'];
            if ($bestMrp !== null && $mrp >= $bestMrp) {
                continue;
            }
            $bestMrp = $mrp;
            $saleRaw = $variant['discount_price'] ?? null;
            if ($saleRaw !== null && $saleRaw !== '' && is_numeric($saleRaw)) {
                $sale = (float) $saleRaw;
                $bestSale = ($sale >= 0 && $sale <= $mrp) ? $sale : null;
            } else {
                $bestSale = null;
            }
        }

        if ($bestMrp !== null) {
            $this->merge(['base_price' => $bestMrp]);
            $this->merge(['sale_price' => $bestSale]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function stagedUploadRules(): array
    {
        return [
            'gallery_uploads' => ['nullable', 'array', 'max:40'],
            'gallery_uploads.*' => ['nullable', 'string', 'max:2000'],
            'gallery_order.*' => ['nullable', 'string', 'max:40'],
            'variants.*.uploaded_images' => ['nullable', 'array', 'max:20'],
            'variants.*.uploaded_images.*' => ['nullable', 'string', 'max:2000'],
            'variants.*.images_managed' => ['nullable', 'boolean'],
            'variants.*.keep_image_ids' => ['nullable', 'array'],
            'variants.*.keep_image_ids.*' => ['integer'],
            'variants.*.image_order' => ['nullable', 'array', 'max:40'],
            'variants.*.image_order.*' => ['nullable', 'string', 'max:40'],
        ];
    }
}
