<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('activity-logs.view'), 403);

        return view('admin.activity-logs.index', [
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('activity-logs.view'), 403);

        $query = ActivityLog::query()
            ->with('user:id,name,email')
            ->latest();

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('user_name', fn (ActivityLog $log) => $log->user?->name ?? 'System')
            ->addColumn('action_label', fn (ActivityLog $log) => $log->action)
            ->addColumn('created_formatted', fn (ActivityLog $log) => optional($log->created_at)->format('d M Y H:i'))
            ->make(true);
    }
}
