<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->whereNull('deleted_at'),
            ],
            'image' => image_upload_rules(),
            'sale_banner' => image_upload_rules(),
            'sale_title' => ['nullable', 'string', 'max:255'],
            'sale_subtitle' => ['nullable', 'string', 'max:255'],
            'sale_active' => ['sometimes', 'boolean'],
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
        ]);
    }
}
