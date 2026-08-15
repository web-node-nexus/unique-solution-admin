<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttributeRequest;
use App\Http\Requests\Admin\StoreAttributeValueRequest;
use App\Http\Requests\Admin\UpdateAttributeRequest;
use App\Http\Requests\Admin\UpdateAttributeValueRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AttributeController extends Controller
{
    public function __construct(protected ImageService $imageService) {}
    public function index(): View
    {
        $this->authorize('viewAny', Attribute::class);

        return view('admin.attributes.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);

        $query = Attribute::query()->withCount('values')->latest();

        return DataTables::of($query)
            ->addColumn('status', function (Attribute $attribute) {
                $badge = $attribute->status ? 'success' : 'secondary';
                $label = $attribute->status ? 'Active' : 'Inactive';

                return '<span class="badge bg-'.$badge.'">'.$label.'</span>';
            })
            ->addColumn('action', function (Attribute $attribute) {
                $buttons = '';
                if (auth()->user()?->can('attributes.update')) {
                    $buttons .= '<a href="'.route('admin.attributes.edit', $attribute).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('attributes.delete')) {
                    $buttons .= '<form action="'.route('admin.attributes.destroy', $attribute).'" method="POST" class="d-inline" data-confirm="Delete this attribute?">'
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
        $this->authorize('create', Attribute::class);

        return view('admin.attributes.create');
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $values = $data['values'] ?? [];
        unset($data['values']);
        $data['status'] = $data['status'] ?? true;

        $attribute = Attribute::query()->create($data);

        foreach ($values as $valueData) {
            $attribute->values()->create([
                'value' => $valueData['value'],
                'extra_data' => $valueData['extra_data'] ?? null,
            ]);
        }

        activity_log('created', 'attributes', "Created attribute #{$attribute->id}: {$attribute->name}");

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute created successfully.');
    }

    public function show(Attribute $attribute): View
    {
        $this->authorize('view', $attribute);

        $attribute->load('values');

        return view('admin.attributes.show', compact('attribute'));
    }

    public function edit(Attribute $attribute): View
    {
        $this->authorize('update', $attribute);

        $attribute->load(['values', 'categories:id,name']);

        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $attribute->update($request->validated());

        activity_log('updated', 'attributes', "Updated attribute #{$attribute->id}: {$attribute->name}");

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $name = $attribute->name;
        $attribute->values()->delete();
        $attribute->delete();

        activity_log('deleted', 'attributes', "Deleted attribute: {$name}");

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }

    public function storeValue(StoreAttributeValueRequest $request, Attribute $attribute): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $attribute);

        $value = $attribute->values()->create($this->valuePayload($request, $attribute));

        activity_log('created', 'attributes', "Added value to attribute #{$attribute->id}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'value' => $value->fresh()]);
        }

        return back()->with('success', 'Attribute value added.');
    }

    public function updateValue(
        UpdateAttributeValueRequest $request,
        Attribute $attribute,
        AttributeValue $value
    ): RedirectResponse|JsonResponse {
        $this->authorize('update', $attribute);

        abort_unless($value->attribute_id === $attribute->id, 404);

        $value->update($request->validated());

        activity_log('updated', 'attributes', "Updated value #{$value->id} on attribute #{$attribute->id}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'value' => $value]);
        }

        return back()->with('success', 'Attribute value updated.');
    }

    public function destroyValue(
        Attribute $attribute,
        AttributeValue $value
    ): RedirectResponse|JsonResponse {
        $this->authorize('update', $attribute);

        abort_unless($value->attribute_id === $attribute->id, 404);

        $image = $value->extra_data['image'] ?? null;
        $this->imageService->delete(is_string($image) ? $image : null);
        $value->delete();

        activity_log('deleted', 'attributes', "Deleted value #{$value->id} from attribute #{$attribute->id}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Attribute value deleted.');
    }

    /**
     * @return array{value: string, extra_data: array<string, mixed>|null}
     */
    private function valuePayload(StoreAttributeValueRequest $request, Attribute $attribute): array
    {
        $data = $request->validated();
        $extra = is_array($data['extra_data'] ?? null) ? $data['extra_data'] : [];
        $mode = $data['color_mode'] ?? 'picker';

        if ($attribute->type === 'color-swatch') {
            $extra['mode'] = $mode;
            if ($mode === 'image') {
                unset($extra['hex']);
            } elseif (! empty($extra['hex'])) {
                $extra['hex'] = strtoupper((string) $extra['hex']);
            }

            if ($request->hasFile('swatch_image')) {
                $extra['image'] = $this->imageService->upload($request->file('swatch_image'), 'attribute-colors');
            }
        }

        unset($data['color_mode'], $data['swatch_image'], $data['extra_data']);

        return [
            'value' => $data['value'],
            'extra_data' => $extra !== [] ? $extra : null,
        ];
    }
}
