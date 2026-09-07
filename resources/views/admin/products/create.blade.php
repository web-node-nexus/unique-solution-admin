@extends('admin.layouts.app')

@section('title', 'Add Product')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Product',
        'breadcrumbs' => [
            'Catalog' => null,
            'Products' => route('admin.products.index'),
            'Add',
        ],
    ])

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="product-wizard-steps" id="wizardSteps" role="list">
                <div class="wizard-step active" data-step="1" role="listitem">
                    <span class="wizard-step-num">1</span>
                    <span class="wizard-step-label">Basic</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="2" role="listitem">
                    <span class="wizard-step-num">2</span>
                    <span class="wizard-step-label">Attributes</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="3" role="listitem">
                    <span class="wizard-step-num">3</span>
                    <span class="wizard-step-label">Variants</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="4" role="listitem">
                    <span class="wizard-step-num">4</span>
                    <span class="wizard-step-label">Review</span>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data"
          id="productWizardForm" novalidate>
        @csrf

        {{-- Step 1: Basic --}}
        <div class="wizard-pane" data-pane="1">
            <div class="card">
                <div class="card-header">Basic information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
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
                                    class="form-select @error('brand_id') is-invalid @enderror"
                                    @disabled(! old('category_id'))>
                                <option value="">{{ old('category_id') ? '— Select brand —' : 'Select category first' }}</option>
                            </select>
                            @error('brand_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Brands are filtered by the selected category.</div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">
                                Full description / specifications
                            </label>
                            <textarea name="description" id="description" rows="18"
                                      class="form-control @error('description') is-invalid @enderror"
                                      data-rich-editor="1">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Use the editor for headings, bullet lists, and specification tables
                                (RAM, storage, dimensions, warranty, etc.). Very long content is supported
                                (tens of thousands of words — stored as LONGTEXT).
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="base_price" class="form-label">MRP <span class="text-danger">*</span></label>
                            <input type="number" name="base_price" id="base_price" step="0.01" min="0"
                                   class="form-control @error('base_price') is-invalid @enderror"
                                   value="{{ old('base_price') }}" placeholder="e.g. 29999" required>
                            @error('base_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="sale_price" class="form-label">Sale price</label>
                            <input type="number" name="sale_price" id="sale_price" step="0.01" min="0"
                                   class="form-control @error('sale_price') is-invalid @enderror"
                                   value="{{ old('sale_price') }}" placeholder="e.g. 24999">
                            @error('sale_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave blank if not on sale. Must be ≤ MRP.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="hidden" name="is_featured" value="0">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                       value="1" @checked(old('is_featured'))>
                                <label class="form-check-label" for="is_featured">Featured</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="warranty_info" class="form-label">Product warranty</label>
                            <textarea name="warranty_info" id="warranty_info" rows="10"
                                      class="form-control @error('warranty_info') is-invalid @enderror"
                                      data-rich-editor="1"
                                      data-editor-height="360">{{ old('warranty_info') }}</textarea>
                            @error('warranty_info')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Choosing a brand auto-fills this from the brand warranty. You can edit, add, or remove text — saving updates this product only, not the brand.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="meta_title" class="form-label">Meta title</label>
                            <input type="text" name="meta_title" id="meta_title"
                                   class="form-control @error('meta_title') is-invalid @enderror"
                                   value="{{ old('meta_title') }}">
                            @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="meta_description" class="form-label">Meta description</label>
                            <input type="text" name="meta_description" id="meta_description"
                                   class="form-control @error('meta_description') is-invalid @enderror"
                                   value="{{ old('meta_description') }}">
                            @error('meta_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            @include('admin.partials.multi-image-uploader', [
                                'inputId' => 'images',
                                'inputName' => 'images[]',
                                'help' => 'Upload photos, then drag or use arrows to set 1st, 2nd, 3rd display order. Max 5MB each.',
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Attributes --}}
        <div class="wizard-pane d-none" data-pane="2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Category attributes</span>
                    <span class="small text-muted">Select values to include in variant generation</span>
                </div>
                <div class="card-body" id="attributesStepBody">
                    <div class="text-muted" id="attributesPlaceholder">
                        Choose a category in step 1 to load attributes.
                    </div>
                </div>
            </div>
            <p class="small text-muted mt-2 mb-0">
                Attributes with no selected values are skipped. Only checked values are used for the cartesian product.
            </p>
        </div>

        {{-- Step 3: Variants --}}
        <div class="wizard-pane d-none" data-pane="3">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Variants</span>
                    <button type="button" class="btn btn-sm btn-soft" id="btnGenerateVariants">
                        <i class="bi bi-magic me-1"></i>Generate Variants
                    </button>
                </div>
                <div class="card-body">
                    <div id="variantsEmptyHint" class="text-muted small mb-3">
                        Click <strong>Generate Variants</strong> to build SKUs from your selected attribute values.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="variantsTable">
                            <thead>
                                <tr>
                                    <th>Attributes</th>
                                    <th style="min-width: 140px;">SKU</th>
                                    <th style="min-width: 100px;">MRP</th>
                                    <th style="min-width: 100px;">Sale price</th>
                                    <th style="min-width: 90px;">Stock</th>
                                    <th style="min-width: 130px;">Image</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="variantsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4: Review --}}
        <div class="wizard-pane d-none" data-pane="4">
            <div class="card mb-3">
                <div class="card-header">Review &amp; publish</div>
                <div class="card-body">
                    <dl class="row mb-0" id="reviewSummary">
                        <dt class="col-sm-3">Name</dt>
                        <dd class="col-sm-9" data-review="name">—</dd>
                        <dt class="col-sm-3">Category</dt>
                        <dd class="col-sm-9" data-review="category">—</dd>
                        <dt class="col-sm-3">Brand</dt>
                        <dd class="col-sm-9" data-review="brand">—</dd>
                        <dt class="col-sm-3">MRP</dt>
                        <dd class="col-sm-9" data-review="base_price">—</dd>
                        <dt class="col-sm-3">Sale price</dt>
                        <dd class="col-sm-9" data-review="sale_price">—</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9" data-review="status">—</dd>
                        <dt class="col-sm-3">Featured</dt>
                        <dd class="col-sm-9" data-review="featured">—</dd>
                        <dt class="col-sm-3">Images</dt>
                        <dd class="col-sm-9" data-review="images">—</dd>
                        <dt class="col-sm-3">Variants</dt>
                        <dd class="col-sm-9" data-review="variant_count">0</dd>
                    </dl>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Variant preview</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Attributes</th>
                                    <th>SKU</th>
                                    <th>MRP</th>
                                    <th>Sale price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody id="reviewVariantsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
            <div>
                <button type="button" class="btn btn-outline-secondary d-none" id="btnWizardPrev">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-link text-muted">Cancel</a>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" id="btnWizardNext">
                    Next<i class="bi bi-arrow-right ms-1"></i>
                </button>
                <button type="submit" class="btn btn-primary d-none" id="btnWizardSubmit">
                    <i class="bi bi-check-lg me-1"></i>Publish product
                </button>
            </div>
        </div>
    </form>
@endsection

@push('styles')
<style>
    .product-wizard-steps {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .wizard-step {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
        font-weight: 500;
        font-size: 0.9rem;
    }
    .wizard-step-num {
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e2e8f0;
        color: #475569;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .wizard-step.active { color: #0f766e; }
    .wizard-step.active .wizard-step-num {
        background: #0d9488;
        color: #fff;
    }
    .wizard-step.done .wizard-step-num {
        background: #99f6e4;
        color: #0f766e;
    }
    .wizard-step-line {
        width: 2.5rem;
        height: 2px;
        background: #e2e8f0;
    }
    .swatch-preview {
        width: 22px;
        height: 22px;
        border-radius: 0.35rem;
        border: 1px solid #cbd5e1;
        display: inline-block;
        vertical-align: middle;
    }
    .color-choice-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .color-choice {
        position: relative;
        width: 112px;
        margin: 0;
        cursor: pointer;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.85rem;
        overflow: hidden;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .12s ease;
    }
    .color-choice:hover {
        border-color: #99f6e4;
        transform: translateY(-1px);
    }
    .color-choice input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .color-choice.is-selected {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.18);
    }
    .color-choice-media {
        display: block;
        width: 100%;
        height: 84px;
        object-fit: cover;
        background: #f1f5f9;
    }
    .color-choice-swatch {
        display: block;
        width: 100%;
        height: 84px;
    }
    .color-choice-name {
        display: block;
        padding: 0.4rem 0.45rem 0.5rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #334155;
        text-align: center;
        line-height: 1.25;
    }
    .color-choice-check {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #0d9488;
        color: #fff;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }
    .color-choice.is-selected .color-choice-check { display: inline-flex; }
    .attr-block {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
    }
    .attr-block h3 {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0.65rem;
    }
</style>
@endpush

@push('scripts')
@include('admin.partials.brand-dependent-dropdown')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const TOTAL_STEPS = 4;
    let currentStep = 1;
    let categoryAttributes = [];
    let variantIndex = 0;

    const form = document.getElementById('productWizardForm');
    const attributesBody = document.getElementById('attributesStepBody');
    const variantsBody = document.getElementById('variantsBody');
    const categoryAttrsBase = @json(url('admin/products/category'));

    // Rich editor loaded via shared partial (initUniqueSolutionEditor)

    function slugifySkuPart(str) {
        return String(str || '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 24);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showStep(step) {
        currentStep = step;
        document.querySelectorAll('.wizard-pane').forEach(function (pane) {
            const n = parseInt(pane.getAttribute('data-pane'), 10);
            pane.classList.toggle('d-none', n !== step);
        });
        document.querySelectorAll('#wizardSteps .wizard-step').forEach(function (el) {
            const n = parseInt(el.getAttribute('data-step'), 10);
            el.classList.toggle('active', n === step);
            el.classList.toggle('done', n < step);
        });
        document.getElementById('btnWizardPrev').classList.toggle('d-none', step === 1);
        document.getElementById('btnWizardNext').classList.toggle('d-none', step === TOTAL_STEPS);
        document.getElementById('btnWizardSubmit').classList.toggle('d-none', step !== TOTAL_STEPS);

        if (step === 4) {
            buildReview();
        }
    }

    function validateStep(step) {
        if (step === 1) {
            const name = document.getElementById('name').value.trim();
            const categoryId = document.getElementById('category_id').value;
            const basePrice = document.getElementById('base_price').value;
            const status = document.getElementById('status').value;

            if (!name) {
                toastr.error('Product name is required');
                document.getElementById('name').focus();
                return false;
            }
            if (!categoryId) {
                toastr.error('Please select a category');
                document.getElementById('category_id').focus();
                return false;
            }
            if (basePrice === '' || Number(basePrice) < 0) {
                toastr.error('Enter a valid MRP');
                document.getElementById('base_price').focus();
                return false;
            }
            const salePrice = document.getElementById('sale_price').value;
            if (salePrice !== '' && Number(salePrice) > Number(basePrice)) {
                toastr.error('Sale price cannot be greater than MRP');
                document.getElementById('sale_price').focus();
                return false;
            }
            if (!status) {
                toastr.error('Status is required');
                return false;
            }
            return true;
        }

        if (step === 3) {
            const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
            if (!rows.length) {
                toastr.error('Generate or add at least one variant');
                return false;
            }
            let ok = true;
            rows.forEach(function (row) {
                const price = row.querySelector('.v-price');
                if (price && price.value === '') {
                    ok = false;
                    price.classList.add('is-invalid');
                }
            });
            if (!ok) {
                toastr.error('Each variant needs a price');
                return false;
            }
            return true;
        }

        return true;
    }

    function renderAttributes(attributes) {
        categoryAttributes = attributes || [];
        attributesBody.innerHTML = '';

        if (!categoryAttributes.length) {
            attributesBody.innerHTML =
                '<div class="text-muted">This category has no attributes. You can skip to variants and add a default SKU.</div>';
            return;
        }

        categoryAttributes.forEach(function (attr) {
            const block = document.createElement('div');
            block.className = 'attr-block';
            block.setAttribute('data-attr-id', attr.id);

            let valuesHtml = '';
            if (attr.type === 'color-swatch') {
                valuesHtml = '<div class="color-choice-grid">';
                (attr.values || []).forEach(function (val) {
                    const hex = (val.extra_data && (val.extra_data.hex || val.extra_data.color)) || null;
                    const img = val.image_url || (val.extra_data && val.extra_data.image_url) || null;
                    const media = img
                        ? '<img class="color-choice-media" src="' + escapeHtml(img) + '" alt="' + escapeHtml(val.value) + '">'
                        : '<span class="color-choice-swatch" style="background:' + escapeHtml(hex || '#cbd5e1') + ';"></span>';
                    valuesHtml +=
                        '<label class="color-choice" data-color-choice>' +
                        '<input class="attr-value-check" type="checkbox"' +
                        ' data-attr-id="' + attr.id + '"' +
                        ' data-attr-name="' + escapeHtml(attr.name) + '"' +
                        ' data-value-id="' + val.id + '"' +
                        ' data-value-label="' + escapeHtml(val.value) + '"' +
                        ' id="av_' + attr.id + '_' + val.id + '" value="' + val.id + '">' +
                        '<span class="color-choice-check"><i class="bi bi-check-lg"></i></span>' +
                        media +
                        '<span class="color-choice-name">' + escapeHtml(val.value) + '</span>' +
                        '</label>';
                });
                valuesHtml += '</div>';
                if (!(attr.values || []).length) {
                    valuesHtml = '<span class="small text-muted">No colors defined. Add them on the Color attribute.</span>';
                }
            } else {
                (attr.values || []).forEach(function (val) {
                    valuesHtml +=
                        '<div class="form-check form-check-inline mb-1">' +
                        '<input class="form-check-input attr-value-check" type="checkbox"' +
                        ' data-attr-id="' + attr.id + '"' +
                        ' data-attr-name="' + escapeHtml(attr.name) + '"' +
                        ' data-value-id="' + val.id + '"' +
                        ' data-value-label="' + escapeHtml(val.value) + '"' +
                        ' id="av_' + attr.id + '_' + val.id + '" value="' + val.id + '">' +
                        '<label class="form-check-label" for="av_' + attr.id + '_' + val.id + '">' +
                        escapeHtml(val.value) +
                        '</label></div>';
                });
            }

            if (!valuesHtml) {
                valuesHtml = '<span class="small text-muted">No values defined for this attribute.</span>';
            }

            block.innerHTML =
                '<h3>' + escapeHtml(attr.name) +
                ' <span class="badge text-bg-light border fw-normal">' + escapeHtml(attr.type) + '</span></h3>' +
                '<div class="attr-values">' + valuesHtml + '</div>';
            attributesBody.appendChild(block);
        });

        attributesBody.querySelectorAll('[data-color-choice]').forEach(function (label) {
            const input = label.querySelector('input');
            const sync = function () {
                label.classList.toggle('is-selected', !!input.checked);
            };
            input?.addEventListener('change', sync);
            sync();
        });
    }

    function loadCategoryAttributes(categoryId) {
        if (!categoryId) {
            categoryAttributes = [];
            attributesBody.innerHTML =
                '<div class="text-muted" id="attributesPlaceholder">Choose a category in step 1 to load attributes.</div>';
            return;
        }

        attributesBody.innerHTML = '<div class="text-muted">Loading attributes…</div>';
        const url = categoryAttrsBase + '/' + encodeURIComponent(categoryId) + '/attributes';

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Failed to load attributes');
                }
                return res.json();
            })
            .then(function (json) {
                renderAttributes(json.attributes || []);
            })
            .catch(function () {
                categoryAttributes = [];
                attributesBody.innerHTML =
                    '<div class="text-danger">Could not load category attributes. Try again.</div>';
                toastr.error('Failed to load category attributes');
            });
    }

    document.getElementById('category_id').addEventListener('change', function () {
        loadCategoryAttributes(this.value);
        variantsBody.innerHTML = '';
        variantIndex = 0;
        document.getElementById('variantsEmptyHint')?.classList.remove('d-none');
        filterBrandsByCategory(this.value);
    });

    function setWarrantyEditorContent(html) {
        const editor = (typeof tinymce !== 'undefined') ? tinymce.get('warranty_info') : null;
        if (editor) {
            editor.setContent(html || '');
            editor.save();
            return;
        }
        const ta = document.getElementById('warranty_info');
        if (ta) ta.value = html || '';
    }

    function fillWarrantyFromBrand(brandId) {
        if (!brandId) {
            return;
        }
        fetch(@json(url('/admin/brands')) + '/' + brandId + '/warranty', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (json) {
                if (!json) return;
                setWarrantyEditorContent(json.warranty || '');
            })
            .catch(function () {});
    }

    document.getElementById('brand_id')?.addEventListener('change', function () {
        fillWarrantyFromBrand(this.value);
    });

    if (!@json(old('warranty_info')) && document.getElementById('brand_id')?.value) {
        fillWarrantyFromBrand(document.getElementById('brand_id').value);
    }

    function getSelectedAttributeGroups() {
        const groups = {};
        document.querySelectorAll('.attr-value-check:checked').forEach(function (el) {
            const attrId = el.getAttribute('data-attr-id');
            if (!groups[attrId]) {
                groups[attrId] = {
                    attrId: attrId,
                    attrName: el.getAttribute('data-attr-name'),
                    values: [],
                };
            }
            groups[attrId].values.push({
                id: parseInt(el.getAttribute('data-value-id'), 10),
                label: el.getAttribute('data-value-label'),
            });
        });
        return Object.values(groups).filter(function (g) {
            return g.values.length > 0;
        });
    }

    function cartesian(groups) {
        if (!groups.length) {
            return [[]];
        }
        return groups.reduce(function (acc, group) {
            const next = [];
            acc.forEach(function (combo) {
                group.values.forEach(function (val) {
                    next.push(combo.concat([{
                        attrId: group.attrId,
                        attrName: group.attrName,
                        id: val.id,
                        label: val.label,
                    }]));
                });
            });
            return next;
        }, [[]]);
    }

    function suggestSku(combo) {
        const namePart = slugifySkuPart(document.getElementById('name').value) || 'SKU';
        const valuePart = combo.map(function (v) {
            return slugifySkuPart(v.label);
        }).filter(Boolean).join('-');
        return valuePart ? (namePart + '-' + valuePart) : namePart;
    }

    function addVariantRow(combo, opts) {
        opts = opts || {};
        const i = variantIndex++;
        const label = combo.length
            ? combo.map(function (v) { return v.attrName + ': ' + v.label; }).join(' / ')
            : 'Default';
        const mrp = document.getElementById('base_price').value || '0';
        const salePrice = document.getElementById('sale_price')?.value || '';
        const sku = opts.sku || suggestSku(combo);
        const tr = document.createElement('tr');
        tr.setAttribute('data-variant-row', '');

        let hiddenAttrs = '';
        combo.forEach(function (v) {
            hiddenAttrs +=
                '<input type="hidden" name="variants[' + i + '][attribute_value_ids][]" value="' + v.id + '">';
        });

        tr.innerHTML =
            '<td>' +
                '<div class="small fw-medium">' + escapeHtml(label) + '</div>' +
                hiddenAttrs +
            '</td>' +
            '<td><input type="text" class="form-control form-control-sm v-sku" name="variants[' + i + '][sku]" value="' + escapeHtml(sku) + '"></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm v-price" name="variants[' + i + '][price]" value="' + escapeHtml(String(opts.price != null ? opts.price : mrp)) + '" required></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="variants[' + i + '][discount_price]" value="' + escapeHtml(String(opts.discount_price != null ? opts.discount_price : salePrice)) + '"></td>' +
            '<td><input type="number" min="0" class="form-control form-control-sm" name="variants[' + i + '][stock_quantity]" value="' + escapeHtml(String(opts.stock_quantity != null ? opts.stock_quantity : 0)) + '"></td>' +
            '<td><input type="file" accept="image/*" class="form-control form-control-sm" name="variants[' + i + '][image]"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant" title="Remove"><i class="bi bi-trash"></i></button></td>';

        variantsBody.appendChild(tr);
        document.getElementById('variantsEmptyHint')?.classList.add('d-none');
    }

    function generateVariants() {
        const groups = getSelectedAttributeGroups();
        const combos = cartesian(groups);

        variantsBody.innerHTML = '';
        variantIndex = 0;

        if (!groups.length) {
            addVariantRow([]);
            toastr.info('No attribute values selected — added one variant');
            return;
        }

        combos.forEach(function (combo) {
            addVariantRow(combo);
        });
        toastr.success('Generated ' + combos.length + ' variant(s)');
    }

    document.getElementById('btnGenerateVariants').addEventListener('click', generateVariants);

    variantsBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-variant');
        if (!btn) {
            return;
        }
        btn.closest('tr')?.remove();
        if (!variantsBody.querySelector('tr[data-variant-row]')) {
            document.getElementById('variantsEmptyHint')?.classList.remove('d-none');
        }
    });

    function buildReview() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        const catSelect = document.getElementById('category_id');
        const brandSelect = document.getElementById('brand_id');
        const imagesInput = document.getElementById('images');
        const uploaderRoot = document.querySelector('[data-uploader="images"]');
        const imageCount = (uploaderRoot && typeof uploaderRoot._getNewImageCount === 'function')
            ? uploaderRoot._getNewImageCount()
            : (imagesInput?.files?.length || 0);

        document.querySelector('[data-review="name"]').textContent =
            document.getElementById('name').value.trim() || '—';
        document.querySelector('[data-review="category"]').textContent =
            catSelect.options[catSelect.selectedIndex]?.text || '—';
        document.querySelector('[data-review="brand"]').textContent =
            brandSelect.value ? (brandSelect.options[brandSelect.selectedIndex]?.text || '—') : 'None';
        document.querySelector('[data-review="base_price"]').textContent =
            document.getElementById('base_price').value || '—';
        document.querySelector('[data-review="sale_price"]').textContent =
            document.getElementById('sale_price').value || '—';
        document.querySelector('[data-review="status"]').textContent =
            document.getElementById('status').value || '—';
        document.querySelector('[data-review="featured"]').textContent =
            document.getElementById('is_featured').checked ? 'Yes' : 'No';
        document.querySelector('[data-review="images"]').textContent =
            imageCount > 0 ? (imageCount + ' file(s)') : 'None';

        const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
        document.querySelector('[data-review="variant_count"]').textContent = String(rows.length);

        const reviewBody = document.getElementById('reviewVariantsBody');
        reviewBody.innerHTML = '';
        if (!rows.length) {
            reviewBody.innerHTML = '<tr><td colspan="5" class="text-muted text-center py-3">No variants</td></tr>';
            return;
        }
        rows.forEach(function (row) {
            const label = row.querySelector('.small.fw-medium')?.textContent || '—';
            const sku = row.querySelector('.v-sku')?.value || '—';
            const price = row.querySelector('.v-price')?.value || '—';
            const discount = row.querySelector('input[name*="[discount_price]"]')?.value || '—';
            const stock = row.querySelector('input[name*="[stock_quantity]"]')?.value || '0';
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + escapeHtml(label) + '</td>' +
                '<td><code>' + escapeHtml(sku) + '</code></td>' +
                '<td>' + escapeHtml(price) + '</td>' +
                '<td>' + escapeHtml(discount || '—') + '</td>' +
                '<td>' + escapeHtml(stock) + '</td>';
            reviewBody.appendChild(tr);
        });
    }

    document.getElementById('btnWizardNext').addEventListener('click', function () {
        if (!validateStep(currentStep)) {
            return;
        }
        if (currentStep === 1 && document.getElementById('category_id').value && !categoryAttributes.length) {
            loadCategoryAttributes(document.getElementById('category_id').value);
        }
        if (currentStep === 2 && !variantsBody.querySelector('tr[data-variant-row]')) {
            // Auto-generate when entering variants if empty
            const groups = getSelectedAttributeGroups();
            if (groups.length) {
                generateVariants();
            }
        }
        showStep(Math.min(TOTAL_STEPS, currentStep + 1));
    });

    document.getElementById('btnWizardPrev').addEventListener('click', function () {
        showStep(Math.max(1, currentStep - 1));
    });

    form.addEventListener('submit', function (e) {
        if (currentStep !== TOTAL_STEPS) {
            e.preventDefault();
            return;
        }
        if (!validateStep(1) || !validateStep(3)) {
            e.preventDefault();
            return;
        }
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        window.setButtonLoading(document.getElementById('btnWizardSubmit'), true);
    });

    // Prefill attributes if old category present
    const initialCategory = document.getElementById('category_id').value;
    if (initialCategory) {
        loadCategoryAttributes(initialCategory);
        filterBrandsByCategory(initialCategory, {
            keepBrandId: @json(old('brand_id')),
            forceKeep: true,
        });
    } else {
        filterBrandsByCategory('');
    }

    showStep(1);
});
</script>
@endpush
