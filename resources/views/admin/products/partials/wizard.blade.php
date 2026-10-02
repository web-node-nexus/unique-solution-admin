@php
    /** @var \App\Models\Product|null $product */
    $isEdit = $product !== null;

    // Rows to prefill: failed-submit input first, then the saved product, else null (seed defaults).
    $initialVariants = null;
    $oldVariants = old('variants');
    if (is_array($oldVariants)) {
        $savedImages = $isEdit
            ? $product->variants->flatMap(fn ($v) => $v->images)->keyBy('id')
            : collect();
        $initialVariants = collect($oldVariants)->filter(fn ($v) => is_array($v))->values()->map(function ($v) use ($savedImages) {
            $keep = array_map('intval', (array) ($v['keep_image_ids'] ?? []));

            return [
                'id' => isset($v['id']) ? (int) $v['id'] : null,
                'sku' => $v['sku'] ?? null,
                'price' => $v['price'] ?? null,
                'discount_price' => $v['discount_price'] ?? null,
                'stock_quantity' => $v['stock_quantity'] ?? 5,
                'low_stock_threshold' => $v['low_stock_threshold'] ?? 2,
                'status' => (bool) ($v['status'] ?? true),
                'attribute_value_ids' => array_values(array_map('intval', array_filter((array) ($v['attribute_value_ids'] ?? [])))),
                'images' => collect($keep)->map(fn ($id) => $savedImages->get($id))->filter()->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/'.$img->image_path),
                    'path' => $img->image_path,
                ])->values(),
            ];
        });
    } elseif ($isEdit) {
        $initialVariants = $wizardVariants;
    }

    $galleryImages = $isEdit
        ? $product->images->sortByDesc(fn ($img) => (int) $img->is_primary)->values()
        : collect();
    $selectedPolicyIds = old('brand_policy_ids', $isEdit ? $product->brandPolicies->pluck('id')->all() : []);
    $initialBrandId = old('brand_id', $product?->brand_id);
    $initialCategoryId = old('category_id', $product?->category_id);
    $featuredOn = (bool) old('is_featured', $isEdit ? $product->is_featured : true);
