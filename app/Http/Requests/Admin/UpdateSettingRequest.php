<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_tagline' => ['nullable', 'string', 'max:255'],
            'shop_address' => ['nullable', 'string', 'max:500'],
            'shop_logo' => ['nullable', 'image', 'max:2048'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'razorpay_key' => ['nullable', 'string', 'max:255'],
            'razorpay_secret' => ['nullable', 'string', 'max:255'],
            'payu_key' => ['nullable', 'string', 'max:255'],
            'payu_salt' => ['nullable', 'string', 'max:255'],
            'notification_order_confirmed' => ['nullable', 'string', 'max:1000'],
            'notification_order_shipped' => ['nullable', 'string', 'max:1000'],
            'notification_order_delivered' => ['nullable', 'string', 'max:1000'],
            'fcm_project_id' => ['nullable', 'string', 'max:255'],
            'fcm_server_key' => ['nullable', 'string', 'max:500'],
            'fcm_credentials_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
