<?php

namespace App\Http\Requests\Admin;

use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_status' => ['required', 'string', Rule::in(OrderService::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
