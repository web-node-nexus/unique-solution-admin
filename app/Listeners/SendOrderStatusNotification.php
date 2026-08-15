<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;

class SendOrderStatusNotification
{
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        // TODO: Send email notification to the customer about the order status change.
        // Mail::to($order->user->email)->send(new OrderStatusMail($order, $event->oldStatus, $event->newStatus));

        // TODO: Send SMS notification to the customer phone when SMS gateway is configured.
        // Sms::to($order->user->phone)->send("Your order {$order->order_number} is now {$event->newStatus}.");

        activity_log(
            'status_notification',
            'orders',
            "Order {$order->order_number} status changed from {$event->oldStatus} to {$event->newStatus}."
        );
    }
}
