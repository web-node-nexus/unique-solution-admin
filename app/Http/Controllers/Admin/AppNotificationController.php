<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TogglesPublishable;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ActivationGuard;
use App\Services\AnnouncementService;
use App\Services\CatalogDuplicator;
use App\Services\ImageService;
use App\Support\PublishingWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AppNotificationController extends Controller
{
    use TogglesPublishable;

    public function __construct(
        protected ImageService $imageService,
        protected AnnouncementService $announcements,
        protected CatalogDuplicator $duplicator,
        protected ActivationGuard $activationGuard,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', AppNotification::class);

        return view('admin.notifications.index');
    }

    public function datatable(): JsonResponse
    {
        $this->authorize('viewAny', AppNotification::class);

        return DataTables::of(AppNotification::query()->with('sender:id,name')->latest())
            ->addColumn('preview', function (AppNotification $n) {
                return $n->image_url
                    ? '<img src="'.e($n->image_url).'" alt="" class="rounded" style="width:56px;height:40px;object-fit:cover;">'
                    : '—';
            })
            ->addColumn('type_label', function (AppNotification $n) {
                $map = [
                    'announcement' => 'primary',
                    'sale' => 'danger',
                    'system' => 'secondary',
                ];
                $badge = $map[$n->type] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.ucfirst($n->type).'</span>';
            })
            ->addColumn('window', function (AppNotification $n) {
                $start = $n->starts_at?->format('d M Y H:i') ?? 'Anytime';
                $end = $n->ends_at?->format('d M Y H:i') ?? 'No end';

                return $start.' → '.$end;
            })
            ->addColumn('status', function (AppNotification $n) {
                $toggle = admin_publish_toggle(
                    route('admin.notifications.toggle-status', $n),
                    (bool) $n->is_active,
                    (bool) auth()->user()?->can('notifications.update'),
                    $n->scheduleState()
                );
                $sent = $n->status === 'sent'
                    ? ' <span class="badge bg-success">Sent</span>'
                    : ' <span class="badge bg-secondary">Draft</span>';

                return $toggle.$sent;
            })
            ->addColumn('fcm', function (AppNotification $n) {
                if ($n->status !== 'sent') {
                    return '—';
                }

                return (int) $n->fcm_success_count.' ok / '.(int) $n->fcm_failure_count.' fail';
            })
            ->editColumn('sent_at', fn (AppNotification $n) => $n->sent_at?->format('d M Y H:i') ?? '—')
            ->addColumn('action', function (AppNotification $n) {
                $buttons = '';
                if (auth()->user()?->can('notifications.update') && $n->status !== 'sent') {
                    $buttons .= '<a href="'.route('admin.notifications.edit', $n).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('notifications.create')) {
                    $buttons .= admin_duplicate_button(route('admin.notifications.duplicate', $n), 'Duplicate this announcement as a deactive copy?');
                }
                if (auth()->user()?->can('notifications.delete')) {
                    $buttons .= '<form action="'.route('admin.notifications.destroy', $n).'" method="POST" class="d-inline" data-confirm="Delete this announcement?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['preview', 'type_label', 'status', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', AppNotification::class);

        return view('admin.notifications.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AppNotification::class);

        $data = $this->validated($request);
        $isActive = (bool) ($data['is_active'] ?? false);
        unset($data['image'], $data['send_now'], $data['remove_image']);

        if ($isActive) {
            $this->activationGuard->assertCanActivate('announcement', $request);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload($request->file('image'), 'notifications');
        }

        $data['type'] = 'announcement';
        $data['is_active'] = $isActive;

        $sendNow = $isActive && $this->shouldSendNow($data);
        $notification = $this->announcements->createAndSend($data, $sendNow, $request->user());

        if ($sendNow) {
            activity_log('sent', 'notifications', "Announced #{$notification->id}: {$notification->title}");

            return redirect()->route('admin.notifications.index')
                ->with('success', 'Announcement sent to all users (in-app + push).');
        }

        activity_log('created', 'notifications', "Created announcement draft #{$notification->id}");

        $message = $isActive
            ? 'Announcement saved and scheduled. It will appear on the app at the start date and time.'
            : 'Announcement saved as deactive. Turn it on after you review the preview.';

        return redirect()->route('admin.notifications.index')
            ->with('success', $message);
    }

    public function edit(AppNotification $notification): View
    {
        $this->authorize('update', $notification);
        abort_if($notification->status === 'sent', 403, 'Sent announcements cannot be edited.');

        return view('admin.notifications.edit', array_merge($this->formData(), compact('notification')));
    }

    public function update(Request $request, AppNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);
        abort_if($notification->status === 'sent', 403, 'Sent announcements cannot be edited.');

        $data = $this->validated($request);
        $isActive = (bool) ($data['is_active'] ?? false);
        unset($data['image'], $data['send_now'], $data['remove_image']);

        if ($isActive) {
            $this->activationGuard->assertCanActivate('announcement', $request);
        }

        if ($request->boolean('remove_image')) {
            if ($notification->related_type !== Sale::class) {
                $this->imageService->delete($notification->image);
            }
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($notification->related_type !== Sale::class) {
                $this->imageService->delete($notification->image);
            }
            $data['image'] = $this->imageService->upload($request->file('image'), 'notifications');
        }

        $data['is_active'] = $isActive;
        $notification->update($data);

        if ($isActive && $notification->fresh()?->isDueToSend()) {
            $this->announcements->send($notification);
            activity_log('sent', 'notifications', "Announced #{$notification->id}");

            return redirect()->route('admin.notifications.index')
                ->with('success', 'Announcement updated and sent to all users.');
        }

        activity_log('updated', 'notifications', "Updated announcement #{$notification->id}");

        $message = $isActive
            ? 'Announcement updated. It will show on the app only during the scheduled window.'
            : 'Announcement updated and kept deactive.';

        return redirect()->route('admin.notifications.index')
            ->with('success', $message);
    }

    public function send(AppNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $this->announcements->send($notification);
        activity_log('sent', 'notifications', "Announced #{$notification->id}: {$notification->title}");

        return back()->with('success', 'Announcement sent to all users (in-app + FCM).');
    }

    public function destroy(AppNotification $notification): RedirectResponse
    {
        $this->authorize('delete', $notification);

        // Do not delete shared sale banner files.
        if ($notification->image && $notification->related_type !== Sale::class) {
            $this->imageService->delete($notification->image);
        }

        $notification->delete();

        activity_log('deleted', 'notifications', "Deleted announcement #{$notification->id}");

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Announcement deleted.');
    }

    public function toggleStatus(Request $request, AppNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $response = $this->togglePublishStatus(
            $request,
            $notification,
            'notifications.update',
            'announcement',
            'notifications',
            function (AppNotification $item, bool $active): void {
                $item->update(['is_active' => $active]);
                if ($active && $item->fresh()?->isDueToSend()) {
                    $this->announcements->send($item);
                }
            }
        );

        return $response;
    }

    public function duplicate(AppNotification $notification): RedirectResponse
    {
        $this->authorize('create', AppNotification::class);

        $copy = $this->duplicator->announcement($notification);

        return redirect()
            ->route('admin.notifications.edit', $copy)
            ->with('success', 'Announcement duplicated as a deactive copy. Review it, then turn it on.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'link_type' => ['required', Rule::in(['none', 'category', 'brand', 'product', 'url', 'sale', 'coupon'])],
            'link_value' => ['nullable', 'string', 'max:500'],
            'audience' => ['required', Rule::in(['all', 'customers'])],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'send_now' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $data['send_now'] = filter_var($request->input('send_now'), FILTER_VALIDATE_BOOLEAN);
        $data['remove_image'] = filter_var($request->input('remove_image'), FILTER_VALIDATE_BOOLEAN);
        $data['starts_at'] = $request->filled('starts_at') ? $request->input('starts_at') : null;
        $data['ends_at'] = $request->filled('ends_at') ? $request->input('ends_at') : null;
        if (($data['link_type'] ?? 'none') === 'none') {
            $data['link_value'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Category::query()->where('status', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name']),
            'sales' => Sale::query()->orderByDesc('id')->limit(100)->get(['id', 'title']),
            'coupons' => Coupon::query()->orderByDesc('id')->limit(100)->get(['id', 'code', 'title']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function shouldSendNow(array $data): bool
    {
        $start = $data['starts_at'] ?? null;
        $end = $data['ends_at'] ?? null;

        return PublishingWindow::isVisibleOnApp(true, $start, $end);
    }
}
