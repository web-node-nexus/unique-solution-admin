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
        $order = $this->route('order');
        $current = $order instanceof \App\Models\Order
            ? (string) $order->order_status
            : 'pending';

        return [
            'order_status' => ['required', 'string', Rule::in(OrderService::allowedNextStatuses($current))],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_status.in' => 'You cannot move this order back to a previous status.',
        ];
    }
}
