@extends('admin.layouts.app')

@section('title', 'Edit Brand')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Brand edit',
        'subtitle' => 'Manage brand name, categories, logo and policies in one place.',
        'breadcrumbs' => [
            'Catalog' => null,
            'Brands' => route('admin.brands.index'),
            $brand->name,
        ],
        'actions' => '<span class="badge text-bg-success rounded-pill px-3 py-2">Edit mode active</span>',
    ])

    <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data" data-publish-form="brand" novalidate>
        @csrf
        @method('PUT')

        <div class="card brand-form-card mb-3">
            <div class="card-body">
                <div class="brand-form-section-title">Brand details</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Brand name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $brand->name) }}" placeholder="e.g. Samsung" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        @include('admin.partials.publish-toggle', [
                            'checked' => filter_var(old('status', $brand->status), FILTER_VALIDATE_BOOLEAN),
                        ])
                    </div>

                    <div class="col-12">
                        <label class="form-label">Categories <span class="text-danger">*</span></label>
                        <div class="form-text mb-2">
                            Same brand name can be linked to multiple categories. It cannot appear twice in one category.
                        </div>
                        <div class="border rounded p-3 @error('category_ids') is-invalid border-danger @enderror" style="max-height: 240px; overflow: auto;">
                            @php
                                $oldCats = collect(old('category_ids', $brand->categories->pluck('id')->all() ?: array_filter([$brand->category_id])))
                                    ->map(fn ($id) => (int) $id);
                            @endphp
                            @foreach ($categories as $category)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="category_ids[]"
                                           id="brand_cat_{{ $category->id }}" value="{{ $category->id }}"
                                           @checked($oldCats->contains($category->id))>
                                    <label class="form-check-label" for="brand_cat_{{ $category->id }}">
                                        @if ($category->parent)
                                            {{ $category->parent->name }} → {{ $category->name }}
                                        @else
                                            {{ $category->name }}
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('category_ids')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Tick every category this brand sells in (e.g. Samsung → Mobile + TV + Fridge).
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label for="logo" class="form-label">Brand logo</label>
                        <div class="brand-logo-row">
                            @php $logoUrl = $brand->logo ? asset('storage/'.$brand->logo) : ''; @endphp
                            <div class="brand-logo-preview">
                                <img id="logoPreview"
                                     src="{{ $logoUrl }}"
                                     alt="Logo preview"
                                     class="{{ $logoUrl ? '' : 'd-none' }}">
                                <span id="logoPlaceholder" class="{{ $logoUrl ? 'd-none' : '' }}">No logo</span>
                            </div>
                            <div>
                                <label class="btn btn-outline-secondary mb-0">
                                    <i class="bi bi-upload me-1"></i>Upload new logo
                                    <input type="file" name="logo" id="logo" accept="image/*" class="d-none @error('logo') is-invalid @enderror">
                                </label>
                                <div class="form-text mt-2">PNG, JPG or SVG. Transparent PNG looks best.</div>
                                @error('logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="warranty" class="form-label">Default brand warranty note <span class="text-muted">(optional)</span></label>
                        <textarea name="warranty" id="warranty" rows="6"
                                  class="form-control @error('warranty') is-invalid @enderror"
                                  data-rich-editor="1"
                                  data-editor-height="220">{{ old('warranty', $brand->warranty) }}</textarea>
                        @error('warranty')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Short default note. Detailed cards belong in <strong>Policy details</strong> below.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card brand-form-card mb-3">
            <div class="card-body">
                @include('admin.partials.policy-manager', [
                    'owner' => 'brand',
                    'field' => 'policies',
                    'policies' => $brand->policies,
                    'heading' => 'Policy details',
                    'hint' => 'Add policy cards here (warranty, replacement, delivery). Products only select which of these to show.',
                ])
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center brand-form-footer">
            <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">
                Cancel / Reset
            </a>
            <div class="d-flex gap-2">
                @include('admin.partials.preview-button', ['type' => 'brand'])
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-1"></i>Save brand & policies
                </button>
            </div>
        </div>
    </form>
@endsection

@push('styles')
<style>
.brand-form-card { border-radius: 1rem; border: 1px solid rgba(15,23,42,.08); box-shadow: 0 10px 28px rgba(15,23,42,.04); }
.brand-form-section-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; }
.brand-logo-row { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
.brand-logo-preview {
    width: 88px; height: 88px; border-radius: 1rem; border: 1px solid rgba(15,23,42,.08);
    background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.brand-logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
.brand-logo-preview span { font-size: .75rem; color: #94a3b8; }
.brand-form-footer {
    position: sticky; bottom: 0; z-index: 5; background: rgba(255,255,255,.92);
    backdrop-filter: blur(8px); border: 1px solid rgba(15,23,42,.06); border-radius: 1rem;
    padding: .85rem 1rem; margin-top: .25rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('logo');
    const preview = document.getElementById('logoPreview');
    const placeholder = document.getElementById('logoPlaceholder');

    input?.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            placeholder?.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });
});
</script>
@include('admin.partials.policy-manager-scripts')
@endpush
