<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::query()
            ->whereIn('email', [
                'customer1@example.com',
                'customer2@example.com',
                'customer3@example.com',
            ])
            ->get()
            ->keyBy('email');

        $admin = User::query()->where('email', 'admin@uniquesolution.com')->first();
        $staff = User::query()->where('email', 'staff@uniquesolution.com')->first();

        $variants = ProductVariant::query()
            ->with(['product', 'attributeValues.attribute'])
            ->where('status', true)
            ->orderBy('id')
            ->take(12)
            ->get();

        if ($variants->count() < 4 || $customers->count() < 3) {
            $this->command?->warn('OrderSeeder skipped: insufficient catalog or customers.');

            return;
        }

        DB::transaction(function () use ($customers, $admin, $staff, $variants): void {
            // Order 1 — pending (COD / unpaid)
            $order1 = $this->createOrder([
                'order_number' => 'ORD-SEED-PENDING-001',
                'user' => $customers['customer1@example.com'],
                'order_status' => 'pending',
                'payment_status' => 'pending',
                'shipping_charge' => 99,
                'discount' => 0,
                'tax_percent' => 18,
                'notes' => 'Please call before delivery.',
                'items' => [
                    ['variant' => $variants[0], 'quantity' => 1],
                    ['variant' => $variants[1], 'quantity' => 1],
                ],
                'payment' => [
                    'payment_method' => 'cod',
                    'status' => 'pending',
                    'transaction_id' => null,
                    'paid_at' => null,
                ],
                'history' => [
                    ['status' => 'pending', 'remarks' => 'Order placed by customer.', 'changed_by' => null],
                ],
            ]);

            // Order 2 — confirmed + paid
            $order2 = $this->createOrder([
                'order_number' => 'ORD-SEED-CONFIRMED-002',
                'user' => $customers['customer2@example.com'],
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'shipping_charge' => 99,
                'discount' => 500,
                'tax_percent' => 18,
                'notes' => null,
                'items' => [
                    ['variant' => $variants[2], 'quantity' => 1],
                    ['variant' => $variants[3], 'quantity' => 2],
                ],
                'payment' => [
                    'payment_method' => 'razorpay',
                    'status' => 'paid',
                    'transaction_id' => 'pay_SEED_CONFIRMED_002',
                    'paid_at' => now()->subDays(2),
                    'gateway_response' => [
                        'razorpay_payment_id' => 'pay_SEED_CONFIRMED_002',
                        'razorpay_order_id' => 'order_SEED_002',
                        'status' => 'captured',
                    ],
                ],
                'history' => [
                    ['status' => 'pending', 'remarks' => 'Order placed by customer.', 'changed_by' => null, 'created_at' => now()->subDays(2)->subHour()],
                    ['status' => 'confirmed', 'remarks' => 'Payment received. Order confirmed.', 'changed_by' => $admin?->id, 'created_at' => now()->subDays(2)],
                ],
            ]);

            // Order 3 — shipped + paid + refund requested on one item
            $order3 = $this->createOrder([
                'order_number' => 'ORD-SEED-SHIPPED-003',
                'user' => $customers['customer3@example.com'],
                'order_status' => 'shipped',
                'payment_status' => 'paid',
                'shipping_charge' => 0,
                'discount' => 0,
                'tax_percent' => 18,
                'notes' => 'Gift wrap requested.',
                'items' => [
                    ['variant' => $variants[4], 'quantity' => 1],
                    ['variant' => $variants[5], 'quantity' => 1],
                ],
                'payment' => [
                    'payment_method' => 'payu',
                    'status' => 'paid',
                    'transaction_id' => 'payu_SEED_SHIPPED_003',
                    'paid_at' => now()->subDays(5),
                    'gateway_response' => [
                        'txnid' => 'payu_SEED_SHIPPED_003',
                        'status' => 'success',
                    ],
                ],
                'history' => [
                    ['status' => 'pending', 'remarks' => 'Order placed by customer.', 'changed_by' => null, 'created_at' => now()->subDays(5)],
                    ['status' => 'confirmed', 'remarks' => 'Payment verified.', 'changed_by' => $admin?->id, 'created_at' => now()->subDays(4)],
                    ['status' => 'shipped', 'remarks' => 'Handed over to courier. AWB: USIND123456', 'changed_by' => $staff?->id, 'created_at' => now()->subDay()],
                ],
            ]);

            $refundItem = $order3->items()->first();

            if ($refundItem) {
                Refund::query()->updateOrCreate(
                    [
                        'order_id' => $order3->id,
                        'order_item_id' => $refundItem->id,
                    ],
                    [
                        'requested_by' => $customers['customer3@example.com']->id,
                        'reason' => 'Received damaged packaging; requesting refund for this item.',
                        'refund_amount' => $refundItem->subtotal,
                        'status' => 'requested',
                        'admin_remarks' => null,
                        'processed_by' => null,
                        'processed_at' => null,
                    ]
                );
            }

            unset($order1, $order2);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createOrder(array $data): Order
    {
        /** @var User $user */
        $user = $data['user'];
        $address = $user->address ?? 'Kargil Chowk, Megha Road, Kurud - 493663';

        $lineItems = [];
        $subtotal = 0;

        foreach ($data['items'] as $item) {
            /** @var ProductVariant $variant */
            $variant = $item['variant'];
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) ($variant->discount_price ?? $variant->price);
            $lineSubtotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineSubtotal;

            $snapshot = [];
            foreach ($variant->attributeValues as $value) {
                $snapshot[$value->attribute?->name ?? 'Attribute'] = $value->value;
            }

            $lineItems[] = [
                'product_variant_id' => $variant->id,
                'product_name_snapshot' => $variant->product?->name ?? 'Product',
                'variant_details_snapshot' => $snapshot,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        $discount = (float) $data['discount'];
        $shipping = (float) $data['shipping_charge'];
        $taxable = max($subtotal - $discount, 0);
        $tax = round($taxable * ((float) $data['tax_percent'] / 100), 2);
        $total = round($taxable + $tax + $shipping, 2);

        $order = Order::query()->updateOrCreate(
            ['order_number' => $data['order_number']],
            [
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'shipping_charge' => $shipping,
                'total_amount' => $total,
                'payment_status' => $data['payment_status'],
                'order_status' => $data['order_status'],
                'shipping_address' => $address,
                'billing_address' => $address,
                'notes' => $data['notes'],
            ]
        );

        $order->items()->delete();
        $order->payments()->delete();
        $order->statusHistory()->delete();
        $order->refunds()->delete();

        foreach ($lineItems as $lineItem) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                ...$lineItem,
            ]);
        }

        $payment = $data['payment'];
        Payment::query()->create([
            'order_id' => $order->id,
            'payment_method' => $payment['payment_method'],
            'transaction_id' => $payment['transaction_id'] ?? null,
            'amount' => $total,
            'status' => $payment['status'],
            'gateway_response' => $payment['gateway_response'] ?? null,
            'paid_at' => $payment['paid_at'] ?? null,
        ]);

        foreach ($data['history'] as $history) {
            $entry = OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $history['status'],
                'remarks' => $history['remarks'],
                'changed_by' => $history['changed_by'] ?? null,
            ]);

            if (! empty($history['created_at'])) {
                $entry->forceFill(['created_at' => $history['created_at'], 'updated_at' => $history['created_at']])->save();
            }
        }

        return $order->fresh(['items']);
    }
}
