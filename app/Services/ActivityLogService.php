<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(
        string $action,
        string $module,
        ?string $description = null,
        ?User $user = null
    ): ActivityLog {
        $actor = $user ?? Auth::user();

        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }
}
