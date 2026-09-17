<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TogglesPublishable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Services\ActivationGuard;
use App\Services\CatalogDuplicator;
use App\Services\ImageService;
use App\Services\PolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    use TogglesPublishable;

    public function __construct(
        protected ImageService $imageService,
        protected PolicyService $policyService,
        protected CatalogDuplicator $duplicator,
        protected ActivationGuard $activationGuard,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);

        return view('admin.brands.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $query = Brand::query()
            ->with(['category:id,name', 'categories:id,name'])
            ->withCount('products')
            ->latest();

        return DataTables::of($query)
            ->addColumn('logo_html', function (Brand $brand) {
                if (! $brand->logo) {
                    return '—';
                }

                $url = asset('storage/'.$brand->logo);

                return '<img src="'.$url.'" alt="" class="rounded border" style="width:40px;height:40px;object-fit:cover;">';
            })
            ->addColumn('category_name', function (Brand $brand) {
                $names = $brand->categories->pluck('name')->filter()->values();
                if ($names->isEmpty() && $brand->category) {
                    $names = collect([$brand->category->name]);
                }

                return $names->isEmpty() ? '—' : e($names->implode(', '));
            })
            ->addColumn('status', function (Brand $brand) {
                return admin_publish_toggle(
                    route('admin.brands.toggle-status', $brand),
                    (bool) $brand->status,
                    (bool) auth()->user()?->can('brands.update')
                );
            })
            ->addColumn('action', function (Brand $brand) {
                $buttons = '';
                if (auth()->user()?->can('brands.update')) {
                    $buttons .= '<a href="'.route('admin.brands.edit', $brand).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('brands.create')) {
                    $buttons .= admin_duplicate_button(route('admin.brands.duplicate', $brand), 'Duplicate this brand as a deactive copy?');
                }
                if (auth()->user()?->can('brands.delete')) {
                    $buttons .= '<form action="'.route('admin.brands.destroy', $brand).'" method="POST" class="d-inline" data-confirm="Delete this brand?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['action', 'status', 'logo_html'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('admin.brands.create', [
            'categories' => Category::query()
                ->where('status', true)
                ->with('parent:id,name')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids'], $data['policies']);
        $data['status'] = $data['status'] ?? false;
        $data['category_id'] = $categoryIds[0] ?? null;

        if ($data['status']) {
            $this->activationGuard->assertCanActivate('brand', $request);
        }

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->imageService->upload($request->file('logo'), 'brands');
        }

        $brand = Brand::query()->create($data);
        $brand->syncCategories($categoryIds);
        $this->syncPoliciesFromRequest($request, $brand);

        activity_log('created', 'brands', "Created brand #{$brand->id}: {$brand->name}");

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('admin.brands.edit', [
            'brand' => $brand->load(['categories:id', 'policies']),
            'categories' => Category::query()
                ->where('status', true)
                ->with('parent:id,name')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids'], $data['policies']);
        $data['category_id'] = $categoryIds[0] ?? null;

        if (($data['status'] ?? $brand->status)) {
            $this->activationGuard->assertCanActivate('brand', $request);
        }

        if ($request->hasFile('logo')) {
            $this->imageService->delete($brand->logo);
            $data['logo'] = $this->imageService->upload($request->file('logo'), 'brands');
        }

        $brand->update($data);
        $brand->syncCategories($categoryIds);
        $this->syncPoliciesFromRequest($request, $brand);

        activity_log('updated', 'brands', "Updated brand #{$brand->id}: {$brand->name}");

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $name = $brand->name;
        $brand->load('policies');
        foreach ($brand->policies as $policy) {
            $this->imageService->delete($policy->icon);
        }
        $this->imageService->delete($brand->logo);
        $brand->delete();

        activity_log('deleted', 'brands', "Deleted brand: {$name}");

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully.');
    }

    public function warranty(Brand $brand): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        return response()->json([
            'id' => $brand->id,
            'name' => $brand->name,
            'warranty' => $brand->warranty ?? '',
            'policies' => $brand->policies()
                ->get()
                ->map(fn ($policy) => $policy->toApiArray())
                ->values(),
        ]);
    }

    protected function syncPoliciesFromRequest(Request $request, Brand $brand): void
    {
        $rows = $request->input('policies', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        $icons = [];
        foreach (array_keys($rows) as $index) {
            $file = $request->file("policies.{$index}.icon");
            if ($file) {
                $icons[(int) $index] = $file;
            }
        }

        // Re-index to match row order after array_values in service
        $orderedRows = array_values($rows);
        $orderedIcons = [];
        foreach (array_keys($rows) as $i => $originalIndex) {
            if (isset($icons[(int) $originalIndex])) {
                $orderedIcons[$i] = $icons[(int) $originalIndex];
            }
        }

        $this->policyService->syncBrandPolicies($brand, $orderedRows, $orderedIcons);
    }

    public function toggleStatus(Request $request, Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        return $this->togglePublishStatus(
            $request,
            $brand,
            'brands.update',
            'brand',
            'brands',
            function (Brand $item, bool $active): void {
                $item->update(['status' => $active]);
            }
        );
    }

    public function duplicate(Brand $brand): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        $copy = $this->duplicator->brand($brand);

        return redirect()
            ->route('admin.brands.edit', $copy)
            ->with('success', 'Brand duplicated as a deactive copy. Review it, then turn it on.');
    }
}
