<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Services\AnnouncementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendFcmAnnouncementJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $notificationId) {}

    public function handle(AnnouncementService $announcements): void
    {
        $notification = AppNotification::query()->find($this->notificationId);

        if (! $notification || $notification->status !== 'sent') {
            return;
        }

        $result = $announcements->deliverFcm($notification);

        Log::info('FCM announcement delivered', [
            'notification_id' => $this->notificationId,
            'result' => $result,
        ]);
    }
}
