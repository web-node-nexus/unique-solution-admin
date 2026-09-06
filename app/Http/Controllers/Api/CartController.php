<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->cartItems()
            ->latest('id')
            ->get()
            ->map(fn (CartItem $item) => $this->transform($item))
            ->values();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.image_url' => ['nullable', 'string', 'max:500'],
            'items.*.mrp' => ['required', 'numeric', 'min:0'],
            'items.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.attribute_label' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $user->cartItems()->delete();

        foreach ($data['items'] as $item) {
            $user->cartItems()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'name' => $item['name'],
                'image_url' => $item['image_url'] ?? null,
                'mrp' => $item['mrp'],
                'sale_price' => $item['sale_price'] ?? null,
                'attribute_label' => $item['attribute_label'] ?? null,
            ]);
        }

        return $this->index($request);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->user()->cartItems()->delete();

        return response()->json(['success' => true, 'data' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(CartItem $item): array
    {
        return [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
            'name' => $item->name,
            'image_url' => $item->image_url,
            'mrp' => (float) $item->mrp,
            'sale_price' => $item->sale_price !== null ? (float) $item->sale_price : null,
            'attribute_label' => $item->attribute_label,
        ];
    }
}
