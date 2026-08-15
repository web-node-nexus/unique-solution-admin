<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);

        return view('admin.brands.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $query = Brand::query()->withCount('products')->latest();

        return DataTables::of($query)
            ->addColumn('logo_html', function (Brand $brand) {
                if (! $brand->logo) {
                    return '—';
                }

                $url = asset('storage/'.$brand->logo);

                return '<img src="'.$url.'" alt="" height="32">';
            })
            ->addColumn('status', function (Brand $brand) {
                $badge = $brand->status ? 'success' : 'secondary';
                $label = $brand->status ? 'Active' : 'Inactive';

                return '<span class="badge bg-'.$badge.'">'.$label.'</span>';
            })
            ->addColumn('action', function (Brand $brand) {
                $buttons = '';
                if (auth()->user()?->can('brands.update')) {
                    $buttons .= '<a href="'.route('admin.brands.edit', $brand).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
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

        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? true;

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->imageService->upload($request->file('logo'), 'brands');
        }

        $brand = Brand::query()->create($data);

        activity_log('created', 'brands', "Created brand #{$brand->id}: {$brand->name}");

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('admin.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->imageService->delete($brand->logo);
            $data['logo'] = $this->imageService->upload($request->file('logo'), 'brands');
        }

        $brand->update($data);

        activity_log('updated', 'brands', "Updated brand #{$brand->id}: {$brand->name}");

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $name = $brand->name;
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
        ]);
    }
}
