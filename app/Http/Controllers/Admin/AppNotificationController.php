<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Sale;
use App\Services\AnnouncementService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AppNotificationController extends Controller
{
    public function __construct(
        protected ImageService $imageService,
        protected AnnouncementService $announcements,
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
            ->addColumn('status', function (AppNotification $n) {
                $badge = $n->status === 'sent' ? 'success' : 'secondary';

                return '<span class="badge bg-'.$badge.'">'.ucfirst($n->status).'</span>';
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
                    $buttons .= '<form action="'.route('admin.notifications.send', $n).'" method="POST" class="d-inline" data-confirm="Send this announcement to all app users (in-app + FCM)?">'
                        .csrf_field()
                        .'<button type="submit" class="btn btn-sm btn-outline-success me-1"><i class="bi bi-send"></i></button></form>';
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
        $sendNow = (bool) ($data['send_now'] ?? false);
        unset($data['image'], $data['send_now'], $data['remove_image']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload($request->file('image'), 'notifications');
        }

        $data['type'] = 'announcement';

        $notification = $this->announcements->createAndSend($data, $sendNow, $request->user());

        if ($sendNow) {
            activity_log('sent', 'notifications', "Announced #{$notification->id}: {$notification->title}");

            return redirect()->route('admin.notifications.index')
                ->with('success', 'Announcement sent to all users (in-app + FCM).');
        }

        activity_log('created', 'notifications', "Created announcement draft #{$notification->id}");

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Announcement draft saved.');
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
        $sendNow = (bool) ($data['send_now'] ?? false);
        unset($data['image'], $data['send_now'], $data['remove_image']);

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

        $notification->update($data);

        if ($sendNow) {
            $this->announcements->send($notification);
            activity_log('sent', 'notifications', "Announced #{$notification->id}");

            return redirect()->route('admin.notifications.index')
                ->with('success', 'Announcement updated and sent to all users.');
        }

        activity_log('updated', 'notifications', "Updated announcement #{$notification->id}");

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Announcement updated.');
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
            'send_now' => ['sometimes', 'boolean'],
        ]);

        $data['send_now'] = filter_var($request->input('send_now'), FILTER_VALIDATE_BOOLEAN);
        $data['remove_image'] = filter_var($request->input('remove_image'), FILTER_VALIDATE_BOOLEAN);
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
            'brands' => \App\Models\Brand::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name']),
            'sales' => Sale::query()->orderByDesc('id')->limit(100)->get(['id', 'title']),
            'coupons' => Coupon::query()->orderByDesc('id')->limit(100)->get(['id', 'code', 'title']),
        ];
    }
}
