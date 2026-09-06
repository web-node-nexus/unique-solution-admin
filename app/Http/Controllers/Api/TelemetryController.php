<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\CrashReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    public function analytics(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.event' => ['required', 'string', 'max:80'],
            'events.*.screen' => ['nullable', 'string', 'max:120'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'platform' => ['nullable', 'string', 'max:20'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'device_id' => ['nullable', 'string', 'max:120'],
        ]);

        $userId = $request->user('sanctum')?->id;
        $rows = [];

        foreach ($data['events'] as $event) {
            $rows[] = [
                'user_id' => $userId,
                'event' => $event['event'],
                'screen' => $event['screen'] ?? null,
                'properties' => isset($event['properties']) ? json_encode($event['properties']) : null,
                'session_id' => $data['session_id'] ?? null,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'occurred_at' => $event['occurred_at'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AnalyticsEvent::query()->insert($rows);

        return response()->json([
            'success' => true,
            'data' => ['accepted' => count($rows)],
        ]);
    }

    public function crash(Request $request): JsonResponse
    {
        $data = $request->validate([
            'level' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:500'],
            'stack' => ['nullable', 'string', 'max:20000'],
            'screen' => ['nullable', 'string', 'max:120'],
            'context' => ['nullable', 'array'],
            'platform' => ['nullable', 'string', 'max:20'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'is_fatal' => ['nullable', 'boolean'],
        ]);

        $report = CrashReport::query()->create([
            'user_id' => $request->user('sanctum')?->id,
            'level' => $data['level'] ?? 'error',
            'message' => $data['message'],
            'stack' => $data['stack'] ?? null,
            'screen' => $data['screen'] ?? null,
            'context' => $data['context'] ?? null,
            'platform' => $data['platform'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'is_fatal' => (bool) ($data['is_fatal'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $report->id],
        ], 201);
    }
}
