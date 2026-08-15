<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryAdjustmentRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        return view('admin.inventory.index', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $query = ProductVariant::query()
            ->with(['product:id,name,category_id,brand_id', 'attributeValues'])
            ->latest();

        if ($request->filled('category_id')) {
            $query->whereHas('product', fn ($q) => $q->where('category_id', $request->input('category_id')));
        }

        if ($request->filled('brand_id')) {
            $query->whereHas('product', fn ($q) => $q->where('brand_id', $request->input('brand_id')));
        }

        if ($request->filled('stock_filter')) {
            match ($request->input('stock_filter')) {
                'low' => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
                'out' => $query->where('stock_quantity', '<=', 0),
                default => null,
            };
        }

        return DataTables::of($query)
            ->addColumn('product_name', fn (ProductVariant $variant) => $variant->product?->name ?? '—')
            ->addColumn('variant_label', fn (ProductVariant $variant) => $variant->display_name)
            ->addColumn('stock_badge', function (ProductVariant $variant) {
                if ($variant->stock_quantity <= 0) {
                    return '<span class="badge bg-danger">Out of stock</span>';
                }
                if ($variant->stock_quantity <= $variant->low_stock_threshold) {
                    return '<span class="badge bg-warning text-dark">Low stock</span>';
                }

                return '<span class="badge bg-success">In stock</span>';
            })
            ->addColumn('action', function (ProductVariant $variant) {
                $buttons = '';
                if (auth()->user()?->can('inventory.create')) {
                    $buttons .= '<a href="'.route('admin.inventory.adjust', ['variant_id' => $variant->id]).'" class="btn btn-sm btn-outline-primary me-1">Adjust</a>';
                }
                $buttons .= '<a href="'.route('admin.inventory.history', $variant).'" class="btn btn-sm btn-outline-secondary">History</a>';

                return $buttons;
            })
            ->rawColumns(['action', 'status', 'stock_badge'])
            ->make(true);
    }

    public function adjustForm(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.create'), 403);

        $variant = null;
        if ($request->filled('variant_id')) {
            $variant = ProductVariant::query()
                ->with(['product', 'attributeValues'])
                ->findOrFail($request->input('variant_id'));
        }

        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->orderBy('sku')
            ->get();

        return view('admin.inventory.adjust', compact('variant', 'variants'));
    }

    public function adjustStore(InventoryAdjustmentRequest $request): RedirectResponse
    {
        $variant = ProductVariant::query()->findOrFail($request->validated('variant_id'));

        $this->inventoryService->adjust(
            $variant,
            $request->validated('type'),
            (int) $request->validated('quantity'),
            $request->validated('reason'),
            $request->user()
        );

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Inventory adjusted successfully.');
    }

    public function history(ProductVariant $variant): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $variant->load(['product', 'attributeValues']);

        $logs = InventoryLog::query()
            ->with('creator:id,name')
            ->where('variant_id', $variant->id)
            ->latest()
            ->paginate(25);

        return view('admin.inventory.history', compact('variant', 'logs'));
    }

    public function updateThreshold(Request $request, ProductVariant $variant): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()?->can('inventory.update'), 403);

        $data = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $variant->update($data);

        activity_log(
            'threshold_updated',
            'inventory',
            "Updated threshold for SKU {$variant->sku} to {$data['low_stock_threshold']}"
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Threshold updated.']);
        }

        return back()->with('success', 'Low stock threshold updated.');
    }
}
