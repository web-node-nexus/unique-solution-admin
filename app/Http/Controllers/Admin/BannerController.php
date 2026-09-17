<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TogglesPublishable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivationGuard;
use App\Services\CatalogDuplicator;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BannerController extends Controller
{
    use TogglesPublishable;

    public function __construct(
        protected ImageService $imageService,
        protected CatalogDuplicator $duplicator,
        protected ActivationGuard $activationGuard,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Banner::class);

        return view('admin.banners.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Banner::class);

        $query = Banner::query()->ordered();

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN));
        }

        return DataTables::of($query)
            ->addColumn('reorder', fn () => '<span class="reorder-handle text-muted" role="button" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>')
            ->addColumn('preview', function (Banner $banner) {
                $url = e($banner->image_url ?? '');

                return $url
                    ? '<img src="'.$url.'" alt="" class="rounded" style="width:72px;height:40px;object-fit:cover;">'
                    : '—';
            })
            ->addColumn('link_label', function (Banner $banner) {
                $link = $banner->deepLink();

                return match ($link['type']) {
                    'category' => 'Category: '.($link['label'] ?? '#'.$link['value']),
                    'brand' => 'Brand: '.($link['label'] ?? '#'.$link['value']),
                    'product' => 'Product: '.($link['label'] ?? '#'.$link['value']),
                    default => 'No link',
                };
            })
            ->addColumn('window', function (Banner $banner) {
                $start = $banner->starts_at?->format('d M Y H:i') ?? 'Anytime';
                $end = $banner->ends_at?->format('d M Y H:i') ?? 'No end';

                return $start.' → '.$end;
            })
            ->addColumn('status', function (Banner $banner) {
                return admin_publish_toggle(
                    route('admin.banners.toggle-status', $banner),
                    (bool) $banner->status,
                    (bool) auth()->user()?->can('banners.update'),
                    $banner->scheduleState()
                );
            })
            ->addColumn('action', function (Banner $banner) {
                $buttons = '';
                if (auth()->user()?->can('banners.update')) {
                    $buttons .= '<a href="'.route('admin.banners.edit', $banner).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('banners.create')) {
                    $buttons .= admin_duplicate_button(route('admin.banners.duplicate', $banner), 'Duplicate this banner as a deactive copy?');
                }
                if (auth()->user()?->can('banners.delete')) {
                    $buttons .= '<form action="'.route('admin.banners.destroy', $banner).'" method="POST" class="d-inline" data-confirm="Delete this banner?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['reorder', 'preview', 'status', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', Banner::class);

        return view('admin.banners.create', [
            'categories' => Category::query()->where('status', true)->orderBy('sort_order')->orderBy('name')->get(),
            'brands' => Brand::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name']),
            'nextSort' => (int) Banner::query()->max('sort_order') + 1,
        ]);
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        $data['image_path'] = $this->imageService->upload($request->file('image'), 'banners');
        $data['sort_order'] = $data['sort_order'] ?? ((int) Banner::query()->max('sort_order') + 1);
        $data['status'] = (bool) ($data['status'] ?? false);

        if ($data['status']) {
            $this->activationGuard->assertCanActivate('banner', $request);
        }

        $banner = Banner::query()->create($data);

        activity_log('created', 'banners', "Created banner #{$banner->id}: {$banner->title}");

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner): View
    {
        $this->authorize('update', $banner);

        return view('admin.banners.edit', [
            'banner' => $banner,
            'categories' => Category::query()->where('status', true)->orderBy('sort_order')->orderBy('name')->get(),
            'brands' => Brand::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $this->imageService->delete($banner->image_path);
            $data['image_path'] = $this->imageService->upload($request->file('image'), 'banners');
        }

        if ($data['status'] ?? $banner->status) {
            $this->activationGuard->assertCanActivate('banner', $request);
        }

        $banner->update($data);

        activity_log('updated', 'banners', "Updated banner #{$banner->id}: {$banner->title}");

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $this->imageService->delete($banner->image_path);
        $title = $banner->title;
        $banner->delete();

        activity_log('deleted', 'banners', "Deleted banner: {$title}");

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner deleted successfully.');
    }

    public function reorder(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('banners.update'), 403);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:banners,id'],
        ]);

        foreach ($request->input('order') as $index => $id) {
            Banner::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        activity_log('reordered', 'banners', 'Reordered app carousel banners');

        return response()->json(['success' => true, 'message' => 'Banners reordered for carousel.']);
    }

    public function toggleStatus(Request $request, Banner $banner): JsonResponse
    {
        $this->authorize('update', $banner);

        return $this->togglePublishStatus(
            $request,
            $banner,
            'banners.update',
            'banner',
            'banners',
            function (Banner $item, bool $active): void {
                $item->update(['status' => $active]);
            }
        );
    }

    public function duplicate(Banner $banner): RedirectResponse
    {
        $this->authorize('create', Banner::class);

        $copy = $this->duplicator->banner($banner);

        return redirect()
            ->route('admin.banners.edit', $copy)
            ->with('success', 'Banner duplicated as a deactive copy. Review it, then turn it on.');
    }
}
