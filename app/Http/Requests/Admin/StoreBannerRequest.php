<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('banners.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'link_type' => ['nullable', 'string'],
            'link_value' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'status' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable'],
            'ends_at' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $linkType = $this->input('link_type') ?: 'none';
        if (! in_array($linkType, ['none', 'category', 'brand', 'product'], true)) {
            $linkType = 'none';
        }

        $this->merge([
            'status' => $this->boolean('status'),
            'link_type' => $linkType,
            'link_value' => $linkType === 'none' ? null : $this->input('link_value'),
            'title' => $this->input('title') ?: '',
            'starts_at' => $this->filled('starts_at') ? $this->input('starts_at') : null,
            'ends_at' => $this->filled('ends_at') ? $this->input('ends_at') : null,
        ]);
    }
}
