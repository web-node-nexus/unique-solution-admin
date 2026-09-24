<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('brands.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Unique per category (not globally) — see withValidator().
            'name' => ['required', 'string', 'max:255'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $brand = $this->route('brand');
            $brandId = $brand instanceof Brand ? $brand->id : (int) $brand;

            $name = trim((string) $this->input('name'));
            $categoryIds = array_values(array_unique(array_filter(array_map(
                'intval',
                (array) $this->input('category_ids', [])
            ))));

            $conflictId = Brand::firstCategoryConflictingWithName($name, $categoryIds, $brandId ?: null);
            if ($conflictId === null) {
                return;
            }

            $categoryName = Category::query()->whereKey($conflictId)->value('name') ?: 'this category';
            $validator->errors()->add(
                'name',
                "\"{$name}\" is already added in {$categoryName}. Same brand can be used in other categories, but not twice in the same category."
            );
        });
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
