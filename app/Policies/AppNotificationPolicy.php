<?php

namespace App\Policies;

use App\Models\AppNotification;
use App\Models\User;

class AppNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notifications.view');
    }

    public function view(User $user, AppNotification $notification): bool
    {
        return $user->can('notifications.view');
    }

    public function create(User $user): bool
    {
        return $user->can('notifications.create');
    }

    public function update(User $user, AppNotification $notification): bool
    {
        return $user->can('notifications.update');
    }

    public function delete(User $user, AppNotification $notification): bool
    {
        return $user->can('notifications.delete');
    }
}
