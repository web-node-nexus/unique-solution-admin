<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }
                    $categoryId = (int) $this->input('category_id');
                    if (! $categoryId) {
                        return;
                    }
                    if (! Brand::query()->forCategory($categoryId)->whereKey($value)->exists()) {
                        $fail('The selected brand is not mapped to this category.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string', 'max:10000000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
            'warranty_info' => ['nullable', 'string', 'max:10000000'],
            'brand_policy_ids' => ['nullable', 'array'],
            'brand_policy_ids.*' => ['integer', 'exists:brand_policies,id'],
            'gallery_order' => ['nullable', 'array'],
            'gallery_order.*' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'is_featured' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.discount_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['sometimes', 'boolean'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => filter_var($this->input('is_featured', false), FILTER_VALIDATE_BOOLEAN),
        ]);

        if ($this->input('sale_price') === '' || $this->input('sale_price') === null) {
            $this->merge(['sale_price' => null]);
        }

        if (! $this->filled('status')) {
            $this->merge(['status' => 'inactive']);
        }

        // Derive product-level MRP / sale from the cheapest-MRP variant.
        $variants = $this->input('variants', []);
        if (is_array($variants) && $variants !== []) {
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
            }
            if ($bestSale !== null) {
                $this->merge(['sale_price' => $bestSale]);
            } elseif ($this->input('sale_price') === '' || $this->input('sale_price') === null) {
                $this->merge(['sale_price' => null]);
            }
        }
    }
}
