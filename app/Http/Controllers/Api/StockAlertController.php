<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockAlertController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $product = Product::query()->whereKey($data['product_id'])->where('status', 'active')->firstOrFail();
        $variantId = $data['product_variant_id'] ?? null;

        if ($variantId) {
            $variant = ProductVariant::query()
                ->whereKey($variantId)
                ->where('product_id', $product->id)
                ->firstOrFail();

            if ((int) $variant->stock_quantity > 0) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['This variant is already in stock.'],
                ]);
            }
        }

        $user = $request->user();
        $email = $data['email'] ?? $user?->email;
        $phone = $data['phone'] ?? $user?->phone;

        if (! $email && ! $phone) {
            throw ValidationException::withMessages([
                'email' => ['Provide an email or phone for the stock alert.'],
            ]);
        }

        $alert = StockAlert::query()->updateOrCreate(
            [
                'user_id' => $user?->id,
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
            ],
            [
                'email' => $email,
                'phone' => $phone,
                'notified_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $alert->id,
                'product_id' => $alert->product_id,
                'product_variant_id' => $alert->product_variant_id,
            ],
            'message' => 'We will notify you when this is back in stock.',
        ], 201);
    }
}
