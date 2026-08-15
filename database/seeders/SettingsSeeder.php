<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'shop_name' => 'Unique Solution',
            'shop_tagline' => 'आपकी अपनी दुकान',
            'shop_address' => 'Kargil Chowk, Megha Road, Kurud - 493663',
            'shop_logo' => null,
            'contact_number' => '+91 9876543210',
            'contact_email' => 'info@uniquesolution.com',
            'tax_percentage' => '18',
            'default_shipping_charge' => '99',
            'currency_symbol' => '₹',
            'razorpay_key' => '',
            'razorpay_secret' => '',
            'payu_key' => '',
            'payu_salt' => '',
            'notification_order_confirmed' => 'Hello {{customer_name}}, your order {{order_number}} is confirmed.',
            'notification_order_shipped' => 'Hello {{customer_name}}, your order {{order_number}} has been shipped.',
            'notification_order_delivered' => 'Hello {{customer_name}}, your order {{order_number}} has been delivered.',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
