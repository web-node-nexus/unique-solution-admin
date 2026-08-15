<?php

namespace App\Http\Requests\Admin;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attributes.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'max:255'],
            'color_mode' => ['nullable', Rule::in(['picker', 'image'])],
            'extra_data' => ['nullable', 'array'],
            'extra_data.hex' => ['nullable', 'string', 'max:20'],
            'swatch_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $attribute = $this->route('attribute');
            if (! $attribute instanceof Attribute || $attribute->type !== 'color-swatch') {
                return;
            }

            $mode = $this->input('color_mode', 'picker');
            if ($mode === 'image' && ! $this->hasFile('swatch_image')) {
                $validator->errors()->add('swatch_image', 'Upload a product photo for this color.');
            }
        });
    }
}
