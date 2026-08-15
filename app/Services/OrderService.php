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
