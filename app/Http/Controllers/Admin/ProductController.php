<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Imports\ProductsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function __construct(protected ProductService $productService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()
            ->with(['category:id,name', 'brand:id,name'])
            ->withCount('variants')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->filled('stock_level')) {
            match ($request->input('stock_level')) {
                'in_stock' => $query->whereHas('variants', fn ($q) => $q->whereColumn('stock_quantity', '>', 'low_stock_threshold')),
                'low_stock' => $query->whereHas(
                    'variants',
                    fn ($q) => $q->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                        ->where('stock_quantity', '>', 0)
                ),
                'out_of_stock' => $query->whereDoesntHave('variants', fn ($q) => $q->where('stock_quantity', '>', 0)),
                default => null,
            };
        }

        return DataTables::of($query)
            ->addColumn('checkbox', fn (Product $product) => '<input type="checkbox" class="form-check-input product-row-check" value="'.$product->id.'">')
            ->addColumn('category_name', fn (Product $product) => $product->category?->name ?? '—')
            ->addColumn('brand_name', fn (Product $product) => $product->brand?->name ?? '—')
            ->addColumn('status', function (Product $product) {
                $map = [
                    'active' => 'success',
                    'inactive' => 'secondary',
                    'draft' => 'warning',
                ];
                $badge = $map[$product->status] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.e(ucfirst($product->status)).'</span>';
            })
            ->addColumn('action', function (Product $product) {
                $buttons = '<a href="'.route('admin.products.show', $product).'" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>';
                if (auth()->user()?->can('products.update')) {
                    $buttons .= '<a href="'.route('admin.products.edit', $product).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-warning me-1 btn-quick-edit"'
                        .' data-id="'.$product->id.'"'
                        .' data-name="'.e($product->name).'"'
                        .' data-status="'.e($product->status).'"'
                        .' data-featured="'.($product->is_featured ? '1' : '0').'"'
                        .' data-url="'.route('admin.products.quick-update', $product).'"'
                        .' title="Quick edit"><i class="bi bi-lightning"></i></button>';
                }
                if (auth()->user()?->can('products.create')) {
                    $buttons .= '<form action="'.route('admin.products.clone', $product).'" method="POST" class="d-inline me-1" data-confirm="Clone this product?" data-confirm-title="Clone product" data-confirm-button="Yes, clone">'
                        .csrf_field()
                        .'<button type="submit" class="btn btn-sm btn-outline-info" title="Clone"><i class="bi bi-copy"></i></button></form>';
                }
                if (auth()->user()?->can('products.delete')) {
                    $buttons .= '<form action="'.route('admin.products.destroy', $product).'" method="POST" class="d-inline" data-confirm="Delete this product?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['checkbox', 'action', 'status'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', [
            'categories' => Category::query()->where('status', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load([
            'category',
            'brand',
            'images',
            'variants.attributeValues.attribute',
            'variants.images',
            'creator:id,name',
        ]);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['images', 'variants.attributeValues', 'variants.images']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->where('status', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function clone(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $clone = $this->productService->clone(
            $product,
            $request->user(),
            $request->boolean('copy_stock')
        );

        return redirect()
            ->route('admin.products.edit', $clone)
            ->with('success', 'Product cloned successfully.');
    }

    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate,draft,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        if ($data['action'] === 'delete') {
            abort_unless(auth()->user()?->can('products.delete'), 403);
            $count = $this->productService->bulkDelete($data['ids']);
        } else {
            abort_unless(auth()->user()?->can('products.update'), 403);
            $status = match ($data['action']) {
                'activate' => 'active',
                'deactivate' => 'inactive',
                'draft' => 'draft',
            };
            $count = $this->productService->bulkStatus($data['ids'], $status);
        }

        $message = "Bulk action completed on {$count} product(s).";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'count' => $count]);
        }

        return back()->with('success', $message);
    }

    public function export(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('products.export'), 403);

        return Excel::download(new ProductsExport, 'products-'.now()->format('Ymd-His').'.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new ProductsImport;
        Excel::import($import, $request->file('file'));

        return back()->with(
            'success',
            "Imported {$import->imported} product(s). Skipped {$import->skipped}."
        );
    }

    public function importTemplate(): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        $headers = [['name', 'category', 'brand', 'base_price', 'price', 'sku', 'stock', 'description']];
        $export = new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        };

        return Excel::download($export, 'products-import-template.xlsx');
    }

    public function getCategoryAttributes(Category $category): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $attributes = $category->attributes()
            ->with('values')
            ->where('attributes.status', true)
            ->get()
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'type' => $attribute->type,
                    'values' => $attribute->values->map(fn ($value) => [
                        'id' => $value->id,
                        'value' => $value->value,
                        'extra_data' => $value->extra_data,
                        'image_url' => $value->image_url,
                    ])->values(),
                ];
            });

        return response()->json(['attributes' => $attributes]);
    }

    public function quickUpdate(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive,draft'],
            'is_featured' => ['sometimes', 'boolean'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $product->update($validator->validated());

        activity_log('quick_updated', 'products', "Quick updated product #{$product->id}");

        return response()->json([
            'success' => true,
            'message' => 'Product updated.',
            'product' => $product->fresh(),
        ]);
    }
}
