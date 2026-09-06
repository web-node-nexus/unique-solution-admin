<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->wishlists()
            ->with(['product.images', 'product.brand', 'product.category'])
            ->latest('id')
            ->get()
            ->map(fn (Wishlist $row) => $this->transformProduct($row->product))
            ->filter()
            ->values();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $request->user()->wishlists()->firstOrCreate([
            'product_id' => $data['product_id'],
        ]);

        return $this->index($request);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $request->user()->wishlists()->where('product_id', $productId)->delete();

        return $this->index($request);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_ids' => ['present', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $user = $request->user();
        $ids = collect($data['product_ids'])->unique()->values()->all();

        $user->wishlists()->whereNotIn('product_id', $ids ?: [0])->delete();

        foreach ($ids as $productId) {
            $user->wishlists()->firstOrCreate(['product_id' => $productId]);
        }

        return $this->index($request);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transformProduct(?Product $product): ?array
    {
        if (! $product || $product->status !== 'active') {
            return null;
        }

        $primary = $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'base_price' => (float) $product->base_price,
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'mrp' => (float) $product->base_price,
            'brand' => $product->brand?->name,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'category' => $product->category?->name,
            'is_featured' => (bool) $product->is_featured,
            'image_url' => $primary
                ? Storage::disk('public')->url($primary->image_path)
                : null,
        ];
    }
}
