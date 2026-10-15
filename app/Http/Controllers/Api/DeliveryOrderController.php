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

    /** @var list<string> */
    private const CLOSED_STATUSES = ['delivered', 'cancelled', 'returned'];

    public function index(Request $request): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $staffIds = $this->deliveryStaffIds($staff);
        $tab = strtolower((string) $request->query('tab', 'pending'));

        $query = Order::query()
            ->whereIn('assigned_to', $staffIds)
            ->with(['items', 'payments', 'user:id,name,phone'])
            ->latest('id');

        if ($tab === 'delivered') {
            $query->whereRaw('LOWER(order_status) = ?', ['delivered']);
        } else {
            // Anything assigned and not closed — processing/shipped/confirmed/pending.
            $query->whereRaw('LOWER(order_status) NOT IN ('.$this->sqlList(self::CLOSED_STATUSES).')');
        }

        $orders = $query->paginate(30);

        $assignedTotal = Order::query()->whereIn('assigned_to', $staffIds)->count();
        $byStatus = Order::query()
            ->whereIn('assigned_to', $staffIds)
            ->selectRaw('LOWER(order_status) as status, COUNT(*) as total')
            ->groupByRaw('LOWER(order_status)')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => $orders->getCollection()->map(fn (Order $order) => $this->transform($order, false))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'staff_id' => $staff->id,
                'staff_ids' => $staffIds,
                'assigned_total' => $assignedTotal,
                'by_status' => $byStatus,
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        abort_unless(in_array((int) $order->assigned_to, $this->deliveryStaffIds($staff), true), 404);

        $order->load(['items', 'payments', 'user:id,name,phone', 'statusHistory']);

        return response()->json([
            'success' => true,
            'data' => $this->transform($order, true),
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        abort_unless(in_array((int) $order->assigned_to, $this->deliveryStaffIds($staff), true), 404);

        $data = $request->validate([
            'order_status' => ['required', 'string', 'in:shipped,delivered'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $status = (string) $data['order_status'];
        $current = strtolower(trim((string) $order->order_status));

        if (in_array($current, self::CLOSED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'order_status' => ['This order is no longer open for delivery updates.'],
            ]);
        }

        if ($status === 'shipped' && ! in_array($current, ['processing', 'confirmed', 'pending'], true)) {
            throw ValidationException::withMessages([
                'order_status' => ['This order cannot be marked out for delivery.'],
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
        abort_unless($user && $user->is_active && $user->hasRole('Staff', 'web'), 403);

        return $user;
    }

    /**
     * All active Staff accounts that share this partner's phone (normalized).
     *
     * @return list<int>
     */
    private function deliveryStaffIds(User $staff): array
    {
        $phone = \App\Services\TwoFactorSmsService::normalizePhone((string) $staff->phone);

        $ids = User::query()
            ->role('Staff')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->filter(function (User $user) use ($phone, $staff) {
                if ((int) $user->id === (int) $staff->id) {
                    return true;
                }
                if (! $phone) {
                    return false;
                }

                return \App\Services\TwoFactorSmsService::normalizePhone((string) $user->phone) === $phone;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids !== [] ? $ids : [(int) $staff->id];
    }

    /**
     * @param  list<string>  $values
     */
    private function sqlList(array $values): string
    {
        return collect($values)
            ->map(fn (string $value) => "'".str_replace("'", "''", strtolower($value))."'")
            ->implode(', ');
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
            'can_mark_shipped' => in_array(strtolower((string) $order->order_status), ['processing', 'confirmed', 'pending'], true),
            'can_mark_delivered' => ! in_array(strtolower((string) $order->order_status), self::CLOSED_STATUSES, true),
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
