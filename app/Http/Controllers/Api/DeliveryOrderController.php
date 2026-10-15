<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DeliveryOrderController extends Controller
{
    /** @var list<string> */
    private const PENDING_STATUSES = ['processing', 'shipped'];

    public function index(Request $request): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $tab = strtolower((string) $request->query('tab', 'pending'));

        $query = Order::query()
            ->where('assigned_to', $staff->id)
            ->with(['items', 'payments', 'user:id,name,phone'])
            ->latest('id');

        if ($tab === 'delivered') {
            $query->where('order_status', 'delivered');
        } else {
            $query->whereIn('order_status', self::PENDING_STATUSES);
        }

        $orders = $query->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $orders->getCollection()->map(fn (Order $order) => $this->transform($order, false))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        abort_unless((int) $order->assigned_to === (int) $staff->id, 404);

        $order->load(['items', 'payments', 'user:id,name,phone', 'statusHistory']);

        return response()->json([
            'success' => true,
            'data' => $this->transform($order, true),
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        abort_unless((int) $order->assigned_to === (int) $staff->id, 404);

        $data = $request->validate([
            'order_status' => ['required', 'string', 'in:shipped,delivered'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $status = (string) $data['order_status'];
        $current = (string) $order->order_status;

        if (! in_array($current, self::PENDING_STATUSES, true)) {
            throw ValidationException::withMessages([
                'order_status' => ['This order is no longer open for delivery updates.'],
            ]);
        }

        if ($status === 'shipped' && $current !== 'processing') {
            throw ValidationException::withMessages([
                'order_status' => ['Only processing orders can be marked out for delivery.'],
            ]);
        }

        try {
            $updated = $orders->updateStatus(
                $order,
                $status,
                $data['remarks'] ?? null,
                $staff,
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'order_status' => [$e->getMessage()],
            ]);
        }

        $updated->load(['items', 'payments', 'user:id,name,phone', 'statusHistory']);

        return response()->json([
            'success' => true,
            'message' => $status === 'delivered' ? 'Marked as delivered.' : 'Marked out for delivery.',
            'data' => $this->transform($updated, true),
        ]);
    }

    private function staffOrAbort(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->hasRole('Staff'), 403);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Order $order, bool $detailed): array
    {
        $payment = $order->relationLoaded('payments') ? $order->payments->first() : null;

        $payload = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $payment?->payment_method,
            'total_amount' => (float) $order->total_amount,
            'shipping_address' => $order->shipping_address,
            'customer_name' => $order->relationLoaded('user') ? $order->user?->name : null,
            'customer_phone' => $order->relationLoaded('user') ? $order->user?->phone : null,
            'items_count' => $order->items?->count() ?? 0,
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'can_mark_shipped' => $order->order_status === 'processing',
            'can_mark_delivered' => in_array($order->order_status, self::PENDING_STATUSES, true),
        ];

        if ($detailed) {
            $payload['notes'] = $order->notes;
            $payload['items'] = $order->items->map(function (OrderItem $item) {
                $snapshot = is_array($item->variant_details_snapshot) ? $item->variant_details_snapshot : [];
                $variantLabel = collect($snapshot['attributes'] ?? [])
                    ->map(fn ($attribute) => is_array($attribute) ? trim((string) ($attribute['value'] ?? '')) : '')
                    ->filter()
                    ->implode(' · ');

                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name_snapshot,
                    'variant_label' => $variantLabel !== '' ? $variantLabel : null,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->price,
                    'total_price' => (float) $item->subtotal,
                ];
            })->values();
            $payload['timeline'] = $order->relationLoaded('statusHistory')
                ? $order->statusHistory->sortBy('id')->values()->map(fn ($h) => [
                    'status' => $h->status,
                    'remarks' => $h->remarks,
                    'at' => optional($h->created_at)?->toIso8601String(),
                ])
                : [];
        }

        return $payload;
    }
}
