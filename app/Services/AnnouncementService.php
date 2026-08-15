<?php

namespace App\Services;

use App\Jobs\SendFcmAnnouncementJob;
use App\Models\AppNotification;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    public function __construct(protected FcmService $fcm) {}

    /**
     * Persist + optionally dispatch FCM for an announcement / notification.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createAndSend(array $attributes, bool $sendNow = true, ?User $sender = null): AppNotification
    {
        return DB::transaction(function () use ($attributes, $sendNow, $sender) {
            $related = $attributes['related'] ?? null;
            unset($attributes['related'], $attributes['send_fcm']);

            $notification = AppNotification::query()->create(array_merge([
                'type' => $attributes['type'] ?? 'announcement',
                'audience' => $attributes['audience'] ?? 'all',
                'status' => 'draft',
                'sent_by' => $sender?->id ?? auth()->id(),
                'link_type' => $attributes['link_type'] ?? 'none',
                'link_value' => $attributes['link_value'] ?? null,
            ], $attributes));

            if ($related instanceof Model) {
                $notification->related()->associate($related);
                $notification->save();
            }

            if ($sendNow) {
                $this->send($notification);
            }

            return $notification->fresh();
        });
    }

    public function send(AppNotification $notification): AppNotification
    {
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_by' => $notification->sent_by ?: auth()->id(),
        ]);

        // Run sync so announce works even without a queue worker.
        SendFcmAnnouncementJob::dispatchSync($notification->id);

        return $notification->fresh();
    }

    /**
     * Create a sale announcement and push to all devices.
     */
    public function announceSale(Sale $sale, ?User $sender = null): AppNotification
    {
        $body = $sale->subtitle
            ?: ($sale->description ?: 'New sale is live — tap to explore offers.');

        $linkType = in_array($sale->link_type, ['category', 'brand', 'product', 'coupon', 'url'], true)
            ? $sale->link_type
            : 'sale';
        $linkValue = $sale->link_type === 'none' || $sale->link_type === 'sale'
            ? (string) $sale->id
            : $sale->link_value;

        if ($sale->link_type === 'none') {
            $linkType = 'sale';
            $linkValue = (string) $sale->id;
        }

        $notification = $this->createAndSend([
            'type' => 'sale',
            'title' => $sale->title,
            'body' => $body,
            'image' => $sale->image,
            'link_type' => $linkType,
            'link_value' => $linkValue,
            'audience' => 'all',
            'related' => $sale,
        ], true, $sender);

        $sale->update([
            'notify_users' => true,
            'notification_sent_at' => now(),
        ]);

        return $notification;
    }

    /**
     * Deliver FCM for a stored notification (called from job).
     *
     * @return array{success: int, failure: int, skipped: bool, message?: string}
     */
    public function deliverFcm(AppNotification $notification): array
    {
        $result = $this->fcm->sendToAll(
            $notification->title,
            $notification->body,
            $notification->image_url,
            [
                'type' => $notification->type,
                'notification_id' => (string) $notification->id,
                'link_type' => $notification->link_type,
                'link_value' => (string) ($notification->link_value ?? ''),
            ]
        );

        $notification->update([
            'fcm_success_count' => (int) ($result['success'] ?? 0),
            'fcm_failure_count' => (int) ($result['failure'] ?? 0),
        ]);

        return $result;
    }
}
