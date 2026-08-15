<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::query()
            ->whereIn('email', [
                'customer1@example.com',
                'customer2@example.com',
                'customer3@example.com',
                'customer4@example.com',
                'customer5@example.com',
            ])
            ->get()
            ->values();

        $products = Product::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->take(6)
            ->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('ReviewSeeder skipped: missing customers or products.');

            return;
        }

        $reviews = [
            [
                'product' => $products[0],
                'user' => $customers[0],
                'rating' => 5,
                'comment' => 'Excellent phone — display and battery life are outstanding for the price.',
                'status' => 'approved',
                'admin_reply' => 'Thank you for shopping with Unique Solution!',
            ],
            [
                'product' => $products[1] ?? $products[0],
                'user' => $customers[1],
                'rating' => 4,
                'comment' => 'Good performance overall. Delivery from Kurud store was quick.',
                'status' => 'approved',
                'admin_reply' => null,
            ],
            [
                'product' => $products[2] ?? $products[0],
                'user' => $customers[2],
                'rating' => 3,
                'comment' => 'Product is fine but packaging could be better.',
                'status' => 'pending',
                'admin_reply' => null,
            ],
            [
                'product' => $products[3] ?? $products[0],
                'user' => $customers[3] ?? $customers[0],
                'rating' => 5,
                'comment' => 'Cooling is powerful. Installation team was professional.',
                'status' => 'approved',
                'admin_reply' => 'Glad you are happy with your AC!',
            ],
            [
                'product' => $products[4] ?? $products[0],
                'user' => $customers[4] ?? $customers[1],
                'rating' => 2,
                'comment' => 'Expected better build quality. Waiting for support response.',
                'status' => 'pending',
                'admin_reply' => null,
            ],
        ];

        foreach ($reviews as $review) {
            Review::query()->updateOrCreate(
                [
                    'product_id' => $review['product']->id,
                    'user_id' => $review['user']->id,
                ],
                [
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                    'admin_reply' => $review['admin_reply'],
                    'status' => $review['status'],
                ]
            );
        }
    }
}
