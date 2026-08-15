<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
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

class SaleController extends Controller
{
    public function __construct(
        protected ImageService $imageService,
        protected AnnouncementService $announcements,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Sale::class);

        return view('admin.sales.index');
    }

    public function datatable(): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        return DataTables::of(Sale::query()->ordered())
            ->addColumn('preview', function (Sale $sale) {
                return $sale->image_url
                    ? '<img src="'.e($sale->image_url).'" alt="" class="rounded" style="width:72px;height:40px;object-fit:cover;">'
                    : '—';
            })
            ->addColumn('window', function (Sale $sale) {
                $start = $sale->starts_at?->format('d M Y H:i') ?? 'Anytime';
                $end = $sale->ends_at?->format('d M Y H:i') ?? 'No expiry';

                return $start.' → '.$end;
            })
            ->addColumn('live', function (Sale $sale) {
                return $sale->isCurrentlyLive()
                    ? '<span class="badge bg-danger">Live</span>'
                    : '<span class="badge bg-secondary">Off</span>';
            })
            ->addColumn('status', function (Sale $sale) {
                return '<span class="badge bg-'.($sale->status ? 'success' : 'secondary').'">'
                    .($sale->status ? 'Active' : 'Inactive').'</span>';
            })
            ->addColumn('action', function (Sale $sale) {
                $buttons = '';
                if (auth()->user()?->can('sales.update')) {
                    $buttons .= '<a href="'.route('admin.sales.edit', $sale).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('sales.delete')) {
                    $buttons .= '<form action="'.route('admin.sales.destroy', $sale).'" method="POST" class="d-inline" data-confirm="Delete this sale?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['preview', 'live', 'status', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);

        return view('admin.sales.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Sale::class);

        $data = $this->validated($request);
        $notifyUsers = (bool) ($data['notify_users'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload($request->file('image'), 'sales');
        }

        $data['status'] = (bool) ($data['status'] ?? true);
        $data['notify_users'] = $notifyUsers;
        $data['sort_order'] = $data['sort_order'] ?? ((int) Sale::query()->max('sort_order') + 1);

        $sale = Sale::query()->create($data);
        activity_log('created', 'sales', "Created sale #{$sale->id}: {$sale->title}");

        $message = 'Sale created successfully.';

        if ($notifyUsers) {
            $this->announcements->announceSale($sale, $request->user());
            activity_log('sent', 'notifications', "Sale #{$sale->id} announced to all users");
            $message = 'Sale created and notification sent to all users (in-app + FCM).';
        }

        return redirect()->route('admin.sales.index')->with('success', $message);
    }

    public function edit(Sale $sale): View
    {
        $this->authorize('update', $sale);

        return view('admin.sales.edit', array_merge($this->formData(), compact('sale')));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('update', $sale);

        $data = $this->validated($request, true);
        $notifyUsers = (bool) ($data['notify_users'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($request->boolean('remove_image')) {
            $this->imageService->delete($sale->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            $this->imageService->delete($sale->image);
            $data['image'] = $this->imageService->upload($request->file('image'), 'sales');
        }

        $data['status'] = (bool) ($data['status'] ?? false);
        $data['notify_users'] = $notifyUsers;
        $sale->update($data);

        activity_log('updated', 'sales', "Updated sale #{$sale->id}: {$sale->title}");

        $message = 'Sale updated successfully.';

        // Send (or re-send) when admin checks notify on save.
        if ($notifyUsers && $request->boolean('send_notification_now')) {
            $this->announcements->announceSale($sale->fresh(), $request->user());
            activity_log('sent', 'notifications', "Sale #{$sale->id} announced to all users");
            $message = 'Sale updated and notification sent to all users (in-app + FCM).';
        }

        return redirect()->route('admin.sales.index')->with('success', $message);
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);

        $this->imageService->delete($sale->image);
        $title = $sale->title;
        $sale->delete();

        activity_log('deleted', 'sales', "Deleted sale: {$title}");

        return redirect()->route('admin.sales.index')->with('success', 'Sale deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $isUpdate = false): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'link_type' => ['required', Rule::in(['none', 'category', 'brand', 'product', 'url', 'coupon'])],
            'link_value' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'boolean'],
            'notify_users' => ['sometimes', 'boolean'],
            'send_notification_now' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['status'] = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
        $data['notify_users'] = filter_var($request->input('notify_users'), FILTER_VALIDATE_BOOLEAN);
        $data['remove_image'] = filter_var($request->input('remove_image'), FILTER_VALIDATE_BOOLEAN);

        if (($data['link_type'] ?? 'none') === 'none') {
            $data['link_value'] = null;
        }

        // Auto-enable notify when targeting a category or brand (admin intent).
        if (in_array($data['link_type'] ?? '', ['category', 'brand'], true) && $request->has('notify_users') === false) {
            $data['notify_users'] = true;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Category::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name']),
            'coupons' => Coupon::query()->orderByDesc('id')->limit(100)->get(['id', 'code']),
        ];
    }
}
