<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\FcmService;

class SendOrderStatusNotification
{
    public function __construct(private readonly FcmService $fcm) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order->loadMissing('user.deviceTokens');
        $user = $order->user;

        activity_log(
            'status_notification',
            'orders',
            "Order {$order->order_number} status changed from {$event->oldStatus} to {$event->newStatus}."
        );

        if (! $user) {
            return;
        }

        $tokens = $user->deviceTokens->pluck('token')->filter()->unique()->values()->all();
        if ($tokens === []) {
            return;
        }

        $title = 'Order '.$order->order_number;
        $body = 'Status updated to '.str_replace('_', ' ', $event->newStatus).'.';

        $this->fcm->sendToTokens($tokens, $title, $body, null, [
            'type' => 'order_status',
            'order_id' => (string) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $event->newStatus,
        ]);
    }
}
