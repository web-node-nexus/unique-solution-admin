<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Services\AnnouncementService;
use Illuminate\Console\Command;

class DispatchScheduledAnnouncements extends Command
{
    protected $signature = 'announcements:dispatch-scheduled';

    protected $description = 'Send active announcements whose start time has arrived.';

    public function handle(AnnouncementService $announcements): int
    {
        $due = AppNotification::query()
            ->where('is_active', true)
            ->where('status', 'draft')
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('id')
            ->get();

        foreach ($due as $notification) {
            $announcements->send($notification);
            $this->info("Sent announcement #{$notification->id}");
        }

        $this->info('Dispatched '.$due->count().' scheduled announcement(s).');

        return self::SUCCESS;
    }
}
