<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_order_value' => 1999,
                'max_uses' => 500,
                'used_count' => 12,
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'status' => true,
            ],
            [
                'code' => 'FLAT500',
                'discount_type' => 'fixed',
                'discount_value' => 500,
                'min_order_value' => 9999,
                'max_uses' => 200,
                'used_count' => 5,
                'expiry_date' => now()->addMonths(3)->toDateString(),
                'status' => true,
            ],
            [
                'code' => 'FESTIVE15',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_order_value' => 4999,
                'max_uses' => 100,
                'used_count' => 0,
                'expiry_date' => now()->addMonth()->toDateString(),
                'status' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
