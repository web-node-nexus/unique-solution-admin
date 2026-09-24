@extends('admin.layouts.app')

@section('title', 'Add Product')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Product',
        'breadcrumbs' => [
            'Products' => route('admin.products.index'),
            'Add',
        ],
    ])

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="product-wizard-steps" id="wizardSteps" role="list">
                <div class="wizard-step active" data-step="1" role="listitem">
                    <span class="wizard-step-num">1</span>
                    <span class="wizard-step-label">Page 1 Basic Detail</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="2" role="listitem">
                    <span class="wizard-step-num">2</span>
                    <span class="wizard-step-label">Page 2 Variant &amp; Multiple Image</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="3" role="listitem">
                    <span class="wizard-step-num">3</span>
                    <span class="wizard-step-label">Page 3 MRP &amp; Pricing</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="4" role="listitem">
                    <span class="wizard-step-num">4</span>
                    <span class="wizard-step-label">Page 4 Review &amp; Save</span>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data"
          id="productWizardForm" data-publish-form="product" novalidate>
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Could not save product — please fix:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Product-level prices filled from variant matrix before submit --}}
        <input type="hidden" name="base_price" id="base_price" value="{{ old('base_price', '0') }}">
        <input type="hidden" name="sale_price" id="sale_price" value="{{ old('sale_price') }}">

        {{-- Step 1: Basic Detail --}}
        <div class="wizard-pane" data-pane="1">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-2">Step 1 / 4</span>
                    <h2 class="h5 mb-1">1. Product Basic Detail</h2>
                    <p class="text-muted small mb-0">Set product name, category, brand, description, and policies here.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">1. Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Samsung Galaxy M34 5G (Super AMOLED Display, 6000mAh Battery)"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">2. Category <span class="text-danger">*</span></label>
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
                        <div class="col-md-6">
                            <label for="brand_id" class="form-label">3. Brand</label>
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
                            @include('admin.partials.html-composer', [
                                'id' => 'description',
                                'name' => 'description',
                                'value' => old('description'),
                                'label' => '4. Description',
                                'invalid' => $errors->has('description'),
                                'error' => $errors->first('description'),
                                'hint' => 'Write specs in English (Display, Camera, Battery, Processor, etc.). HTML is stored and shown in the app.',
                            ])
                        </div>

                        <div class="col-12">
                            <div class="fw-semibold mb-2">5. Policy</div>
                            @include('admin.partials.product-policy-picker', [
                                'initialBrandId' => old('brand_id'),
                                'selectedIds' => old('brand_policy_ids', []),
                                'autoSelectAll' => old('brand_policy_ids') === null,
                            ])
                        </div>

                        <div class="col-12">
                            <label for="warranty_info" class="form-label">Product warranty (optional notes)</label>
                            <textarea name="warranty_info" id="warranty_info" rows="6"
                                      class="form-control @error('warranty_info') is-invalid @enderror"
                                      data-rich-editor="1"
                                      data-editor-height="220">{{ old('warranty_info') }}</textarea>
                            @error('warranty_info')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Choosing a brand auto-fills this from the brand warranty. Edits apply to this product only.
                            </div>
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.publish-toggle', [
                                'name' => 'status',
                                'id' => 'status',
                                'onValue' => 'active',
                                'offValue' => 'inactive',
                                'checked' => old('status', 'inactive') === 'active',
                            ])
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="hidden" name="is_featured" value="0">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                       value="1" @checked(old('is_featured'))>
                                <label class="form-check-label" for="is_featured">Featured product</label>
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
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Variants & Multiple Images --}}
        <div class="wizard-pane d-none" data-pane="2">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis border border-primary-subtle mb-2">Step 2 / 4</span>
                    <h2 class="h5 mb-1">2. Variants &amp; Multiple Images</h2>
                    <p class="text-muted small mb-0">
                        Select RAM, storage, color (and other attributes), generate variants, then upload a thumbnail / gallery per variant. The first photo is the customer thumbnail.
                    </p>
                </div>
                <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle align-self-center" id="activeVariantBadge">0 active variants</span>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Variant selection (attributes)</span>
                    <span class="small text-muted">Select values to include in variant generation</span>
                </div>
                <div class="card-body" id="attributesStepBody">
                    <div class="text-muted" id="attributesPlaceholder">
                        Choose a category in step 1 to load attributes.
                    </div>
                </div>
            </div>

            <div id="variantsMountStep2"></div>

            <div class="card mt-3">
                <div class="card-header">Shared product gallery (optional)</div>
                <div class="card-body">
                    @include('admin.partials.multi-image-uploader', [
                        'inputId' => 'images',
                        'inputName' => 'images[]',
                        'help' => 'Optional product-level photos. Prefer per-variant images above. Max 5MB each.',
                    ])
                </div>
            </div>
        </div>

        {{-- Step 3: MRP & Pricing --}}
        <div class="wizard-pane d-none" data-pane="3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-2">Step 3 / 4</span>
                    <h2 class="h5 mb-1">3. MRP &amp; Pricing</h2>
                    <p class="text-muted small mb-0">
                        Set MRP, selling price, discount, and stock for every variant. Currency: ₹ (INR).
                    </p>
                </div>
                <button type="button" class="btn btn-warning btn-sm" id="btnApplyDiscountAll">
                    Apply 15% discount to all
                </button>
            </div>
            <div id="variantsMountStep3"></div>
            <div class="d-flex flex-wrap justify-content-between gap-2 mt-2 small text-muted" id="pricingFooterStats">
                <span>Total variants: <strong data-stat="variant_total">0</strong> · Total stock: <strong data-stat="stock_total">0</strong> units</span>
                <span>Note: Selling price can never be more than MRP.</span>
            </div>
        </div>

        {{-- Shared variants card (moved between step 2 and 3) --}}
        <div id="variantsSharedCard" class="card d-none">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span id="variantsCardTitle">Variant &amp; multiple image list (first image = thumbnail)</span>
                <button type="button" class="btn btn-sm btn-primary" id="btnGenerateVariants">
                    <i class="bi bi-plus-lg me-1"></i>Add / generate variants
                </button>
            </div>
            <div class="card-body">
                <div id="variantsEmptyHint" class="text-muted small mb-3">
                    Select attribute values above, then click <strong>Add / generate variants</strong>.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle wizard-variants-table" id="variantsTable">
                        <thead>
                            <tr>
                                <th>Variant</th>
                                <th class="col-sku" style="min-width: 140px;">SKU</th>
                                <th class="col-pricing" style="min-width: 100px;">MRP (₹) *</th>
                                <th class="col-pricing" style="min-width: 110px;">Selling price (₹) *</th>
                                <th class="col-pricing" style="min-width: 90px;">Discount %</th>
                                <th class="col-pricing" style="min-width: 90px;">Stock</th>
                                <th class="col-pricing" style="min-width: 80px;">Status</th>
                                <th class="col-image" style="min-width: 130px;">Image</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="variantsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Step 4: Review & Save --}}
        <div class="wizard-pane d-none" data-pane="4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle mb-2">Step 4 / 4</span>
                    <h2 class="h5 mb-1">4. Review &amp; Save Product</h2>
                    <p class="text-muted small mb-0">Check all details, variants, images, and MRP, then save &amp; publish.</p>
                </div>
            </div>

            <div class="row g-3 mb-3" id="reviewSummaryCards">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="small text-muted mb-1">Product</div>
                            <div class="fw-semibold text-truncate" data-review="name">—</div>
                            <div class="small text-muted mt-1">
                                Brand: <span data-review="brand">—</span> · Category: <span data-review="category">—</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="small text-muted mb-1">Variants &amp; photos</div>
                            <div class="fw-semibold text-primary" data-review="variant_count_label">0 active variants ready</div>
                            <div class="small text-muted mt-1" data-review="images">No shared gallery images</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="small text-muted mb-1">Pricing range</div>
                            <div class="fw-semibold text-success" data-review="price_range">₹—</div>
                            <div class="small text-muted mt-1">MRP range: <span data-review="mrp_range">₹—</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-primary-subtle">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">App preview</div>
                        <div class="small text-muted mb-0">Check how this product will look in the app before publishing.</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary js-app-preview" data-preview="product">
                        <i class="bi bi-phone me-1"></i>Preview in app
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Variant pricing preview</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Variant</th>
                                    <th>SKU</th>
                                    <th>MRP</th>
                                    <th>Selling price</th>
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
                    <i class="bi bi-arrow-left me-1"></i><span id="btnWizardPrevLabel">Back</span>
                </button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary" id="btnWizardCancel">Cancel</a>
            </div>
            <div class="d-flex gap-2 align-items-center">
                @include('admin.partials.preview-button', [
                    'type' => 'product',
                    'wrapId' => 'wizardPreviewWrap',
                    'hidden' => true,
                    'hint' => 'Final check before save',
                ])
                <button type="button" class="btn btn-primary" id="btnWizardNext">
                    <span id="btnWizardNextLabel">Next (Page 2 — Variant &amp; Image)</span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>
                <button type="submit" class="btn btn-success d-none" id="btnWizardSubmit">
                    <i class="bi bi-check-lg me-1"></i>Save &amp; publish product
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
    .wizard-variants-table.mode-images .col-pricing,
    .wizard-variants-table.mode-images td.col-pricing {
        display: none;
    }
    .wizard-variants-table.mode-pricing .col-image,
    .wizard-variants-table.mode-pricing td.col-image {
        display: none;
    }
    .wizard-step.done .wizard-step-num::after {
        content: none;
    }
    .wizard-discount-display {
        min-width: 4.5rem;
        font-weight: 600;
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 0.4rem;
        padding: 0.35rem 0.5rem;
        text-align: center;
        font-size: 0.8rem;
    }
    .wizard-status-badge {
        display: inline-block;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 0.75rem;
        font-weight: 600;
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

    function mountVariantsCard(step) {
        const card = document.getElementById('variantsSharedCard');
        const table = document.getElementById('variantsTable');
        const title = document.getElementById('variantsCardTitle');
        if (!card || !table) return;

        card.classList.remove('d-none');
        table.classList.remove('mode-images', 'mode-pricing');

        if (step === 2) {
            document.getElementById('variantsMountStep2')?.appendChild(card);
            table.classList.add('mode-images');
            if (title) title.textContent = 'Variant & multiple image list (first image = thumbnail)';
        } else if (step === 3) {
            document.getElementById('variantsMountStep3')?.appendChild(card);
            table.classList.add('mode-pricing');
            if (title) title.textContent = 'Variant pricing matrix';
            refreshPricingStats();
        } else {
            card.classList.add('d-none');
        }
    }

    function formatInr(n) {
        const num = Number(n);
        if (!isFinite(num)) return '—';
        return '₹' + num.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    function syncProductPricesFromVariants() {
        const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
        let bestMrp = null;
        let bestSale = null;
        rows.forEach(function (row) {
            const mrp = Number(row.querySelector('.v-price')?.value);
            const saleRaw = row.querySelector('.v-sale')?.value;
            const sale = saleRaw === '' ? null : Number(saleRaw);
            if (!isFinite(mrp) || mrp < 0) return;
            if (bestMrp === null || mrp < bestMrp) {
                bestMrp = mrp;
                bestSale = (sale !== null && isFinite(sale) && sale >= 0 && sale <= mrp) ? sale : null;
            }
        });
        document.getElementById('base_price').value = bestMrp !== null ? String(bestMrp) : '0';
        document.getElementById('sale_price').value = bestSale !== null ? String(bestSale) : '';
    }

    function updateRowDiscount(row) {
        const mrp = Number(row.querySelector('.v-price')?.value);
        const sale = Number(row.querySelector('.v-sale')?.value);
        const el = row.querySelector('[data-discount-display]');
        if (!el) return;
        if (isFinite(mrp) && mrp > 0 && isFinite(sale) && sale >= 0 && sale < mrp) {
            const pct = Math.round(((mrp - sale) / mrp) * 100);
            el.textContent = pct + '% off';
        } else {
            el.textContent = '—';
        }
    }

    function refreshPricingStats() {
        const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
        let stock = 0;
        rows.forEach(function (row) {
            stock += Number(row.querySelector('.v-stock')?.value) || 0;
            updateRowDiscount(row);
        });
        const badge = document.getElementById('activeVariantBadge');
        if (badge) badge.textContent = rows.length + ' active variant' + (rows.length === 1 ? '' : 's');
        document.querySelector('[data-stat="variant_total"]') &&
            (document.querySelector('[data-stat="variant_total"]').textContent = String(rows.length));
        document.querySelector('[data-stat="stock_total"]') &&
            (document.querySelector('[data-stat="stock_total"]').textContent = String(stock));
        syncProductPricesFromVariants();
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
            const num = el.querySelector('.wizard-step-num');
            if (num) {
                num.textContent = n < step ? '✓' : String(n);
            }
        });
        document.getElementById('btnWizardPrev').classList.toggle('d-none', step === 1);
        document.getElementById('btnWizardCancel')?.classList.toggle('d-none', step !== 1);
        document.getElementById('btnWizardNext').classList.toggle('d-none', step === TOTAL_STEPS);
        document.getElementById('btnWizardSubmit').classList.toggle('d-none', step !== TOTAL_STEPS);
        document.getElementById('wizardPreviewWrap')?.classList.toggle('d-none', step !== TOTAL_STEPS);

        const nextLabels = {
            1: 'Next (Page 2 — Variant & Image)',
            2: 'Next (Page 3 — MRP & Pricing)',
            3: 'Next (Page 4 — Review & Save)',
        };
        const prevLabels = {
            2: 'Back: Page 1 — Basic Detail',
            3: 'Back: Page 2 — Variant & Image',
            4: 'Back: Page 3 — MRP & Pricing',
        };
        const nextLabel = document.getElementById('btnWizardNextLabel');
        const prevLabel = document.getElementById('btnWizardPrevLabel');
        if (nextLabel && nextLabels[step]) nextLabel.textContent = nextLabels[step];
        if (prevLabel && prevLabels[step]) prevLabel.textContent = prevLabels[step];

        mountVariantsCard(step);

        if (step === 4) {
            buildReview();
        }
    }

    function validateStep(step) {
        if (step === 1) {
            const name = document.getElementById('name').value.trim();
            const categoryId = document.getElementById('category_id').value;
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
            if (!status) {
                toastr.error('Status is required');
                return false;
            }
            return true;
        }

        if (step === 2) {
            const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
            if (!rows.length) {
                toastr.error('Generate or add at least one variant');
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
                const sale = row.querySelector('.v-sale');
                if (price && price.value === '') {
                    ok = false;
                    price.classList.add('is-invalid');
                } else if (price) {
                    price.classList.remove('is-invalid');
                }
                if (sale && sale.value !== '' && price && Number(sale.value) > Number(price.value)) {
                    ok = false;
                    sale.classList.add('is-invalid');
                } else if (sale) {
                    sale.classList.remove('is-invalid');
                }
            });
            if (!ok) {
                toastr.error('Each variant needs a valid MRP, and selling price must be ≤ MRP');
                return false;
            }
            syncProductPricesFromVariants();
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
        document.getElementById('brand_id')?.dispatchEvent(new Event('change', { bubbles: true }));
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
            ? combo.map(function (v) { return v.label; }).join(' · ')
            : 'Default';
        const sku = opts.sku || suggestSku(combo);
        const tr = document.createElement('tr');
        tr.setAttribute('data-variant-row', '');

        let hiddenAttrs = '';
        combo.forEach(function (v) {
            hiddenAttrs +=
                '<input type="hidden" name="variants[' + i + '][attribute_value_ids][]" value="' + v.id + '">';
        });

        const mrpVal = opts.price != null ? opts.price : '';
        const saleVal = opts.discount_price != null ? opts.discount_price : '';
        const stockVal = opts.stock_quantity != null ? opts.stock_quantity : 0;

        tr.innerHTML =
            '<td>' +
                '<div class="small fw-medium">' + escapeHtml(label) + '</div>' +
                hiddenAttrs +
                '<input type="hidden" name="variants[' + i + '][status]" value="1">' +
            '</td>' +
            '<td class="col-sku"><input type="text" class="form-control form-control-sm v-sku" name="variants[' + i + '][sku]" value="' + escapeHtml(sku) + '"></td>' +
            '<td class="col-pricing"><input type="number" step="0.01" min="0" class="form-control form-control-sm v-price" name="variants[' + i + '][price]" value="' + escapeHtml(String(mrpVal)) + '" placeholder="MRP"></td>' +
            '<td class="col-pricing"><input type="number" step="0.01" min="0" class="form-control form-control-sm v-sale" name="variants[' + i + '][discount_price]" value="' + escapeHtml(String(saleVal)) + '" placeholder="Selling"></td>' +
            '<td class="col-pricing"><div class="wizard-discount-display" data-discount-display>—</div></td>' +
            '<td class="col-pricing"><input type="number" min="0" class="form-control form-control-sm v-stock" name="variants[' + i + '][stock_quantity]" value="' + escapeHtml(String(stockVal)) + '"></td>' +
            '<td class="col-pricing"><span class="wizard-status-badge">Active</span></td>' +
            '<td class="col-image"><input type="file" accept="image/*" class="form-control form-control-sm" name="variants[' + i + '][image]"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant" title="Remove"><i class="bi bi-trash"></i></button></td>';

        variantsBody.appendChild(tr);
        document.getElementById('variantsEmptyHint')?.classList.add('d-none');
        updateRowDiscount(tr);
        refreshPricingStats();
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
        refreshPricingStats();
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
        refreshPricingStats();
    });

    variantsBody.addEventListener('input', function (e) {
        const row = e.target.closest('tr[data-variant-row]');
        if (!row) return;
        if (e.target.classList.contains('v-price') || e.target.classList.contains('v-sale') || e.target.classList.contains('v-stock')) {
            updateRowDiscount(row);
            refreshPricingStats();
        }
    });

    document.getElementById('btnApplyDiscountAll')?.addEventListener('click', function () {
        const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
        if (!rows.length) {
            toastr.warning('No variants to update');
            return;
        }
        rows.forEach(function (row) {
            const mrpInput = row.querySelector('.v-price');
            const saleInput = row.querySelector('.v-sale');
            const mrp = Number(mrpInput?.value);
            if (!isFinite(mrp) || mrp <= 0 || !saleInput) return;
            saleInput.value = String(Math.round(mrp * 0.85));
            updateRowDiscount(row);
        });
        refreshPricingStats();
        toastr.success('Applied 15% discount to all variants');
    });

    function buildReview() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        syncProductPricesFromVariants();

        const catSelect = document.getElementById('category_id');
        const brandSelect = document.getElementById('brand_id');
        const imagesInput = document.getElementById('images');
        const uploaderRoot = document.querySelector('[data-uploader="images"]');
        const imageCount = (uploaderRoot && typeof uploaderRoot._getNewImageCount === 'function')
            ? uploaderRoot._getNewImageCount()
            : (imagesInput?.files?.length || 0);

        const rows = variantsBody.querySelectorAll('tr[data-variant-row]');
        let minMrp = null, maxMrp = null, minSale = null, maxSale = null;

        document.querySelector('[data-review="name"]').textContent =
            document.getElementById('name').value.trim() || '—';
        document.querySelector('[data-review="category"]').textContent =
            catSelect.options[catSelect.selectedIndex]?.text || '—';
        document.querySelector('[data-review="brand"]').textContent =
            brandSelect.value ? (brandSelect.options[brandSelect.selectedIndex]?.text || '—') : 'None';
        document.querySelector('[data-review="variant_count_label"]').textContent =
            rows.length + ' active variant' + (rows.length === 1 ? '' : 's') + ' ready';
        document.querySelector('[data-review="images"]').textContent =
            imageCount > 0
                ? (imageCount + ' shared gallery photo(s). Separate thumbnails per variant when uploaded.')
                : 'Separate thumbnails and photos for variants when uploaded.';

        const reviewBody = document.getElementById('reviewVariantsBody');
        reviewBody.innerHTML = '';
        if (!rows.length) {
            reviewBody.innerHTML = '<tr><td colspan="5" class="text-muted text-center py-3">No variants</td></tr>';
            document.querySelector('[data-review="price_range"]').textContent = '₹—';
            document.querySelector('[data-review="mrp_range"]').textContent = '₹—';
            return;
        }
        rows.forEach(function (row) {
            const label = row.querySelector('.small.fw-medium')?.textContent || '—';
            const sku = row.querySelector('.v-sku')?.value || '—';
            const price = row.querySelector('.v-price')?.value || '';
            const sale = row.querySelector('.v-sale')?.value || '';
            const stock = row.querySelector('.v-stock')?.value || '0';
            const mrpN = Number(price);
            const saleN = Number(sale);
            if (isFinite(mrpN)) {
                minMrp = minMrp === null ? mrpN : Math.min(minMrp, mrpN);
                maxMrp = maxMrp === null ? mrpN : Math.max(maxMrp, mrpN);
            }
            if (isFinite(saleN) && sale !== '') {
                minSale = minSale === null ? saleN : Math.min(minSale, saleN);
                maxSale = maxSale === null ? saleN : Math.max(maxSale, saleN);
            }
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + escapeHtml(label) + '</td>' +
                '<td><code>' + escapeHtml(sku) + '</code></td>' +
                '<td>' + escapeHtml(price || '—') + '</td>' +
                '<td>' + escapeHtml(sale || '—') + '</td>' +
                '<td>' + escapeHtml(stock) + '</td>';
            reviewBody.appendChild(tr);
        });

        const saleLo = minSale !== null ? minSale : minMrp;
        const saleHi = maxSale !== null ? maxSale : maxMrp;
        document.querySelector('[data-review="price_range"]').textContent =
            (saleLo !== null && saleHi !== null)
                ? (saleLo === saleHi ? formatInr(saleLo) : formatInr(saleLo) + ' - ' + formatInr(saleHi))
                : '₹—';
        document.querySelector('[data-review="mrp_range"]').textContent =
            (minMrp !== null && maxMrp !== null)
                ? (minMrp === maxMrp ? formatInr(minMrp) : formatInr(minMrp) + ' - ' + formatInr(maxMrp))
                : '₹—';
    }

    document.getElementById('btnWizardNext').addEventListener('click', function () {
        // Leaving step 2: auto-build variants if the list is still empty
        if (currentStep === 2 && !variantsBody.querySelector('tr[data-variant-row]')) {
            const groups = getSelectedAttributeGroups();
            if (groups.length) {
                generateVariants();
            } else {
                addVariantRow([]);
                toastr.info('No attribute values selected — added one default variant');
            }
        }

        if (!validateStep(currentStep)) {
            return;
        }
        if (currentStep === 1 && document.getElementById('category_id').value && !categoryAttributes.length) {
            loadCategoryAttributes(document.getElementById('category_id').value);
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
        if (!validateStep(1) || !validateStep(2) || !validateStep(3)) {
            e.preventDefault();
            return;
        }
        syncProductPricesFromVariants();
        // Disabled selects are omitted from POST — re-enable brand before submit.
        const brandSelect = document.getElementById('brand_id');
        if (brandSelect) {
            brandSelect.disabled = false;
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
@include('admin.partials.product-policy-picker-scripts')
@endpush
