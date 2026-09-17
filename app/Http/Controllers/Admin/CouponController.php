<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TogglesPublishable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\ActivationGuard;
use App\Services\CatalogDuplicator;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CouponController extends Controller
{
    use TogglesPublishable;

    public function __construct(
        protected ImageService $imageService,
        protected CatalogDuplicator $duplicator,
        protected ActivationGuard $activationGuard,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Coupon::class);

        return view('admin.coupons.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Coupon::class);

        $query = Coupon::query()->latest();

        return DataTables::of($query)
            ->addColumn('discount_label', function (Coupon $coupon) {
                return $coupon->discount_type === 'percentage'
                    ? rtrim(rtrim(number_format((float) $coupon->discount_value, 2), '0'), '.').'%'
                    : format_money($coupon->discount_value);
            })
            ->addColumn('usage', fn (Coupon $coupon) => ($coupon->used_count ?? 0).'/'.($coupon->max_uses ?? '∞'))
            ->addColumn('status', function (Coupon $coupon) {
                return admin_publish_toggle(
                    route('admin.coupons.toggle-status', $coupon),
                    (bool) $coupon->status,
                    (bool) auth()->user()?->can('coupons.update'),
                    $coupon->scheduleState()
                );
            })
            ->addColumn('action', function (Coupon $coupon) {
                $buttons = '<a href="'.route('admin.coupons.usage', $coupon).'" class="btn btn-sm btn-outline-secondary me-1">Usage</a>';
                if (auth()->user()?->can('coupons.update')) {
                    $buttons .= '<a href="'.route('admin.coupons.edit', $coupon).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('coupons.create')) {
                    $buttons .= admin_duplicate_button(route('admin.coupons.duplicate', $coupon), 'Duplicate this coupon as a deactive copy?');
                }
                if (auth()->user()?->can('coupons.delete')) {
                    $buttons .= '<form action="'.route('admin.coupons.destroy', $coupon).'" method="POST" class="d-inline" data-confirm="Delete this coupon?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? false;
        $data['used_count'] = 0;
        unset($data['image']);

        if ($data['status']) {
            $this->activationGuard->assertCanActivate('coupon', $request);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload($request->file('image'), 'coupons');
        }

        $coupon = Coupon::query()->create($data);

        activity_log('created', 'coupons', "Created coupon #{$coupon->id}: {$coupon->code}");

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image'], $data['remove_image']);

        if ($request->boolean('remove_image')) {
            $this->imageService->delete($coupon->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            $this->imageService->delete($coupon->image);
            $data['image'] = $this->imageService->upload($request->file('image'), 'coupons');
        }

        if ($data['status'] ?? $coupon->status) {
            $this->activationGuard->assertCanActivate('coupon', $request);
        }

        $coupon->update($data);

        activity_log('updated', 'coupons', "Updated coupon #{$coupon->id}: {$coupon->code}");

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $this->imageService->delete($coupon->image);
        $code = $coupon->code;
        $coupon->delete();

        activity_log('deleted', 'coupons', "Deleted coupon: {$code}");

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully.');
    }

    public function usage(Coupon $coupon): View
    {
        $this->authorize('view', $coupon);

        return view('admin.coupons.usage', [
            'coupon' => $coupon,
            'remaining' => $coupon->max_uses !== null
                ? max(0, $coupon->max_uses - $coupon->used_count)
                : null,
        ]);
    }

    public function toggleStatus(Request $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('update', $coupon);

        return $this->togglePublishStatus(
            $request,
            $coupon,
            'coupons.update',
            'coupon',
            'coupons',
            function (Coupon $item, bool $active): void {
                $item->update(['status' => $active]);
            }
        );
    }

    public function duplicate(Coupon $coupon): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $copy = $this->duplicator->coupon($coupon);

        return redirect()
            ->route('admin.coupons.edit', $copy)
            ->with('success', 'Coupon duplicated as a deactive copy. Review it, then turn it on.');
    }
}
