<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderService;
use App\Services\TwoFactorSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class DeliveryOrderController extends Controller
{
    /** @var list<string> */
    private const CLOSED_STATUSES = ['delivered', 'cancelled', 'returned'];

    public function index(Request $request): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $staffIds = $this->deliveryStaffIds($staff);
        $tab = strtolower((string) $request->query('tab', 'pending'));

        $query = Order::query()
            ->whereIn('assigned_to', $staffIds)
            ->with([
                'items.variant.images',
                'items.variant.product.images',
                'payments',
                'user:id,name,phone,email',
            ])
            ->latest('id');

        if ($tab === 'delivered') {
            $query->whereRaw('LOWER(order_status) = ?', ['delivered']);
        } else {
            $query->whereRaw('LOWER(order_status) NOT IN ('.$this->sqlList(self::CLOSED_STATUSES).')');
        }

        $orders = $query->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $orders->getCollection()->map(fn (Order $order) => $this->transform($order, false))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'staff_id' => $staff->id,
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $this->assertAssigned($order, $staff);

        $order->load([
            'items.variant.images',
            'items.variant.product.images',
            'items.variant.product.brand:id,name',
            'payments',
            'user:id,name,phone,email',
            'statusHistory',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transform($order, true),
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $this->assertAssigned($order, $staff);

        $data = $request->validate([
            'order_status' => ['required', 'string', 'in:shipped'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $current = strtolower(trim((string) $order->order_status));
        if (in_array($current, self::CLOSED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'order_status' => ['This order is no longer open for delivery updates.'],
            ]);
        }

        if (! in_array($current, ['processing', 'confirmed', 'pending'], true)) {
            throw ValidationException::withMessages([
                'order_status' => ['This order cannot be marked out for delivery.'],
            ]);
        }

        try {
            $updated = $orders->updateStatus($order, 'shipped', $data['remarks'] ?? null, $staff);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'order_status' => [$e->getMessage()],
            ]);
        }

        $updated->load([
            'items.variant.images',
            'items.variant.product.images',
            'items.variant.product.brand:id,name',
            'payments',
            'user:id,name,phone,email',
            'statusHistory',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marked out for delivery.',
            'data' => $this->transform($updated, true),
        ]);
    }

    /**
     * Send handover OTP to the customer's phone before delivery can be completed.
     */
    public function sendDeliveryOtp(Request $request, Order $order, TwoFactorSmsService $sms): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $this->assertAssigned($order, $staff);

        $current = strtolower(trim((string) $order->order_status));
        if ($current !== 'shipped') {
            throw ValidationException::withMessages([
                'order' => ['Mark the order out for delivery before sending the customer OTP.'],
            ]);
        }

        $order->loadMissing('user:id,phone,name');
        $phone = TwoFactorSmsService::normalizePhone((string) ($order->user?->phone ?? ''));
        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['Customer phone number is missing. Ask admin to update it.'],
            ]);
        }

        $throttleKey = "delivery-handover-send:{$order->id}";
        if (Cache::has($throttleKey)) {
            throw ValidationException::withMessages([
                'otp' => ['Please wait a minute before requesting another OTP.'],
            ]);
        }

        try {
            $sms->sendOtp($phone);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'otp' => [$e->getMessage()],
            ]);
        }

        Cache::put("delivery-handover-otp:{$order->id}", $phone, now()->addMinutes(10));
        Cache::put($throttleKey, 1, now()->addMinute());

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to the customer.',
            'data' => [
                'phone_masked' => $this->maskPhone($phone),
                'expires_in' => 600,
            ],
        ]);
    }

    /**
     * Verify customer OTP, then mark the order delivered.
     */
    public function confirmDelivery(Request $request, Order $order, TwoFactorSmsService $sms, OrderService $orders): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $this->assertAssigned($order, $staff);

        $data = $request->validate([
            'otp' => ['required', 'string', 'min:4', 'max:8'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $current = strtolower(trim((string) $order->order_status));
        if ($current !== 'shipped') {
            throw ValidationException::withMessages([
                'order' => ['Only out-for-delivery orders can be completed with OTP.'],
            ]);
        }

        $phone = Cache::get("delivery-handover-otp:{$order->id}");
        if (! $phone) {
            $order->loadMissing('user:id,phone');
            $phone = TwoFactorSmsService::normalizePhone((string) ($order->user?->phone ?? ''));
        }
        if (! $phone) {
            throw ValidationException::withMessages([
                'otp' => ['Send OTP to the customer first.'],
            ]);
        }

        try {
            $ok = $sms->verifyOtp((string) $phone, trim($data['otp']));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'otp' => [$e->getMessage()],
            ]);
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP. Ask the customer for the code from SMS.'],
            ]);
        }

        try {
            $updated = $orders->updateStatus(
                $order,
                'delivered',
                $data['remarks'] ?? 'Delivered after customer OTP verification',
                $staff,
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'order' => [$e->getMessage()],
            ]);
        }

        Cache::forget("delivery-handover-otp:{$order->id}");
        Cache::forget("delivery-handover-send:{$order->id}");

        $updated->load([
            'items.variant.images',
            'items.variant.product.images',
            'items.variant.product.brand:id,name',
            'payments',
            'user:id,name,phone,email',
            'statusHistory',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified. Order marked delivered.',
            'data' => $this->transform($updated, true),
        ]);
    }

    public function invoice(Request $request, Order $order): JsonResponse
    {
        $staff = $this->staffOrAbort($request);
        $this->assertAssigned($order, $staff);

        $url = URL::temporarySignedRoute(
            'api.v1.orders.invoice.download',
            now()->addMinutes(45),
            ['order' => $order->id]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_url' => $url,
                'order_number' => $order->order_number,
            ],
        ]);
    }

    private function staffOrAbort(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->hasRole('Staff', 'web'), 403);

        return $user;
    }

    private function assertAssigned(Order $order, User $staff): void
    {
        abort_unless(in_array((int) $order->assigned_to, $this->deliveryStaffIds($staff), true), 404);
    }

    /**
     * @return list<int>
     */
    private function deliveryStaffIds(User $staff): array
    {
        $phone = TwoFactorSmsService::normalizePhone((string) $staff->phone);

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

                return TwoFactorSmsService::normalizePhone((string) $user->phone) === $phone;
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

    private function maskPhone(string $phone10): string
    {
        return '******'.substr($phone10, -4);
    }

    private function orderItemImageUrl(OrderItem $item): ?string
    {
        $item->loadMissing(['variant.images', 'variant.product.images']);

        $variantImage = $item->variant?->images
            ?->sortBy(fn ($image) => (int) ($image->sort_order ?? 0))
            ->first()?->image_path;
        if ($url = $this->publicMediaUrl($variantImage)) {
            return $url;
        }

        $product = $item->variant?->product;
        if ($product) {
            if ($url = $product->primaryImageUrl()) {
                return $url;
            }

            $fallback = $product->images
                ?->sortBy(fn ($image) => (int) ($image->sort_order ?? 0))
                ->first()?->image_path;
            if ($url = $this->publicMediaUrl($fallback)) {
                return $url;
            }
        }

        $snapshot = is_array($item->variant_details_snapshot) ? $item->variant_details_snapshot : [];
        foreach (['image_url', 'image', 'thumbnail', 'thumbnail_url'] as $key) {
            if ($url = $this->publicMediaUrl($snapshot[$key] ?? null)) {
                return $url;
            }
        }

        return null;
    }

    private function publicMediaUrl(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Order $order, bool $detailed): array
    {
        $payment = $order->relationLoaded('payments') ? $order->payments->first() : null;
        $status = strtolower((string) $order->order_status);
        $firstItem = $order->relationLoaded('items') ? $order->items->first() : null;

        $payload = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $payment?->payment_method,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'tax' => (float) $order->tax,
            'shipping_charge' => (float) $order->shipping_charge,
            'total_amount' => (float) $order->total_amount,
            'shipping_address' => $order->shipping_address,
            'customer_name' => $order->relationLoaded('user') ? $order->user?->name : null,
            'customer_phone' => $order->relationLoaded('user') ? $order->user?->phone : null,
            'customer_email' => $order->relationLoaded('user') ? $order->user?->email : null,
            'items_count' => $order->items?->count() ?? 0,
            'thumbnail_url' => $firstItem ? $this->orderItemImageUrl($firstItem) : null,
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'can_mark_shipped' => in_array($status, ['processing', 'confirmed', 'pending'], true),
            'can_mark_delivered' => $status === 'shipped',
            'needs_delivery_otp' => $status === 'shipped',
        ];

        if ($detailed) {
            $payload['notes'] = $order->notes;
            $payload['billing_address'] = $order->billing_address;
            $payload['items'] = $order->items->map(function (OrderItem $item) {
                $snapshot = is_array($item->variant_details_snapshot) ? $item->variant_details_snapshot : [];
                $variantLabel = collect($snapshot['attributes'] ?? [])
                    ->map(fn ($attribute) => is_array($attribute) ? trim((string) ($attribute['value'] ?? '')) : '')
                    ->filter()
                    ->implode(' · ');
                $product = $item->variant?->product;

                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name_snapshot,
                    'brand_name' => $product?->brand?->name,
                    'sku' => $snapshot['sku'] ?? $item->variant?->sku,
                    'variant_label' => $variantLabel !== '' ? $variantLabel : null,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->price,
                    'total_price' => (float) $item->subtotal,
                    'image_url' => $this->orderItemImageUrl($item),
                    'product_id' => $product?->id,
                ];
            })->values();

            $payload['timeline'] = $order->relationLoaded('statusHistory')
                ? $order->statusHistory->sortBy('id')->values()->map(fn ($h) => [
                    'status' => $h->status,
                    'remarks' => $h->remarks,
                    'at' => optional($h->created_at)?->toIso8601String(),
                ])
                : [];

            $payload['customer_order_history'] = $order->user_id
                ? Order::query()
                    ->where('user_id', $order->user_id)
                    ->where('id', '!=', $order->id)
                    ->latest('id')
                    ->limit(8)
                    ->get(['id', 'order_number', 'order_status', 'total_amount', 'created_at', 'payment_status'])
                    ->map(fn (Order $past) => [
                        'id' => $past->id,
                        'order_number' => $past->order_number,
                        'order_status' => $past->order_status,
                        'payment_status' => $past->payment_status,
                        'total_amount' => (float) $past->total_amount,
                        'created_at' => optional($past->created_at)?->toIso8601String(),
                    ])->values()
                : [];
        }

        return $payload;
    }
}
