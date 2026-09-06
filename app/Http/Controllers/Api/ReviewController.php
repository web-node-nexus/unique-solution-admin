<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        abort_unless($product->status === 'active', 404);

        $reviews = Review::query()
            ->with('user:id,name')
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest('id')
            ->paginate(20);

        $stats = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as count, AVG(rating) as average')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'average' => round((float) ($stats->average ?? 0), 1),
                    'count' => (int) ($stats->count ?? 0),
                ],
                'reviews' => collect($reviews->items())->map(fn (Review $r) => $this->transform($r))->values(),
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status === 'active', 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $verified = $this->hasPurchased($user->id, $product->id);

        if (! $verified) {
            throw ValidationException::withMessages([
                'product' => ['You can review products after a delivered purchase.'],
            ]);
        }

        $review = Review::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => 'pending',
                'admin_reply' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $this->transform($review->load('user:id,name')),
            'message' => 'Review submitted. It will appear after approval.',
        ], 201);
    }

    private function hasPurchased(int $userId, int $productId): bool
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('order_status', 'delivered')
            ->whereHas('items.variant', fn ($v) => $v->where('product_id', $productId))
            ->exists();
    }

    private function transform(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'admin_reply' => $review->admin_reply,
            'status' => $review->status,
            'user_name' => $review->user?->name ?? 'Customer',
            'created_at' => optional($review->created_at)?->toIso8601String(),
        ];
    }
}
