<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly RazorpayService $razorpay,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => collect($orders->items())->map(fn (Order $o) => $this->transformOrder($o, false))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['items.variant.product', 'payments', 'statusHistory', 'refunds']);

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order, true),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = $user->orders();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'stats' => [
                    'orders_count' => (clone $orders)->count(),
                    'pending_count' => (clone $orders)->whereIn('order_status', ['pending', 'confirmed', 'processing'])->count(),
                    'delivered_count' => (clone $orders)->where('order_status', 'delivered')->count(),
                    'addresses_count' => $user->addresses()->count(),
                ],
                'recent_orders' => $user->orders()
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Order $o) => $this->transformOrder($o, false)),
            ],
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'billing_address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'in:cod,razorpay,upi'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $method = $data['payment_method'];
        if (in_array($method, ['razorpay', 'upi'], true) && ! $this->razorpay->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => ['Online payment is not available right now. Choose Cash on delivery.'],
            ]);
        }

        $user = $request->user();
        $addressText = $data['shipping_address'] ?? null;

        if (! empty($data['address_id'])) {
            $address = $user->addresses()->whereKey($data['address_id'])->firstOrFail();
            $addressText = trim(implode(', ', array_filter([
                $address->label ? "({$address->label})" : null,
                $address->address,
                $address->city,
                $address->state,
                $address->pincode,
            ])));
        }

        if (! $addressText) {
            throw ValidationException::withMessages([
                'shipping_address' => ['Please provide a delivery address.'],
            ]);
        }

        $billing = $data['billing_address'] ?? $addressText;

        $order = DB::transaction(function () use ($data, $user, $addressText, $billing, $method) {
            $lines = [];
            $subtotal = 0.0;

            foreach ($data['items'] as $item) {
                $variant = null;
                if (! empty($item['product_variant_id'])) {
                    $variant = ProductVariant::query()
                        ->with(['product', 'attributeValues.attribute'])
                        ->whereKey($item['product_variant_id'])
                        ->first();
                } elseif (! empty($item['product_id'])) {
                    $variant = ProductVariant::query()
                        ->with(['product', 'attributeValues.attribute'])
                        ->where('product_id', $item['product_id'])
                        ->where('status', true)
                        ->orderBy('id')
                        ->first();
                }

                if (! $variant || ! $variant->product || $variant->product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => ['One or more products are unavailable.'],
                    ]);
                }

                $qty = (int) $item['quantity'];
                if ($variant->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for {$variant->product->name}."],
                    ]);
                }

                $unit = $variant->discount_price !== null && (float) $variant->discount_price > 0
                    ? (float) $variant->discount_price
                    : (float) $variant->price;
                if ($unit <= 0 && $variant->product->sale_price) {
                    $unit = (float) $variant->product->sale_price;
                }
                if ($unit <= 0) {
                    $unit = (float) $variant->product->base_price;
                }

                $lineSubtotal = round($unit * $qty, 2);
                $subtotal += $lineSubtotal;

                $lines[] = [
                    'variant' => $variant,
                    'quantity' => $qty,
                    'price' => $unit,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = 0.0;
            $couponCode = null;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()
                    ->whereRaw('LOWER(code) = ?', [strtolower(trim($data['coupon_code']))])
                    ->where('status', true)
                    ->first();

                if (! $coupon) {
                    throw ValidationException::withMessages(['coupon_code' => ['Invalid coupon code.']]);
                }
                if ($coupon->expiry_date && $coupon->expiry_date->isPast()) {
                    throw ValidationException::withMessages(['coupon_code' => ['Coupon has expired.']]);
                }
                if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
                    throw ValidationException::withMessages(['coupon_code' => ['Coupon usage limit reached.']]);
                }
                if ($subtotal < (float) $coupon->min_order_value) {
                    throw ValidationException::withMessages([
                        'coupon_code' => ['Minimum order value not met for this coupon.'],
                    ]);
                }

                $discount = $coupon->discount_type === 'percent'
                    ? round($subtotal * ((float) $coupon->discount_value / 100), 2)
                    : min((float) $coupon->discount_value, $subtotal);
                $couponCode = $coupon->code;
                $coupon->increment('used_count');
            }

            $taxPct = (float) Setting::get('tax_percentage', 0);
            $shipping = (float) Setting::get('default_shipping_charge', 0);
            $taxable = max($subtotal - $discount, 0);
            $tax = round($taxable * ($taxPct / 100), 2);
            $total = round($taxable + $tax + $shipping, 2);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'shipping_charge' => $shipping,
                'total_amount' => $total,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'shipping_address' => $addressText,
                'billing_address' => $billing,
                'notes' => trim(($data['notes'] ?? '').($couponCode ? "\nCoupon: {$couponCode}" : '')),
            ]);

            foreach ($lines as $line) {
                /** @var ProductVariant $variant */
                $variant = $line['variant'];
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name_snapshot' => $variant->product->name,
                    'variant_details_snapshot' => [
                        'sku' => $variant->sku,
                        'attributes' => $variant->attributeValues->map(fn ($av) => [
                            'name' => $av->attribute?->name,
                            'value' => $av->value,
                        ])->values()->all(),
                    ],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'subtotal' => $line['subtotal'],
                ]);

                $variant->decrement('stock_quantity', $line['quantity']);
            }

            $order->statusHistory()->create([
                'status' => 'pending',
                'remarks' => 'Order placed from mobile app',
                'changed_by' => $user->id,
            ]);

            $payToken = Str::random(40);
            $gateway = [
                'source' => 'mobile_app',
                'pay_token' => $payToken,
            ];

            if (in_array($method, ['razorpay', 'upi'], true)) {
                $rz = $this->razorpay->createOrder((float) $total, $order->order_number);
                $gateway['razorpay_order_id'] = $rz['id'];
                $gateway['razorpay_amount'] = $rz['amount'];
                $gateway['razorpay_currency'] = $rz['currency'];
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => $method === 'upi' ? 'upi' : $method,
                'amount' => $total,
                'status' => 'pending',
                'transaction_id' => null,
                'gateway_response' => $gateway,
            ]);

            $user->cartItems()->delete();

            return $order->load(['items', 'payments']);
        });

        $payload = $this->transformOrder($order, true);
        $payment = $order->payments->first();
        $gateway = is_array($payment?->gateway_response) ? $payment->gateway_response : [];

        if (in_array($method, ['razorpay', 'upi'], true)) {
            $payload['payment_session'] = [
                'provider' => 'razorpay',
                'key' => $this->razorpay->key(),
                'razorpay_order_id' => $gateway['razorpay_order_id'] ?? null,
                'amount' => (int) ($gateway['razorpay_amount'] ?? round($order->total_amount * 100)),
                'currency' => $gateway['razorpay_currency'] ?? 'INR',
                'payment_url' => url('/pay/'.$order->id.'?t='.urlencode((string) ($gateway['pay_token'] ?? ''))),
                'name' => (string) Setting::get('shop_name', 'Unique Solution'),
                'description' => 'Order '.$order->order_number,
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact' => $user->phone,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
            'message' => $method === 'cod'
                ? 'Order placed successfully. Pay on delivery.'
                : 'Order created. Complete payment to confirm.',
        ], 201);
    }

    public function verifyPayment(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = $order->payments()->latest('id')->first();
        if (! $payment) {
            throw ValidationException::withMessages(['payment' => ['Payment record missing.']]);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'success' => true,
                'data' => $this->transformOrder($order->load(['items', 'payments', 'statusHistory']), true),
                'message' => 'Payment already confirmed.',
            ]);
        }

        $gateway = is_array($payment->gateway_response) ? $payment->gateway_response : [];
        if (($gateway['razorpay_order_id'] ?? null) !== $data['razorpay_order_id']) {
            throw ValidationException::withMessages(['razorpay_order_id' => ['Order mismatch.']]);
        }

        if (! $this->razorpay->verifySignature(
            $data['razorpay_order_id'],
            $data['razorpay_payment_id'],
            $data['razorpay_signature']
        )) {
            $payment->update(['status' => 'failed']);
            $order->update(['payment_status' => 'failed']);

            throw ValidationException::withMessages(['razorpay_signature' => ['Payment verification failed.']]);
        }

        $gateway['razorpay_payment_id'] = $data['razorpay_payment_id'];
        $gateway['razorpay_signature'] = $data['razorpay_signature'];

        $payment->update([
            'status' => 'paid',
            'transaction_id' => $data['razorpay_payment_id'],
            'paid_at' => now(),
            'gateway_response' => $gateway,
        ]);
        $order->update(['payment_status' => 'paid']);

        if ($order->order_status === 'pending') {
            $this->orders->updateStatus($order, 'confirmed', 'Payment received online', $request->user());
        }

        $order->refresh()->load(['items', 'payments', 'statusHistory']);

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order, true),
            'message' => 'Payment confirmed.',
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! in_array($order->order_status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'order' => ['This order can no longer be cancelled.'],
            ]);
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->load('items');
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->increment('stock_quantity', (int) $item->quantity);
                }
            }

            $this->orders->updateStatus(
                $order,
                'cancelled',
                $data['reason'] ?? 'Cancelled by customer',
                $request->user()
            );

            $payment = $order->payments()->latest('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
                $order->update(['payment_status' => 'failed']);
            }
        });

        $order->refresh()->load(['items', 'payments', 'statusHistory']);

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order, true),
            'message' => 'Order cancelled.',
        ]);
    }

    public function reorder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['items.variant.product']);
        $added = [];

        foreach ($order->items as $item) {
            $variant = $item->variant;
            $product = $variant?->product;
            if (! $product || $product->status !== 'active') {
                continue;
            }

            $mrp = (float) ($product->base_price ?? $item->price);
            $sale = $product->sale_price !== null ? (float) $product->sale_price : null;
            $attr = collect($item->variant_details_snapshot['attributes'] ?? [])
                ->map(fn ($a) => $a['value'] ?? null)
                ->filter()
                ->implode(' / ');

            $added[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => (int) $item->quantity,
                'name' => $item->product_name_snapshot ?: $product->name,
                'image_url' => null,
                'mrp' => $mrp,
                'sale_price' => $sale,
                'attribute_label' => $attr ?: null,
            ];

            $existingQuery = $request->user()->cartItems()->where('product_id', $product->id);
            if ($variant?->id) {
                $existingQuery->where('product_variant_id', $variant->id);
            } else {
                $existingQuery->whereNull('product_variant_id');
            }
            $existing = $existingQuery->first();

            if ($existing) {
                $existing->update([
                    'quantity' => min(50, $existing->quantity + (int) $item->quantity),
                ]);
            } else {
                $request->user()->cartItems()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => (int) $item->quantity,
                    'name' => $item->product_name_snapshot ?: $product->name,
                    'image_url' => null,
                    'mrp' => $mrp,
                    'sale_price' => $sale,
                    'attribute_label' => $attr ?: null,
                ]);
            }
        }

        if ($added === []) {
            throw ValidationException::withMessages([
                'order' => ['No items from this order are available to reorder.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $added,
            'message' => 'Items added to your cart.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrder(Order $order, bool $detailed): array
    {
        $payload = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'tax' => (float) $order->tax,
            'shipping_charge' => (float) $order->shipping_charge,
            'total_amount' => (float) $order->total_amount,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'items_count' => $order->items_count ?? $order->items?->count(),
            'can_cancel' => in_array($order->order_status, ['pending', 'confirmed'], true),
            'can_reorder' => true,
            'can_return' => $order->order_status === 'delivered',
            'can_invoice' => true,
            'can_review' => $order->order_status === 'delivered',
        ];

        if ($detailed) {
            $payload['shipping_address'] = $order->shipping_address;
            $payload['billing_address'] = $order->billing_address;
            $payload['notes'] = $order->notes;
            $payload['items'] = $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name_snapshot,
                'variant' => $item->variant_details_snapshot,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
                'product_variant_id' => $item->product_variant_id,
                'product_id' => $item->variant?->product_id,
            ])->values();
            $payload['timeline'] = $order->statusHistory
                ? $order->statusHistory->sortBy('id')->values()->map(fn ($h) => [
                    'status' => $h->status,
                    'remarks' => $h->remarks,
                    'at' => optional($h->created_at)?->toIso8601String(),
                ])
                : [];
            $payload['refunds'] = $order->relationLoaded('refunds')
                ? $order->refunds->map(fn ($r) => [
                    'id' => $r->id,
                    'reason' => $r->reason,
                    'refund_amount' => (float) $r->refund_amount,
                    'status' => $r->status,
                    'admin_remarks' => $r->admin_remarks,
                    'created_at' => optional($r->created_at)?->toIso8601String(),
                ])->values()
                : [];
            $payment = $order->payments->first();
            $gateway = is_array($payment?->gateway_response) ? $payment->gateway_response : [];
            $payload['payment'] = $payment ? [
                'method' => $payment->payment_method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'needs_payment' => $payment->status === 'pending' && in_array($payment->payment_method, ['razorpay', 'upi'], true),
                'payment_url' => ($payment->status === 'pending' && ! empty($gateway['pay_token']))
                    ? url('/pay/'.$order->id.'?t='.urlencode((string) $gateway['pay_token']))
                    : null,
            ] : null;
        }

        return $payload;
    }

    public function requestReturn(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'refund_amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        if ($order->order_status !== 'delivered') {
            throw ValidationException::withMessages([
                'order' => ['Returns are only available after delivery.'],
            ]);
        }

        $existing = $order->refunds()
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'order' => ['A return request is already in progress for this order.'],
            ]);
        }

        if (! empty($data['order_item_id'])) {
            $item = $order->items()->whereKey($data['order_item_id'])->firstOrFail();
            $amount = (float) ($data['refund_amount'] ?? $item->subtotal);
        } else {
            $amount = (float) ($data['refund_amount'] ?? $order->total_amount);
        }

        $refund = $order->refunds()->create([
            'order_item_id' => $data['order_item_id'] ?? null,
            'requested_by' => $request->user()->id,
            'reason' => $data['reason'],
            'refund_amount' => min($amount, (float) $order->total_amount),
            'status' => 'pending',
        ]);

        $order->statusHistory()->create([
            'status' => 'return_requested',
            'remarks' => $data['reason'],
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $refund->id,
                'status' => $refund->status,
                'refund_amount' => (float) $refund->refund_amount,
                'reason' => $refund->reason,
            ],
            'message' => 'Return request submitted. We will review it shortly.',
        ], 201);
    }

    public function invoice(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'api.v1.orders.invoice.download',
            now()->addMinutes(30),
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

    public function downloadInvoice(Request $request, Order $order): \Illuminate\Http\Response
    {
        $order->load([
            'user',
            'items.variant.product',
            'items.variant.attributeValues',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.invoice', [
            'order' => $order,
            'shopName' => shop_name(),
            'shopAddress' => shop_address(),
        ]);

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }
}
