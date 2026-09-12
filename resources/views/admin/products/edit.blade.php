@extends('admin.layouts.app')

@section('title', 'Edit Product')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Product',
        'breadcrumbs' => [
            'Catalog' => null,
            'Products' => route('admin.products.index'),
            $product->name => route('admin.products.show', $product),
            'Edit',
        ],
        'actions' => '<a href="'.route('admin.products.show', $product).'" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>View</a>',
    ])

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills mb-4" id="editTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-basic" data-bs-toggle="pill"
                            data-bs-target="#pane-basic" type="button" role="tab">Basic</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-variants" data-bs-toggle="pill"
                            data-bs-target="#pane-variants" type="button" role="tab">Variants</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-images" data-bs-toggle="pill"
                            data-bs-target="#pane-images" type="button" role="tab">Images</button>
                </li>
            </ul>

            <form action="{{ route('admin.products.update', $product) }}" method="POST"
                  enctype="multipart/form-data" id="productEditForm" novalidate>
                @csrf
                @method('PUT')

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-basic" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $product->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id"
                                        class="form-select @error('category_id') is-invalid @enderror" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            @selected(old('category_id', $product->category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="brand_id" class="form-label">Brand</label>
                                <select name="brand_id" id="brand_id"
                                        class="form-select @error('brand_id') is-invalid @enderror">
                                    <option value="">— Select brand —</option>
                                </select>
                                @error('brand_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Brands are filtered by the selected category.</div>
                            </div>

                            <div class="col-12">
                                @include('admin.partials.html-composer', [
                                    'id' => 'description',
                                    'name' => 'description',
                                    'value' => old('description', $product->description),
                                    'label' => 'Full description / specifications',
                                    'invalid' => $errors->has('description'),
                                    'error' => $errors->first('description'),
                                    'hint' => 'Paste HTML directly (tables, lists, headings, inline styles). The same HTML is stored in the database and rendered in the app.',
                                ])
                            </div>

                            <div class="col-md-3">
                                <label for="base_price" class="form-label">MRP <span class="text-danger">*</span></label>
                                <input type="number" name="base_price" id="base_price" step="0.01" min="0"
                                       class="form-control @error('base_price') is-invalid @enderror"
                                       value="{{ old('base_price', $product->base_price) }}" placeholder="e.g. 29999" required>
                                @error('base_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="sale_price" class="form-label">Sale price</label>
                                <input type="number" name="sale_price" id="sale_price" step="0.01" min="0"
                                       class="form-control @error('sale_price') is-invalid @enderror"
                                       value="{{ old('sale_price', $product->sale_price) }}" placeholder="e.g. 24999">
                                @error('sale_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Leave blank if not on sale. Must be ≤ MRP.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                           value="1" @checked(old('is_featured', $product->is_featured))>
                                    <label class="form-check-label" for="is_featured">Featured</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="warranty_info" class="form-label">Product warranty</label>
                                <textarea name="warranty_info" id="warranty_info" rows="10"
                                          class="form-control @error('warranty_info') is-invalid @enderror"
                                          data-rich-editor="1"
                                          data-editor-height="360">{{ old('warranty_info', $product->warranty_info) }}</textarea>
                                @error('warranty_info')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Auto-fills from the selected brand. Edit here to change this product only — the brand warranty stays unchanged.
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle mb-3">
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="use_brand_policies" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               name="use_brand_policies" id="use_brand_policies" value="1"
                                               @checked(old('use_brand_policies', $product->use_brand_policies))>
                                        <label class="form-check-label fw-semibold" for="use_brand_policies">
                                            Use brand policies
                                        </label>
                                    </div>
                                    <div class="form-text mb-0">
                                        When checked, this product also shows all policies from the selected brand in the app.
                                    </div>
                                </div>
                                @include('admin.partials.policy-manager', [
                                    'owner' => 'product',
                                    'field' => 'policies',
                                    'policies' => $product->policies,
                                    'heading' => 'Product policies',
                                    'hint' => 'Add product-only policy cards (shown with brand policies when “Use brand policies” is on).',
                                ])
                            </div>

                            <div class="col-md-6">
                                <label for="meta_title" class="form-label">Meta title</label>
                                <input type="text" name="meta_title" id="meta_title"
                                       class="form-control @error('meta_title') is-invalid @enderror"
                                       value="{{ old('meta_title', $product->meta_title) }}">
                                @error('meta_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="meta_description" class="form-label">Meta description</label>
                                <input type="text" name="meta_description" id="meta_description"
                                       class="form-control @error('meta_description') is-invalid @enderror"
                                       value="{{ old('meta_description', $product->meta_description) }}">
                                @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-variants" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h6 mb-0">Product variants</h2>
                                <p class="small text-muted mb-0">Update existing variants or add new ones. Removed rows are deleted on save.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-soft" id="btnAddVariant">
                                <i class="bi bi-plus-lg me-1"></i>Add variant
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="editVariantsTable">
                                <thead>
                                    <tr>
                                        <th>Attributes</th>
                                        <th style="min-width: 130px;">SKU</th>
                                        <th style="min-width: 100px;">MRP</th>
                                        <th style="min-width: 100px;">Sale price</th>
                                        <th style="min-width: 80px;">Stock</th>
                                        <th style="min-width: 80px;">Low</th>
                                        <th>Status</th>
                                        <th style="min-width: 120px;">Image</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="editVariantsBody">
                                    @foreach ($product->variants as $index => $variant)
                                        @php
                                            $attrLabel = $variant->attributeValues
                                                ->map(fn ($v) => $v->value)
                                                ->implode(' / ') ?: 'Default';
                                        @endphp
                                        <tr data-variant-row>
                                            <td>
                                                <div class="small fw-medium">{{ $attrLabel }}</div>
                                                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                                                @foreach ($variant->attributeValues as $av)
                                                    <input type="hidden" name="variants[{{ $index }}][attribute_value_ids][]" value="{{ $av->id }}">
                                                @endforeach
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm"
                                                       name="variants[{{ $index }}][sku]"
                                                       value="{{ old('variants.'.$index.'.sku', $variant->sku) }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                                       name="variants[{{ $index }}][price]"
                                                       value="{{ old('variants.'.$index.'.price', $variant->price) }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                                       name="variants[{{ $index }}][discount_price]"
                                                       value="{{ old('variants.'.$index.'.discount_price', $variant->discount_price) }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" class="form-control form-control-sm"
                                                       name="variants[{{ $index }}][stock_quantity]"
                                                       value="{{ old('variants.'.$index.'.stock_quantity', $variant->stock_quantity) }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" class="form-control form-control-sm"
                                                       name="variants[{{ $index }}][low_stock_threshold]"
                                                       value="{{ old('variants.'.$index.'.low_stock_threshold', $variant->low_stock_threshold) }}">
                                            </td>
                                            <td>
                                                <select name="variants[{{ $index }}][status]" class="form-select form-select-sm">
                                                    <option value="1" @selected(old('variants.'.$index.'.status', $variant->status ? '1' : '0') == '1')>On</option>
                                                    <option value="0" @selected(old('variants.'.$index.'.status', $variant->status ? '1' : '0') === '0')>Off</option>
                                                </select>
                                            </td>
                                            <td>
                                                @if ($variant->images->first())
                                                    <img src="{{ asset('storage/'.$variant->images->first()->image_path) }}"
                                                         alt="" class="rounded border mb-1"
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                @endif
                                                <input type="file" accept="image/*" class="form-control form-control-sm"
                                                       name="variants[{{ $index }}][image]">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-edit-variant"
                                                        title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($product->variants->isEmpty())
                            <p class="text-muted small" id="noVariantsHint">No variants yet. Add one above.</p>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="pane-images" role="tabpanel">
                        @include('admin.partials.multi-image-uploader', [
                            'inputId' => 'images',
                            'inputName' => 'images[]',
                            'existingImages' => $product->images,
                            'help' => 'Drag cards or use arrows to set 1st / 2nd / 3rd display order. New photos can be mixed into the same sequence.',
                        ])
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary" id="btnSaveProduct">
                        <i class="bi bi-check-lg me-1"></i>Save changes
                    </button>
                    <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.partials.brand-dependent-dropdown')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let nextIndex = {{ $product->variants->count() }};
    const body = document.getElementById('editVariantsBody');
    const basePrice = @json((string) $product->base_price);
    const salePrice = @json((string) ($product->sale_price ?? ''));

    document.getElementById('btnAddVariant')?.addEventListener('click', function () {
        const i = nextIndex++;
        const tr = document.createElement('tr');
        tr.setAttribute('data-variant-row', '');
        tr.innerHTML =
            '<td><div class="small fw-medium">New variant</div></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="variants[' + i + '][sku]" value=""></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="variants[' + i + '][price]" value="' + basePrice + '"></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="variants[' + i + '][discount_price]" value="' + salePrice + '"></td>' +
            '<td><input type="number" min="0" class="form-control form-control-sm" name="variants[' + i + '][stock_quantity]" value="0"></td>' +
            '<td><input type="number" min="0" class="form-control form-control-sm" name="variants[' + i + '][low_stock_threshold]" value="5"></td>' +
            '<td><select name="variants[' + i + '][status]" class="form-select form-select-sm"><option value="1" selected>On</option><option value="0">Off</option></select></td>' +
            '<td><input type="file" accept="image/*" class="form-control form-control-sm" name="variants[' + i + '][image]"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-edit-variant" title="Remove"><i class="bi bi-trash"></i></button></td>';
        body.appendChild(tr);
        document.getElementById('noVariantsHint')?.remove();
    });

    body?.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-edit-variant');
        if (!btn) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const isExisting = !!btn.closest('tr')?.querySelector('input[name*="[id]"]');
        const remove = function () {
            btn.closest('tr')?.remove();
        };

        if (!isExisting) {
            remove();
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Remove variant',
                text: 'Remove this variant from the product?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, remove',
                reverseButtons: true,
                focusCancel: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    remove();
                }
            });
        } else if (window.confirm('Remove this variant?')) {
            remove();
        }
    });

    document.getElementById('category_id')?.addEventListener('change', function () {
        filterBrandsByCategory(this.value);
    });
    filterBrandsByCategory(document.getElementById('category_id')?.value || '', {
        keepBrandId: @json(old('brand_id', $product->brand_id)),
        forceKeep: true,
    });

    document.getElementById('brand_id')?.addEventListener('change', function () {
        const brandId = this.value;
        if (!brandId) return;
        fetch(@json(url('/admin/brands')) + '/' + brandId + '/warranty', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (json) {
                if (!json) return;
                const html = json.warranty || '';
                const editor = (typeof tinymce !== 'undefined') ? tinymce.get('warranty_info') : null;
                if (editor) {
                    editor.setContent(html);
                    editor.save();
                } else {
                    const ta = document.getElementById('warranty_info');
                    if (ta) ta.value = html;
                }
            })
            .catch(function () {});
    });

    document.getElementById('productEditForm')?.addEventListener('submit', function () {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        window.setButtonLoading(document.getElementById('btnSaveProduct'), true);
    });
});
</script>
@include('admin.partials.policy-manager-scripts')
@endpush