@endphp

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="product-wizard-steps" id="wizardSteps" role="list">
                <div class="wizard-step active" data-step="1" role="listitem" title="Go to page 1">
                    <span class="wizard-step-num">1</span>
                    <span class="wizard-step-label">Page 1 Basic Detail</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="2" role="listitem" title="Go to page 2">
                    <span class="wizard-step-num">2</span>
                    <span class="wizard-step-label">Page 2 Variant &amp; Multiple Image</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="3" role="listitem" title="Go to page 3">
                    <span class="wizard-step-num">3</span>
                    <span class="wizard-step-label">Page 3 MRP &amp; Pricing</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" data-step="4" role="listitem" title="Go to page 4">
                    <span class="wizard-step-num">4</span>
                    <span class="wizard-step-label">Page 4 Review &amp; Save</span>
                </div>
            </div>
            @if ($isEdit)
                <div class="text-center small text-muted mt-2">
                    Editing <strong>{{ $product->name }}</strong> — click any page above to jump straight to it. You can save from any page.
                </div>
            @endif
        </div>
    </div>

    <form action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
          method="POST" enctype="multipart/form-data"
          id="productWizardForm" data-publish-form="product" data-wizard-mode="{{ $isEdit ? 'edit' : 'create' }}" novalidate>
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

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
        <input type="hidden" name="base_price" id="base_price" value="{{ old('base_price', $product?->base_price ?? '0') }}">
        <input type="hidden" name="sale_price" id="sale_price" value="{{ old('sale_price', $product?->sale_price) }}">

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
                                   value="{{ old('name', $product?->name) }}"
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
                                    <option value="{{ $category->id }}" @selected($initialCategoryId == $category->id)>
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
                                    @disabled(! $initialCategoryId)>
                                <option value="">{{ $initialCategoryId ? '— Select brand —' : 'Select category first' }}</option>
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
                                'value' => old('description', $product?->description),
                                'label' => '4. Description',
                                'invalid' => $errors->has('description'),
                                'error' => $errors->first('description'),
                                'hint' => 'Write specs in English (Display, Camera, Battery, Processor, etc.). HTML is stored and shown in the app.',
                            ])
                        </div>

                        <div class="col-12">
                            <div class="fw-semibold mb-2">5. Policy</div>
                            @include('admin.partials.product-policy-picker', [
                                'initialBrandId' => $initialBrandId,
                                'selectedIds' => $selectedPolicyIds,
                                'autoSelectAll' => ! $isEdit && old('brand_policy_ids') === null,
                            ])
                        </div>

                        <div class="col-12">
                            <div class="featured-toggle-box">
                                <input type="hidden" name="is_featured" value="0">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_featured" id="is_featured"
                                           value="1" @checked($featuredOn)>
                                    <label class="form-check-label fw-semibold" for="is_featured">
                                        <i class="bi bi-star-fill text-warning me-1"></i>Featured product (show on app home page)
                                    </label>
                                </div>
                                <div class="small text-muted mt-1">ON by default. Turn it off anytime to hide this product from the home page.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="meta_title" class="form-label">Meta title</label>
                            <input type="text" name="meta_title" id="meta_title"
                                   class="form-control @error('meta_title') is-invalid @enderror"
                                   value="{{ old('meta_title', $product?->meta_title) }}">
                            @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="meta_description" class="form-label">Meta description</label>
                            <input type="text" name="meta_description" id="meta_description"
                                   class="form-control @error('meta_description') is-invalid @enderror"
                                   value="{{ old('meta_description', $product?->meta_description) }}">
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
                        Upload product photos in bulk (1st photo = main thumbnail). Tick colors and other options, then add a separate photo set for each color. The app shows that color's photos when the customer picks it.
                    </p>
                </div>
                <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle align-self-center" id="activeVariantBadge">0 active variants</span>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-images me-1"></i>Product photos — bulk upload</span>
                    <span class="small text-muted">Select 15+ photos at once · 1st photo = main thumbnail</span>
                </div>
                <div class="card-body">
                    @include('admin.partials.multi-image-uploader', [
                        'inputId' => 'images',
                        'inputName' => 'images[]',
                        'existingImages' => $galleryImages,
                        'uploadUrl' => route('admin.products.upload-image'),
                    ])
                </div>
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

            <div class="card mb-3" id="colorGalleryCard" hidden>
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-palette me-1"></i>Photos for each color</span>
                    <span class="small text-muted">Same photos for every storage of that color</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Tick the colors above, then add photos here. The app swaps to these photos when the customer chooses that color.</p>
                    <div id="colorGalleryBody"></div>
                </div>
            </div>

            <div id="variantsMountStep2"></div>
        </div>

        {{-- Step 3: MRP & Pricing --}}
        <div class="wizard-pane d-none" data-pane="3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-2">Step 3 / 4</span>
                    <h2 class="h5 mb-1">3. MRP &amp; Pricing</h2>
                    <p class="text-muted small mb-0">
                        Set MRP, selling price, stock and low-stock alert for every variant. Currency: ₹ (INR).
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
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddBlankVariant">
                        <i class="bi bi-plus me-1"></i>Add one variant
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnGenerateVariants">
                        <i class="bi bi-magic me-1"></i>Generate from selected
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="variantsEmptyHint" class="text-muted small mb-3">
                    Select attribute values above, then click <strong>Generate from selected</strong>.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle wizard-variants-table" id="variantsTable">
                        <thead>
                            <tr>
                                <th style="min-width: 180px;">Variant (color / RAM / storage)</th>
                                <th class="col-sku" style="min-width: 140px;">SKU</th>
                                <th class="col-pricing" style="min-width: 100px;">MRP (₹) *</th>
                                <th class="col-pricing" style="min-width: 110px;">Selling price (₹) *</th>
                                <th class="col-pricing" style="min-width: 80px;">Discount</th>
                                <th class="col-pricing" style="min-width: 80px;">Stock</th>
                                <th class="col-pricing" style="min-width: 80px;">Low stock</th>
                                <th class="col-pricing" style="min-width: 70px;">Active</th>
                                <th class="col-image" style="min-width: 240px;">Images</th>
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
                            <div class="small mt-1" data-review="featured">—</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="small text-muted mb-1">Variants &amp; photos</div>
                            <div class="fw-semibold text-primary" data-review="variant_count_label">0 active variants ready</div>
                            <div class="small text-muted mt-1" data-review="images">No product photos</div>
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

            <div class="card mb-3">
                <div class="card-body">
                    @include('admin.partials.publish-toggle', [
                        'name' => 'status',
                        'id' => 'status',
                        'onValue' => 'active',
                        'offValue' => 'inactive',
                        'checked' => old('status', $product?->status ?? 'inactive') === 'active',
                    ])
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
                                    <th>Low stock</th>
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
                <a href="{{ $isEdit ? route('admin.products.show', $product) : route('admin.products.index') }}" class="btn btn-outline-secondary" id="btnWizardCancel">Cancel</a>
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
                    <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Save changes' : 'Save & publish product' }}
                </button>
            </div>
        </div>
    </form>

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
    #attributesStepBody.has-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 0.75rem;
        align-items: start;
    }
    #attributesStepBody.has-grid .attr-block { margin-bottom: 0; height: 100%; }
    .color-photo-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.9rem;
        background: #fff;
        margin-bottom: 0.75rem;
    }
    .color-photo-meta { display: flex; align-items: center; gap: 0.65rem; min-width: 160px; }
    .color-photo-swatch {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, 0.15);
        flex: none;
    }
    .color-photo-name { font-weight: 600; font-size: 0.92rem; color: #0f172a; }
    .color-photo-count { font-size: 0.75rem; color: #64748b; }
    .color-photo-picker {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        min-width: 0;
        flex: 1;
    }
    .wizard-variants-table.mode-color-photos .col-image,
    .wizard-variants-table.mode-color-photos td.col-image {
        display: none;
    }
    .wizard-variants-table.mode-images .col-pricing,
    .wizard-variants-table.mode-images td.col-pricing {
        display: none;
    }
    .wizard-variants-table.mode-pricing .col-image,
    .wizard-variants-table.mode-pricing td.col-image {
        display: none;
    }
    .variant-image-picker {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        min-width: 190px;
    }
    .variant-image-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .variant-image-grid:empty {
        display: none;
    }
    .variant-image-preview-wrap {
        position: relative;
        width: 64px;
        height: 64px;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        overflow: hidden;
        flex: 0 0 auto;
    }
    .variant-image-preview-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .variant-image-preview-wrap.is-primary::after {
        content: '1st';
        position: absolute;
        left: 0;
        bottom: 0;
        right: 0;
        background: rgba(13, 148, 136, 0.88);
        color: #fff;
        font-size: 0.6rem;
        font-weight: 600;
        text-align: center;
        line-height: 1.2;
        padding: 1px 0;
    }
    .variant-image-clear {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 20px;
        height: 20px;
        padding: 0;
        border: 0;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        cursor: pointer;
        z-index: 1;
    }
    .variant-image-clear:hover {
        background: #dc2626;
    }
    .variant-image-picker .form-control {
        font-size: 0.75rem;
    }
    .variant-image-hint {
        font-size: 0.7rem;
        color: #64748b;
        line-height: 1.2;
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
    .wizard-step { cursor: pointer; }
    .wizard-step:hover .wizard-step-label { text-decoration: underline; }
    .featured-toggle-box {
        border: 1px solid #fde68a;
        background: #fffbeb;
        border-radius: 0.65rem;
        padding: 0.75rem 1rem;
    }
    .featured-toggle-box .form-check-input { width: 2.6em; height: 1.35em; cursor: pointer; }
    .v-attr { min-width: 150px; }
    .variant-image-preview-wrap img { cursor: pointer; }
    .variant-image-preview-wrap.is-uploading img { opacity: 0.5; }
    .variant-image-preview-wrap.is-failed { border-color: #dc2626; }
    .variant-image-preview-wrap.is-failed img { opacity: 0.35; }
    .variant-image-progress {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 4px;
        background: #e2e8f0;
    }
    .variant-image-progress span {
        display: block;
        height: 100%;
        background: #0d9488;
        transition: width 0.2s ease;
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
    const MAX_VARIANT_IMAGES = 20;
    const DEFAULT_STOCK = 5;
    const DEFAULT_LOW_STOCK = 2;
    let currentStep = 1;
    let categoryAttributes = [];
    let variantIndex = 0;
    let colorGalleries = {};

    const form = document.getElementById('productWizardForm');
    const isEdit = form.getAttribute('data-wizard-mode') === 'edit';
    const attributesBody = document.getElementById('attributesStepBody');
    const variantsBody = document.getElementById('variantsBody');
    const categoryAttrsBase = @json(url('admin/products/category'));
    const uploadUrl = @json(route('admin.products.upload-image'));
    const initialVariants = @json($initialVariants);

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

    function variantRows() {
        return variantsBody.querySelectorAll('tr[data-variant-row]');
    }

    function mountVariantsCard(step) {
        const card = document.getElementById('variantsSharedCard');
        const table = document.getElementById('variantsTable');
        const title = document.getElementById('variantsCardTitle');
        if (!card || !table) return;

        card.classList.remove('d-none');
        table.classList.remove('mode-images', 'mode-pricing', 'mode-color-photos');

        if (step === 2) {
            document.getElementById('variantsMountStep2')?.appendChild(card);
            table.classList.add('mode-images');
            syncImageColumn();
            if (title) {
                title.textContent = colorAttribute()
                    ? 'Variant list — photos are set on each color above'
                    : 'Variant & multiple image list (first image = thumbnail)';
            }
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
        let bestMrp = null;
        let bestSale = null;
        variantRows().forEach(function (row) {
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
            el.textContent = Math.round(((mrp - sale) / mrp) * 100) + '% off';
        } else {
            el.textContent = '—';
        }
    }

    function refreshPricingStats() {
        const rows = variantRows();
        let stock = 0;
        rows.forEach(function (row) {
            stock += Number(row.querySelector('.v-stock')?.value) || 0;
            updateRowDiscount(row);
        });
        const badge = document.getElementById('activeVariantBadge');
        if (badge) badge.textContent = rows.length + ' variant' + (rows.length === 1 ? '' : 's');
        const totalEl = document.querySelector('[data-stat="variant_total"]');
        if (totalEl) totalEl.textContent = String(rows.length);
        const stockEl = document.querySelector('[data-stat="stock_total"]');
        if (stockEl) stockEl.textContent = String(stock);
        document.getElementById('variantsEmptyHint')?.classList.toggle('d-none', rows.length > 0);
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
            if (num) num.textContent = n < step ? '✓' : String(n);
        });
        document.getElementById('btnWizardPrev').classList.toggle('d-none', step === 1);
        document.getElementById('btnWizardCancel')?.classList.toggle('d-none', step !== 1);
        document.getElementById('btnWizardNext').classList.toggle('d-none', step === TOTAL_STEPS);
        // Re-edit: save is available on every page so a single-field fix is one click.
        document.getElementById('btnWizardSubmit').classList.toggle('d-none', !isEdit && step !== TOTAL_STEPS);
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
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        if (step === 1) {
            const name = document.getElementById('name').value.trim();
            const categoryId = document.getElementById('category_id').value;
            if (!name) {
                toastr.error('Product name is required');
                showStep(1);
                document.getElementById('name').focus();
                return false;
            }
            if (!categoryId) {
                toastr.error('Please select a category');
                showStep(1);
                document.getElementById('category_id').focus();
                return false;
            }
            return true;
        }

        if (step === 2) {
            ensureColorVariants();
            if (!variantRows().length) {
                toastr.error('Generate or add at least one variant');
                return false;
            }
            const seen = {};
            let dup = false;
            variantRows().forEach(function (row) {
                const ids = rowValueIds(row);
                if (!ids.length) return;
                const key = ids.slice().sort(function (a, b) { return a - b; }).join('-');
                if (seen[key]) dup = true;
                seen[key] = true;
            });
            if (dup) {
                toastr.error('Two variants have the same color / RAM / storage. Change or remove one.');
                showStep(2);
                return false;
            }
            let colorPending = false;
            let colorFailed = false;
            Object.keys(colorGalleries).forEach(function (id) {
                (colorGalleries[id].items || []).forEach(function (item) {
                    if (item.state === 'uploading') colorPending = true;
                    if (item.state === 'failed') colorFailed = true;
                });
            });
            if (colorPending) {
                toastr.error('Wait until color photos finish uploading');
                showStep(2);
                return false;
            }
            if (colorFailed) {
                toastr.error('Remove the color photo that failed to upload, then try again');
                showStep(2);
                return false;
            }
            return true;
        }

        if (step === 3) {
            if (!variantRows().length) {
                toastr.error('Generate or add at least one variant');
                return false;
            }
            let ok = true;
            variantRows().forEach(function (row) {
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
                showStep(3);
                return false;
            }
            syncProductPricesFromVariants();
            return true;
        }

        return true;
    }

    /* ---------- Attributes ---------- */

    function valueLookup() {
        const map = {};
        categoryAttributes.forEach(function (attr) {
            (attr.values || []).forEach(function (val) {
                map[val.id] = { attrId: attr.id, label: val.value };
            });
        });
        return map;
    }

    function renderAttributes(attributes) {
        categoryAttributes = attributes || [];
        attributesBody.innerHTML = '';

        if (!categoryAttributes.length) {
            attributesBody.classList.remove('has-grid');
            attributesBody.innerHTML =
                '<div class="text-muted">This category has no attributes. Use <strong>Add one variant</strong> for each SKU.</div>';
            renderColorGallery();
            return;
        }

        attributesBody.classList.add('has-grid');

        const used = {};
        variantRows().forEach(function (row) {
            rowValueIds(row).forEach(function (id) { used[id] = true; });
        });

        categoryAttributes.forEach(function (attr) {
            const block = document.createElement('div');
            block.className = 'attr-block';
            block.setAttribute('data-attr-id', attr.id);

            const checkAttrs = function (val) {
                const hex = (val.extra_data && (val.extra_data.hex || val.extra_data.color)) || '';
                return ' data-attr-id="' + attr.id + '"' +
                    ' data-attr-name="' + escapeHtml(attr.name) + '"' +
                    ' data-value-id="' + val.id + '"' +
                    ' data-value-label="' + escapeHtml(val.value) + '"' +
                    ' data-hex="' + escapeHtml(hex) + '"' +
                    ' id="av_' + attr.id + '_' + val.id + '" value="' + val.id + '"' +
                    (used[val.id] ? ' checked' : '');
            };

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
                        '<input class="attr-value-check" type="checkbox"' + checkAttrs(val) + '>' +
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
                        '<input class="form-check-input attr-value-check" type="checkbox"' + checkAttrs(val) + '>' +
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

        seedColorGalleriesFromRows();
        renderColorGallery();
    }

    function loadCategoryAttributes(categoryId) {
        if (!categoryId) {
            categoryAttributes = [];
            attributesBody.innerHTML =
                '<div class="text-muted" id="attributesPlaceholder">Choose a category in step 1 to load attributes.</div>';
            variantRows().forEach(renderRowAttrCell);
            renderColorGallery();
            return Promise.resolve();
        }

        attributesBody.innerHTML = '<div class="text-muted">Loading attributes…</div>';
        const url = categoryAttrsBase + '/' + encodeURIComponent(categoryId) + '/attributes';

        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load attributes');
                return res.json();
            })
            .then(function (json) {
                categoryAttributes = json.attributes || [];
                variantRows().forEach(renderRowAttrCell);
                renderAttributes(categoryAttributes);
            })
            .catch(function () {
                categoryAttributes = [];
                attributesBody.innerHTML =
                    '<div class="text-danger">Could not load category attributes. Try again.</div>';
                toastr.error('Failed to load category attributes');
            });
    }

    document.getElementById('category_id').addEventListener('change', function () {
        // Keep prices, stock and photos — only the attribute picks belong to the old category.
        variantRows().forEach(function (row) {
            row._valueIds = [];
            row._extraValueIds = [];
        });
        colorGalleries = {};
        loadCategoryAttributes(this.value);
        filterBrandsByCategory(this.value);
        document.getElementById('brand_id')?.dispatchEvent(new Event('change', { bubbles: true }));
    });

    function getSelectedAttributeGroups() {
        const groups = {};
        document.querySelectorAll('.attr-value-check:checked').forEach(function (el) {
            const attrId = el.getAttribute('data-attr-id');
            if (!groups[attrId]) {
                groups[attrId] = { attrId: attrId, attrName: el.getAttribute('data-attr-name'), values: [] };
            }
            groups[attrId].values.push({
                id: parseInt(el.getAttribute('data-value-id'), 10),
                label: el.getAttribute('data-value-label'),
            });
        });
        return Object.values(groups).filter(function (g) { return g.values.length > 0; });
    }

    function cartesian(groups) {
        if (!groups.length) return [[]];
        return groups.reduce(function (acc, group) {
            const next = [];
            acc.forEach(function (combo) {
                group.values.forEach(function (val) {
                    next.push(combo.concat([{ attrId: group.attrId, attrName: group.attrName, id: val.id, label: val.label }]));
                });
            });
            return next;
        }, [[]]);
    }

    function suggestSku(labels) {
        const namePart = slugifySkuPart(document.getElementById('name').value) || 'SKU';
        const valuePart = (labels || []).map(slugifySkuPart).filter(Boolean).join('-');
        return valuePart ? (namePart + '-' + valuePart) : namePart;
    }

    /* ---------- Variant rows ---------- */

    function rowValueIds(row) {
        return (row._valueIds || []).concat(row._extraValueIds || []);
    }

    function rowLabel(row) {
        const lookup = valueLookup();
        const labels = (row._valueIds || []).map(function (id) { return lookup[id]?.label; }).filter(Boolean);
        return labels.length ? labels.join(' · ') : 'Default';
    }

    function renderRowAttrCell(row) {
        const cell = row.querySelector('[data-attr-cell]');
        if (!cell) return;
        const i = row.getAttribute('data-index');
        const lookup = valueLookup();
        const all = rowValueIds(row);

        // Values not in this category's attribute list are kept as-is (hidden).
        row._valueIds = all.filter(function (id) { return lookup[id]; });
        row._extraValueIds = all.filter(function (id) { return !lookup[id]; });

        let html = '';
        categoryAttributes.forEach(function (attr) {
            const selected = row._valueIds.find(function (id) { return lookup[id].attrId === attr.id; });
            html += '<select class="form-select form-select-sm v-attr mb-1" data-attr-id="' + attr.id + '"' +
                ' name="variants[' + i + '][attribute_value_ids][]" title="' + escapeHtml(attr.name) + '">' +
                '<option value="">— ' + escapeHtml(attr.name) + ' —</option>';
            (attr.values || []).forEach(function (val) {
                html += '<option value="' + val.id + '"' + (val.id === selected ? ' selected' : '') + '>' +
                    escapeHtml(val.value) + '</option>';
            });
            html += '</select>';
        });
        if (!categoryAttributes.length) {
            html = '<div class="small fw-medium text-muted">Default</div>';
        }
        row._extraValueIds.forEach(function (id) {
            html += '<input type="hidden" name="variants[' + i + '][attribute_value_ids][]" value="' + id + '">';
        });
        cell.innerHTML = html;
    }

    function addVariantRow(opts) {
        opts = opts || {};
        const i = variantIndex++;
        const tr = document.createElement('tr');
        tr.setAttribute('data-variant-row', '');
        tr.setAttribute('data-index', String(i));
        tr._valueIds = (opts.attribute_value_ids || []).map(Number);
        tr._extraValueIds = [];

        const mrpVal = opts.price != null ? opts.price : '';
        const saleVal = opts.discount_price != null ? opts.discount_price : '';
        const stockVal = opts.stock_quantity != null ? opts.stock_quantity : DEFAULT_STOCK;
        const lowVal = opts.low_stock_threshold != null ? opts.low_stock_threshold : DEFAULT_LOW_STOCK;
        const active = opts.status !== false;
        const accept = document.body.dataset.imageAccept || 'image/jpeg,image/png,image/webp';

        tr.innerHTML =
            '<td>' +
                (opts.id ? '<input type="hidden" name="variants[' + i + '][id]" value="' + Number(opts.id) + '">' : '') +
                '<div data-attr-cell></div>' +
            '</td>' +
            '<td class="col-sku"><input type="text" class="form-control form-control-sm v-sku" name="variants[' + i + '][sku]" value="' + escapeHtml(opts.sku || '') + '"></td>' +
            '<td class="col-pricing"><input type="number" step="0.01" min="0" class="form-control form-control-sm v-price" name="variants[' + i + '][price]" value="' + escapeHtml(String(mrpVal)) + '" placeholder="MRP"></td>' +
            '<td class="col-pricing"><input type="number" step="0.01" min="0" class="form-control form-control-sm v-sale" name="variants[' + i + '][discount_price]" value="' + escapeHtml(String(saleVal)) + '" placeholder="Selling"></td>' +
            '<td class="col-pricing"><div class="wizard-discount-display" data-discount-display>—</div></td>' +
            '<td class="col-pricing"><input type="number" min="0" class="form-control form-control-sm v-stock" name="variants[' + i + '][stock_quantity]" value="' + escapeHtml(String(stockVal)) + '"></td>' +
            '<td class="col-pricing"><input type="number" min="0" class="form-control form-control-sm v-low" name="variants[' + i + '][low_stock_threshold]" value="' + escapeHtml(String(lowVal)) + '" title="Low stock alert at"></td>' +
            '<td class="col-pricing">' +
                '<input type="hidden" name="variants[' + i + '][status]" value="0">' +
                '<div class="form-check form-switch mb-0"><input class="form-check-input v-status" type="checkbox" role="switch" name="variants[' + i + '][status]" value="1"' + (active ? ' checked' : '') + '></div>' +
            '</td>' +
            '<td class="col-image">' +
                '<div class="variant-image-picker" data-variant-image-picker>' +
                    '<input type="hidden" name="variants[' + i + '][images_managed]" value="1">' +
                    '<div class="variant-image-grid" data-variant-image-grid></div>' +
                    '<div data-variant-image-hidden></div>' +
                    '<input type="file" accept="' + accept + '" class="form-control form-control-sm" multiple data-variant-image-input>' +
                    '<div class="variant-image-hint">Many photos OK · 1st = thumbnail · click a photo to make it 1st</div>' +
                '</div>' +
            '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant" title="Remove variant"><i class="bi bi-trash"></i></button></td>';

        variantsBody.appendChild(tr);
        renderRowAttrCell(tr);

        const picker = tr.querySelector('[data-variant-image-picker]');
        picker._ownIds = {};
        picker._items = (opts.images || []).map(function (img) {
            if (img.id) picker._ownIds[img.id] = true;
            return { kind: 'existing', id: img.id, url: img.url, path: img.path || '' };
        });
        renderVariantImages(picker);

        updateRowDiscount(tr);
        refreshPricingStats();
        return tr;
    }

    /* ---------- Variant images (uploaded in the background) ---------- */

    function syncVariantImageHidden(picker) {
        const box = picker.querySelector('[data-variant-image-hidden]');
        const row = picker.closest('tr[data-variant-row]');
        if (!box || !row) return;
        const i = row.getAttribute('data-index');
        let html = '';
        let newIndex = 0;
        (picker._items || []).forEach(function (item) {
            if (item.kind === 'existing') {
                html += '<input type="hidden" name="variants[' + i + '][keep_image_ids][]" value="' + Number(item.id) + '">';
                html += '<input type="hidden" name="variants[' + i + '][image_order][]" value="existing:' + Number(item.id) + '">';
            } else if (item.kind === 'copy' && item.path) {
                html += '<input type="hidden" name="variants[' + i + '][uploaded_images][]" value="path:' + escapeHtml(item.path) + '">';
                html += '<input type="hidden" name="variants[' + i + '][image_order][]" value="new:' + newIndex + '">';
                newIndex += 1;
            } else if (item.token) {
                html += '<input type="hidden" name="variants[' + i + '][uploaded_images][]" value="' + escapeHtml(item.token) + '">';
                html += '<input type="hidden" name="variants[' + i + '][image_order][]" value="new:' + newIndex + '">';
                newIndex += 1;
            }
        });
        box.innerHTML = html;
    }

    function renderVariantImages(picker) {
        const grid = picker.querySelector('[data-variant-image-grid]');
        if (!grid) return;
        grid.innerHTML = '';
        (picker._items || []).forEach(function (item, index) {
            const wrap = document.createElement('div');
            wrap.className = 'variant-image-preview-wrap' +
                (index === 0 ? ' is-primary' : '') +
                (item.state === 'uploading' ? ' is-uploading' : '') +
                (item.state === 'failed' ? ' is-failed is-upload-failed' : '');
            wrap.title = index === 0 ? 'Thumbnail' : 'Click to make this the thumbnail';

            const img = document.createElement('img');
            img.src = item.url;
            img.alt = 'Variant image ' + (index + 1);
            img.setAttribute('data-make-first', String(index));
            if (item.kind === 'existing') img.setAttribute('data-existing-variant-image', '');

            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'variant-image-clear';
            clearBtn.setAttribute('data-variant-image-clear', '');
            clearBtn.setAttribute('data-index', String(index));
            clearBtn.title = 'Remove image';
            clearBtn.innerHTML = '<i class="bi bi-x-lg"></i>';

            wrap.appendChild(img);
            wrap.appendChild(clearBtn);
            if (item.state === 'uploading') {
                const bar = document.createElement('div');
                bar.className = 'variant-image-progress';
                bar.innerHTML = '<span style="width:' + Math.round((item.progress || 0) * 100) + '%"></span>';
                wrap.appendChild(bar);
            }
            grid.appendChild(wrap);
        });
        syncVariantImageHidden(picker);
        const countEl = picker.closest('.color-photo-row')?.querySelector('.color-photo-count');
        if (countEl) {
            const n = (picker._items || []).filter(function (item) { return item.state !== 'failed'; }).length;
            countEl.textContent = n + ' photo' + (n === 1 ? '' : 's');
        }
    }

    function addVariantImagesFromInput(input) {
        const picker = input.closest('[data-variant-image-picker]');
        if (!picker) return;
        picker._items = picker._items || [];
        const validate = window.UniqueValidateImageFile || function () { return true; };
        let skipped = 0;

        Array.from(input.files || []).forEach(function (file) {
            if (!validate(file)) return;
            if (picker._items.length >= MAX_VARIANT_IMAGES) {
                skipped += 1;
                return;
            }
            const item = { kind: 'new', file: file, url: URL.createObjectURL(file), state: 'uploading', progress: 0 };
            picker._items.push(item);
            window.UniqueStagedUpload.upload(uploadUrl, file, 'variants', function (ratio) {
                item.progress = ratio;
                const idx = picker._items.indexOf(item);
                const bar = picker.querySelectorAll('.variant-image-preview-wrap')[idx]?.querySelector('.variant-image-progress span');
                if (bar) bar.style.width = Math.round(ratio * 100) + '%';
            }).then(function (res) {
                item.token = res.token;
                item.state = 'done';
            }).catch(function (err) {
                item.state = 'failed';
                toastr.error(err.message || 'Variant photo upload failed');
            }).finally(function () {
                if (picker.isConnected) renderVariantImages(picker);
            });
        });

        if (skipped) toastr.warning('Max ' + MAX_VARIANT_IMAGES + ' photos per variant — ' + skipped + ' skipped');
        input.value = '';
        renderVariantImages(picker);
    }

    /* ---------- One photo set per color ---------- */

    function colorAttribute() {
        return categoryAttributes.find(function (attr) {
            const name = String(attr.name || '').toLowerCase();
            return attr.type === 'color-swatch' || name.indexOf('color') !== -1 || name.indexOf('colour') !== -1;
        }) || null;
    }

    function syncImageColumn() {
        const table = document.getElementById('variantsTable');
        if (!table) return;
        const hasColor = !!colorAttribute();
        table.classList.toggle('mode-color-photos', hasColor && table.classList.contains('mode-images'));
        const title = document.getElementById('variantsCardTitle');
        if (title && currentStep === 2) {
            title.textContent = hasColor
                ? 'Variant list — photos are set on each color above'
                : 'Variant & multiple image list (first image = thumbnail)';
        }
    }

    function rowColorValueId(row) {
        const attr = colorAttribute();
        if (!attr) return null;
        const lookup = valueLookup();
        const found = rowValueIds(row).find(function (id) {
            return lookup[id] && Number(lookup[id].attrId) === Number(attr.id);
        });
        return found ? Number(found) : null;
    }

    function seedColorGalleriesFromRows() {
        const attr = colorAttribute();
        if (!attr) return;
        variantRows().forEach(function (row) {
            const colorId = rowColorValueId(row);
            if (!colorId || colorGalleries[colorId]) return;
            const picker = row.querySelector('[data-variant-image-picker]');
            const items = (picker?._items || []).filter(function (item) { return item.kind === 'existing' && item.url; })
                .map(function (item) {
                    return { kind: 'existing', id: item.id, url: item.url, path: item.path || '' };
                });
            if (items.length) colorGalleries[colorId] = { items: items };
        });
    }

    function selectedColors() {
        const attr = colorAttribute();
        if (!attr) return [];
        const map = {};
        document.querySelectorAll('.attr-value-check:checked').forEach(function (el) {
            if (String(el.getAttribute('data-attr-id')) !== String(attr.id)) return;
            const id = parseInt(el.getAttribute('data-value-id'), 10);
            if (!id) return;
            map[id] = {
                id: id,
                label: el.getAttribute('data-value-label') || '',
                hex: el.getAttribute('data-hex') || '',
            };
        });
        const lookup = valueLookup();
        variantRows().forEach(function (row) {
            const id = rowColorValueId(row);
            if (!id || map[id]) return;
            map[id] = { id: id, label: lookup[id]?.label || 'Color', hex: '' };
        });
        return Object.keys(map).map(function (id) { return map[id]; });
    }

    function renderColorGallery() {
        const card = document.getElementById('colorGalleryCard');
        const body = document.getElementById('colorGalleryBody');
        syncImageColumn();
        if (!card || !body) return;

        body.querySelectorAll('[data-color-image-picker]').forEach(function (picker) {
            const id = picker.getAttribute('data-color-id');
            if (id) colorGalleries[id] = { items: picker._items || [] };
        });

        const colors = selectedColors();
        if (!colorAttribute() || !colors.length) {
            card.hidden = true;
            body.innerHTML = '';
            return;
        }

        card.hidden = false;
        body.innerHTML = '';
        const accept = document.body.dataset.imageAccept || 'image/jpeg,image/png,image/webp';
        colors.forEach(function (color) {
            if (!colorGalleries[color.id]) colorGalleries[color.id] = { items: [] };
            const row = document.createElement('div');
            row.className = 'color-photo-row';
            const count = (colorGalleries[color.id].items || []).filter(function (item) {
                return item.state !== 'failed';
            }).length;
            row.innerHTML =
                '<div class="color-photo-meta">' +
                    '<span class="color-photo-swatch" style="background:' + escapeHtml(color.hex || '#cbd5e1') + '"></span>' +
                    '<div><div class="color-photo-name">' + escapeHtml(color.label) + '</div>' +
                    '<div class="color-photo-count">' + count + ' photo' + (count === 1 ? '' : 's') + '</div></div>' +
                '</div>' +
                '<div class="variant-image-picker color-photo-picker" data-variant-image-picker data-color-image-picker data-color-id="' + color.id + '">' +
                    '<div class="variant-image-grid" data-variant-image-grid></div>' +
                    '<label class="btn btn-sm btn-outline-primary color-photo-add mb-0">' +
                        '<i class="bi bi-plus-lg me-1"></i>Add photos' +
                        '<input type="file" accept="' + accept + '" multiple class="d-none" data-variant-image-input>' +
                    '</label>' +
                '</div>';
            body.appendChild(row);
            const picker = row.querySelector('[data-color-image-picker]');
            picker._items = colorGalleries[color.id].items;
            renderVariantImages(picker);
        });
    }

    function ensureColorVariants() {
        const colors = selectedColors();
        if (!colors.length) return;
        const present = {};
        variantRows().forEach(function (row) {
            const id = rowColorValueId(row);
            if (id) present[id] = true;
        });
        if (colors.some(function (color) { return !present[color.id]; })) {
            generateVariants();
        }
    }

    function applyColorPhotos() {
        if (!colorAttribute()) return;
        const shown = {};
        selectedColors().forEach(function (color) { shown[color.id] = true; });
        document.querySelectorAll('[data-color-image-picker]').forEach(function (picker) {
            const id = picker.getAttribute('data-color-id');
            if (id) colorGalleries[id] = { items: picker._items || [] };
        });

        variantRows().forEach(function (row) {
            const colorId = rowColorValueId(row);
            if (!colorId || !shown[colorId] || !colorGalleries[colorId]) return;
            const picker = row.querySelector('[data-variant-image-picker]');
            if (!picker) return;
            const own = picker._ownIds || {};
            picker._items = (colorGalleries[colorId].items || []).map(function (item) {
                if (item.state === 'uploading' || item.state === 'failed') return null;
                if (item.kind === 'existing' && item.id && own[item.id]) {
                    return { kind: 'existing', id: item.id, url: item.url, path: item.path || '' };
                }
                if (item.path) {
                    return { kind: 'copy', path: item.path, url: item.url };
                }
                if (item.token) {
                    return { kind: 'new', token: item.token, url: item.url, state: 'done' };
                }
                return null;
            }).filter(Boolean);
            renderVariantImages(picker);
        });
    }

    /* ---------- Generate / add ---------- */

    function rowIsBlankDefault(row) {
        const hasId = !!row.querySelector('input[name$="[id]"]');
        const picker = row.querySelector('[data-variant-image-picker]');
        return !hasId && !rowValueIds(row).length && !(picker?._items || []).length;
    }

    function generateVariants() {
        const groups = getSelectedAttributeGroups();
        if (!groups.length) {
            toastr.warning('Select at least one color / RAM / storage value above first');
            return;
        }

        const existingKeys = {};
        variantRows().forEach(function (row) {
            const ids = rowValueIds(row);
            if (ids.length) existingKeys[ids.slice().sort(function (a, b) { return a - b; }).join('-')] = true;
        });

        // Blank placeholder rows are replaced; saved rows and rows with photos stay.
        variantRows().forEach(function (row) {
            if (rowIsBlankDefault(row)) row.remove();
        });

        const template = variantRows()[0];
        let added = 0;
        cartesian(groups).forEach(function (combo) {
            const ids = combo.map(function (v) { return v.id; });
            const key = ids.slice().sort(function (a, b) { return a - b; }).join('-');
            if (existingKeys[key]) return;
            addVariantRow({
                attribute_value_ids: ids,
                sku: suggestSku(combo.map(function (v) { return v.label; })),
                price: template?.querySelector('.v-price')?.value || null,
                discount_price: template?.querySelector('.v-sale')?.value || null,
            });
            added += 1;
        });

        refreshPricingStats();
        if (added) {
            toastr.success('Added ' + added + ' new variant(s)');
        } else {
            toastr.info('All selected combinations already exist');
        }
    }

    function seedDefaultVariants(count) {
        const n = Math.max(1, parseInt(count, 10) || 5);
        for (let i = 0; i < n; i++) {
            addVariantRow({ sku: suggestSku([]) + (n > 1 ? '-' + (i + 1) : '') });
        }
    }

    attributesBody.addEventListener('change', function (e) {
        if (e.target.matches('.attr-value-check')) renderColorGallery();
    });

    document.getElementById('colorGalleryBody')?.addEventListener('click', function (e) {
        const clearBtn = e.target.closest('[data-variant-image-clear]');
        if (clearBtn) {
            e.preventDefault();
            const picker = clearBtn.closest('[data-variant-image-picker]');
            const index = parseInt(clearBtn.getAttribute('data-index') || '-1', 10);
            if (picker && index >= 0) {
                picker._items.splice(index, 1);
                renderVariantImages(picker);
            }
            return;
        }
        const makeFirst = e.target.closest('[data-make-first]');
        if (!makeFirst) return;
        const picker = makeFirst.closest('[data-variant-image-picker]');
        const index = parseInt(makeFirst.getAttribute('data-make-first'), 10);
        if (picker && index > 0) {
            const item = picker._items.splice(index, 1)[0];
            picker._items.unshift(item);
            renderVariantImages(picker);
        }
    });

    document.getElementById('colorGalleryBody')?.addEventListener('change', function (e) {
        if (e.target.matches('[data-variant-image-input]')) addVariantImagesFromInput(e.target);
    });

    document.getElementById('btnGenerateVariants').addEventListener('click', generateVariants);
    document.getElementById('btnAddBlankVariant').addEventListener('click', function () {
        const row = addVariantRow({ sku: suggestSku([]) + '-' + (variantRows().length + 1) });
        row.querySelector('.v-attr, .v-sku')?.focus();
    });

    variantsBody.addEventListener('click', function (e) {
        const clearBtn = e.target.closest('[data-variant-image-clear]');
        if (clearBtn) {
            e.preventDefault();
            const picker = clearBtn.closest('[data-variant-image-picker]');
            const index = parseInt(clearBtn.getAttribute('data-index') || '-1', 10);
            if (picker && index >= 0) {
                picker._items.splice(index, 1);
                renderVariantImages(picker);
            }
            return;
        }

        const makeFirst = e.target.closest('[data-make-first]');
        if (makeFirst) {
            const picker = makeFirst.closest('[data-variant-image-picker]');
            const index = parseInt(makeFirst.getAttribute('data-make-first'), 10);
            if (picker && index > 0) {
                const item = picker._items.splice(index, 1)[0];
                picker._items.unshift(item);
                renderVariantImages(picker);
            }
            return;
        }

        const btn = e.target.closest('.btn-remove-variant');
        if (!btn) return;
        const row = btn.closest('tr');
        const saved = !!row?.querySelector('input[name$="[id]"]');
        if (saved && !window.confirm('Remove this saved variant? It will be deleted when you save.')) {
            return;
        }
        row?.remove();
        refreshPricingStats();
    });

    variantsBody.addEventListener('change', function (e) {
        if (e.target.matches('[data-variant-image-input]')) {
            addVariantImagesFromInput(e.target);
            return;
        }
        if (e.target.matches('.v-attr')) {
            const row = e.target.closest('tr[data-variant-row]');
            if (!row) return;
            row._valueIds = Array.from(row.querySelectorAll('.v-attr'))
                .map(function (s) { return parseInt(s.value, 10); })
                .filter(function (n) { return n > 0; });
        }
    });

    variantsBody.addEventListener('input', function (e) {
        const row = e.target.closest('tr[data-variant-row]');
        if (!row) return;
        if (e.target.matches('.v-price, .v-sale, .v-stock')) {
            updateRowDiscount(row);
            refreshPricingStats();
        }
    });

    document.getElementById('btnApplyDiscountAll')?.addEventListener('click', function () {
        const rows = variantRows();
        if (!rows.length) {
            toastr.warning('No variants to update');
            return;
        }
        rows.forEach(function (row) {
            const mrp = Number(row.querySelector('.v-price')?.value);
            const saleInput = row.querySelector('.v-sale');
            if (!isFinite(mrp) || mrp <= 0 || !saleInput) return;
            saleInput.value = String(Math.round(mrp * 0.85));
            updateRowDiscount(row);
        });
        refreshPricingStats();
        toastr.success('Applied 15% discount to all variants');
    });

    /* ---------- Review ---------- */

    function buildReview() {
        if (typeof tinymce !== 'undefined') tinymce.triggerSave();
        syncProductPricesFromVariants();

        const catSelect = document.getElementById('category_id');
        const brandSelect = document.getElementById('brand_id');
        const photoCount = document.querySelectorAll('#imagesPreview .multi-image-card:not(.is-removed):not(.is-upload-failed)').length;
        const rows = variantRows();
        let minMrp = null, maxMrp = null, minSale = null, maxSale = null;

        document.querySelector('[data-review="name"]').textContent = document.getElementById('name').value.trim() || '—';
        document.querySelector('[data-review="category"]').textContent = catSelect.options[catSelect.selectedIndex]?.text || '—';
        document.querySelector('[data-review="brand"]').textContent =
            brandSelect.value ? (brandSelect.options[brandSelect.selectedIndex]?.text || '—') : 'None';
        document.querySelector('[data-review="featured"]').innerHTML = document.getElementById('is_featured').checked
            ? '<span class="text-warning-emphasis"><i class="bi bi-star-fill me-1"></i>Featured on home page</span>'
            : '<span class="text-muted">Not featured</span>';
        document.querySelector('[data-review="variant_count_label"]').textContent =
            rows.length + ' variant' + (rows.length === 1 ? '' : 's') + ' ready';
        document.querySelector('[data-review="images"]').textContent = photoCount > 0
            ? photoCount + ' product photo(s) · 1st photo is the thumbnail'
            : 'No product photos — the first variant photo will be used as thumbnail';

        const reviewBody = document.getElementById('reviewVariantsBody');
        reviewBody.innerHTML = '';
        if (!rows.length) {
            reviewBody.innerHTML = '<tr><td colspan="6" class="text-muted text-center py-3">No variants</td></tr>';
            document.querySelector('[data-review="price_range"]').textContent = '₹—';
            document.querySelector('[data-review="mrp_range"]').textContent = '₹—';
            return;
        }
        rows.forEach(function (row) {
            const sku = row.querySelector('.v-sku')?.value || '—';
            const price = row.querySelector('.v-price')?.value || '';
            const sale = row.querySelector('.v-sale')?.value || '';
            const stock = row.querySelector('.v-stock')?.value || '0';
            const low = row.querySelector('.v-low')?.value || '0';
            const active = row.querySelector('.v-status')?.checked;
            const mrpN = Number(price);
            const saleN = Number(sale);
            if (isFinite(mrpN) && price !== '') {
                minMrp = minMrp === null ? mrpN : Math.min(minMrp, mrpN);
                maxMrp = maxMrp === null ? mrpN : Math.max(maxMrp, mrpN);
            }
            if (isFinite(saleN) && sale !== '') {
                minSale = minSale === null ? saleN : Math.min(minSale, saleN);
                maxSale = maxSale === null ? saleN : Math.max(maxSale, saleN);
            }
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + escapeHtml(rowLabel(row)) + (active ? '' : ' <span class="badge text-bg-secondary">Off</span>') + '</td>' +
                '<td><code>' + escapeHtml(sku) + '</code></td>' +
                '<td>' + escapeHtml(price || '—') + '</td>' +
                '<td>' + escapeHtml(sale || '—') + '</td>' +
                '<td>' + escapeHtml(stock) + '</td>' +
                '<td>' + escapeHtml(low) + '</td>';
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

    /* ---------- Navigation ---------- */

    function goToStep(target) {
        if (target <= currentStep) {
            showStep(target);
            return;
        }
        for (let s = currentStep; s < target; s++) {
            if (s === 2 && !variantRows().length) {
                addVariantRow({ sku: suggestSku([]) });
                toastr.info('Added one default variant');
            }
            if (!validateStep(s)) return;
        }
        showStep(target);
    }

    document.getElementById('btnWizardNext').addEventListener('click', function () {
        goToStep(Math.min(TOTAL_STEPS, currentStep + 1));
    });

    document.getElementById('btnWizardPrev').addEventListener('click', function () {
        showStep(Math.max(1, currentStep - 1));
    });

    document.querySelectorAll('#wizardSteps .wizard-step').forEach(function (el) {
        el.addEventListener('click', function () {
            goToStep(parseInt(el.getAttribute('data-step'), 10));
        });
    });

    form.addEventListener('submit', function (e) {
        if (!isEdit && currentStep !== TOTAL_STEPS) {
            e.preventDefault();
            return;
        }
        if (!validateStep(1) || !validateStep(2) || !validateStep(3)) {
            e.preventDefault();
            return;
        }
        syncProductPricesFromVariants();
        applyColorPhotos();
        // Unpicked attribute dropdowns must not post empty ids.
        variantsBody.querySelectorAll('.v-attr').forEach(function (select) {
            select.disabled = !select.value;
        });
        // Disabled selects are omitted from POST — re-enable brand before submit.
        const brandSelect = document.getElementById('brand_id');
        if (brandSelect) brandSelect.disabled = false;
        if (typeof tinymce !== 'undefined') tinymce.triggerSave();
        window.setButtonLoading(document.getElementById('btnWizardSubmit'), true);
    });

    /* ---------- Boot ---------- */

    if (Array.isArray(initialVariants) && initialVariants.length) {
        initialVariants.forEach(function (v) { addVariantRow(v); });
    } else {
        seedDefaultVariants(5);
    }

    const initialCategory = document.getElementById('category_id').value;
    if (initialCategory) {
        loadCategoryAttributes(initialCategory);
        filterBrandsByCategory(initialCategory, {
            keepBrandId: @json($initialBrandId),
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
