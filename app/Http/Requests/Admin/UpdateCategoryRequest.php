<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'sale_banner' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'sale_title' => ['nullable', 'string', 'max:255'],
            'sale_subtitle' => ['nullable', 'string', 'max:255'],
            'sale_active' => ['sometimes', 'boolean'],
            'remove_sale_banner' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'attribute_ids' => ['nullable', 'array'],
            'attribute_ids.*' => ['integer', 'exists:attributes,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge(['status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN)]);
        }

        $this->merge([
            'sale_active' => filter_var($this->input('sale_active'), FILTER_VALIDATE_BOOLEAN),
            'remove_sale_banner' => filter_var($this->input('remove_sale_banner'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
