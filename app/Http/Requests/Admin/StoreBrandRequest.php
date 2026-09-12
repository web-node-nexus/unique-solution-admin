<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('brands.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'warranty' => ['nullable', 'string', 'max:10000000'],
            'status' => ['sometimes', 'boolean'],
            'policies' => ['nullable', 'array'],
            'policies.*.id' => ['nullable', 'integer'],
            'policies.*.title' => ['nullable', 'string', 'max:120'],
            'policies.*.description' => ['nullable', 'string', 'max:5000'],
            'policies.*.remove' => ['nullable'],
            'policies.*.icon' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_ids' => 'categories',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('category_id') && ! $this->has('category_ids')) {
            $this->merge(['category_ids' => [(int) $this->input('category_id')]]);
        }

        if ($this->has('status')) {
            $this->merge(['status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
