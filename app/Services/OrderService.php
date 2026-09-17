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
                'title' => '1. Pending Orders',
                'subtitle' => 'New orders appear here. Confirm or cancel them from this list.',
                'badge' => 'orders',
                'color' => '#ea580c',
            ],
            'confirmed' => [
                'title' => '2. Confirmed Orders',
                'subtitle' => 'Confirmed orders ready to assign or process further.',
                'badge' => 'orders',
                'color' => '#2563eb',
            ],
            'assigned' => [
                'title' => '3. Assigned Orders',
                'subtitle' => 'Orders currently processing or out for shipping.',
                'badge' => 'orders',
                'color' => '#7c3aed',
            ],
            'delivered' => [
                'title' => '4. Delivered Orders',
                'subtitle' => 'Orders successfully delivered to customers.',
                'badge' => 'orders',
                'color' => '#16a34a',
            ],
            'cancelled' => [
                'title' => '5. Cancelled Orders',
                'subtitle' => 'Orders that were cancelled.',
                'badge' => 'orders',
                'color' => '#dc2626',
            ],
            'returned' => [
                'title' => '6. Returned Orders',
                'subtitle' => 'Orders with return requests.',
                'badge' => 'orders',
                'color' => '#a855f7',
            ],
            'all' => [
                'title' => 'All Orders',
                'subtitle' => 'Orders across every status appear here.',
                'badge' => 'orders',
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
