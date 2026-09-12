<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    /**
     * @var list<string>
     */
    public const STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'returned',
    ];

    /**
     * Admin board tabs (UI). "assigned" maps to processing + shipped.
     *
     * @var list<string>
     */
    public const BOARD_TABS = [
        'pending',
        'confirmed',
        'assigned',
        'delivered',
        'cancelled',
        'returned',
    ];

    /**
     * @return array<string, array{title: string, subtitle: string, badge: string, color: string}>
     */
    public static function boardMeta(): array
    {
        return [
            'pending' => [
                'title' => '1. पेंडिंग ऑर्डर्स (Pending Orders)',
                'subtitle' => 'नए ऑर्डर्स यहाँ दिखेंगे। यहाँ से आप ऑर्डर कन्फर्म या कैंसिल कर सकते हैं।',
                'badge' => 'ऑर्डर्स',
                'color' => '#ea580c',
            ],
            'confirmed' => [
                'title' => '2. कन्फर्म्ड ऑर्डर्स (Confirmed Orders)',
                'subtitle' => 'कन्फर्म हो चुके ऑर्डर्स। इन्हें असाइन या आगे प्रोसेस कर सकते हैं।',
                'badge' => 'ऑर्डर्स',
                'color' => '#2563eb',
            ],
            'assigned' => [
                'title' => '3. असाइन्ड ऑर्डर्स (Assigned Orders)',
                'subtitle' => 'प्रोसेसिंग / शिप हो रहे ऑर्डर्स यहाँ दिखेंगे।',
                'badge' => 'ऑर्डर्स',
                'color' => '#7c3aed',
            ],
            'delivered' => [
                'title' => '4. डिलीवर्ड ऑर्डर्स (Delivered Orders)',
                'subtitle' => 'सफलतापूर्वक डिलीवर हुए ऑर्डर्स।',
                'badge' => 'ऑर्डर्स',
                'color' => '#16a34a',
            ],
            'cancelled' => [
                'title' => '5. कैंसिल्ड ऑर्डर्स (Cancelled Orders)',
                'subtitle' => 'कैंसिल किए गए ऑर्डर्स की सूची।',
                'badge' => 'ऑर्डर्स',
                'color' => '#dc2626',
            ],
            'returned' => [
                'title' => '6. रिटर्न्ड ऑर्डर्स (Returned Orders)',
                'subtitle' => 'रिटर्न अनुरोध वाले ऑर्डर्स।',
                'badge' => 'ऑर्डर्स',
                'color' => '#a855f7',
            ],
            'all' => [
                'title' => 'सभी ऑर्डर्स (All Orders)',
                'subtitle' => 'सभी स्टेटस के ऑर्डर्स यहाँ दिखेंगे।',
                'badge' => 'ऑर्डर्स',
                'color' => '#0f172a',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function statusesForBoardTab(string $tab): array
    {
        return match ($tab) {
            'assigned' => ['processing', 'shipped'],
            default => [$tab],
        };
    }

    public static function boardTabForStatus(string $status): string
    {
        return match ($status) {
            'processing', 'shipped' => 'assigned',
            default => $status,
        };
    }

    public static function tabLabel(string $tab): string
    {
        return match ($tab) {
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'assigned' => 'Assigned',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
            default => ucfirst($tab),
        };
    }

    public function updateStatus(
        Order $order,
        string $status,
        ?string $remarks,
        User $user
    ): Order {
        $status = strtolower(trim($status));

        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Invalid order status [{$status}].");
        }

        return DB::transaction(function () use ($order, $status, $remarks, $user) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $oldStatus = (string) $order->order_status;

            if ($oldStatus === $status) {
                return $order;
            }

            $order->update(['order_status' => $status]);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $status,
                'remarks' => $remarks,
                'changed_by' => $user->id,
            ]);

            event(new OrderStatusUpdated($order->fresh(), $oldStatus, $status));

            activity_log(
                'status_updated',
                'orders',
                "Order {$order->order_number}: {$oldStatus} → {$status}"
            );

            return $order->fresh(['statusHistory', 'items']);
        });
    }
}
