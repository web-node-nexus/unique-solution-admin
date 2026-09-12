<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Attribute;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('admin.categories.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN));
        }

        return DataTables::of($query)
            ->addColumn('reorder', fn () => '<span class="reorder-handle text-muted" role="button" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>')
            ->addColumn('image_html', function (Category $category) {
                if (! $category->image) {
                    return '<span class="text-muted">—</span>';
                }

                return '<img src="'.e(asset('storage/'.$category->image)).'" alt="" class="rounded border" style="width:40px;height:40px;object-fit:cover;">';
            })
            ->addColumn('status', function (Category $category) {
                $badge = $category->status ? 'success' : 'secondary';
                $label = $category->status ? 'Active' : 'Inactive';
                $html = '<span class="badge bg-'.$badge.'">'.$label.'</span>';

                if ($category->hasActiveSaleBanner()) {
                    $html .= ' <span class="badge bg-danger">Sale</span>';
                }

                return $html;
            })
            ->addColumn('action', function (Category $category) {
                $buttons = '';
                if (auth()->user()?->can('categories.update')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-move-category" data-id="'.$category->id.'" data-direction="up" title="Move up"><i class="bi bi-arrow-up"></i></button>';
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-move-category" data-id="'.$category->id.'" data-direction="down" title="Move down"><i class="bi bi-arrow-down"></i></button>';
                    $buttons .= '<a href="'.route('admin.categories.edit', $category).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('categories.delete')) {
                    $buttons .= '<form action="'.route('admin.categories.destroy', $category).'" method="POST" class="d-inline" data-confirm="Delete this category?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->setRowId(fn (Category $category) => 'category-'.$category->id)
            ->setRowAttr(['data-id' => fn (Category $category) => $category->id])
            ->rawColumns(['reorder', 'image_html', 'action', 'status'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create', [
            'attributes' => Attribute::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['status'] = $data['status'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload($request->file('image'), 'categories');
        }

        if ($request->hasFile('sale_banner')) {
            $data['sale_banner'] = $this->imageService->upload($request->file('sale_banner'), 'categories/sales');
        }

        $data['sale_active'] = (bool) ($data['sale_active'] ?? false);
        $data['parent_id'] = null;

        $attributeIds = $data['attribute_ids'] ?? [];
        unset($data['attribute_ids']);

        $category = Category::query()->create($data);
        $category->attributes()->sync($attributeIds);

        activity_log('created', 'categories', "Created category #{$category->id}: {$category->name}");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        $this->authorize('view', $category);

        $category->load(['attributes.values']);

        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        $category->load('attributes');

        return view('admin.categories.edit', [
            'category' => $category,
            'attributes' => Attribute::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($request->hasFile('image')) {
            $this->imageService->delete($category->image);
            $data['image'] = $this->imageService->upload($request->file('image'), 'categories');
        }

        if (! empty($data['remove_sale_banner'])) {
            $this->imageService->delete($category->sale_banner);
            $data['sale_banner'] = null;
        }

        if ($request->hasFile('sale_banner')) {
            $this->imageService->delete($category->sale_banner);
            $data['sale_banner'] = $this->imageService->upload($request->file('sale_banner'), 'categories/sales');
        }

        $data['sale_active'] = (bool) ($data['sale_active'] ?? false);

        $attributeIds = $data['attribute_ids'] ?? [];
        unset($data['attribute_ids'], $data['remove_sale_banner'], $data['parent_id']);

        $category->update($data);
        $category->attributes()->sync($attributeIds);

        activity_log('updated', 'categories', "Updated category #{$category->id}: {$category->name}");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $name = $category->name;
        $category->delete();

        activity_log('deleted', 'categories', "Deleted category: {$name}");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function reorder(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('categories.update'), 403);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:categories,id'],
        ]);

        foreach ($request->input('order') as $index => $id) {
            Category::query()->where('id', $id)->update(['sort_order' => $index]);
        }

        activity_log('reordered', 'categories', 'Reordered categories');

        return response()->json(['success' => true, 'message' => 'Categories reordered.']);
    }

    /**
     * Move a category one step up or down (same parent group).
     * App home always reads sort_order from API after this change.
     */
    public function move(Request $request, Category $category): JsonResponse
    {
        abort_unless($request->user()?->can('categories.update'), 403);

        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $siblings = Category::query()
            ->when(
                $category->parent_id === null,
                fn ($q) => $q->whereNull('parent_id'),
                fn ($q) => $q->where('parent_id', $category->parent_id)
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $siblings->search(fn (Category $item) => $item->id === $category->id);

        if ($index === false) {
            return response()->json(['success' => false, 'message' => 'Category not found in group.'], 404);
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapWith < 0 || $swapWith >= $siblings->count()) {
            return response()->json([
                'success' => false,
                'message' => $direction === 'up' ? 'Already at the top.' : 'Already at the bottom.',
            ], 422);
        }

        $other = $siblings[$swapWith];
        $currentOrder = $category->sort_order;
        $category->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentOrder]);

        // Normalize contiguous order within the group
        Category::query()
            ->when(
                $category->parent_id === null,
                fn ($q) => $q->whereNull('parent_id'),
                fn ($q) => $q->where('parent_id', $category->parent_id)
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (Category $item, int $i): void {
                if ((int) $item->sort_order !== $i) {
                    $item->update(['sort_order' => $i]);
                }
            });

        activity_log('moved', 'categories', "Moved category #{$category->id} {$direction}");

        return response()->json([
            'success' => true,
            'message' => 'Category position updated. App will show new order.',
        ]);
    }
}
