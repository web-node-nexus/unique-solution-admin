<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attributes.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:attributes,name'],
            'type' => ['required', 'string', Rule::in(['text', 'dropdown', 'color-swatch', 'number'])],
            'status' => ['sometimes', 'boolean'],
            'values' => ['nullable', 'array'],
            'values.*.value' => ['required_with:values', 'string', 'max:255'],
            'values.*.extra_data' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge(['status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
