@extends('admin.layouts.app')

@section('title', 'Add Category')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Category',
        'breadcrumbs' => [
            'Catalog' => null,
            'Categories' => route('admin.categories.index'),
            'Add',
        ],
    ])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf

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

                    <div class="col-md-6">
                        <label for="parent_id" class="form-label">Parent category</label>
                        <select name="parent_id" id="parent_id"
                                class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">— None —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="image" class="form-label">Category image</label>
                        <input type="file" name="image" id="image" accept="image/*"
                               class="form-control @error('image') is-invalid @enderror">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Normal category thumbnail for app grid.</div>
                        <div class="mt-2">
                            <img id="imagePreview" src="" alt="Preview"
                                 class="rounded border d-none"
                                 style="max-height: 120px; object-fit: contain;">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="sort_order" class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', 0) }}">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status"
                                class="form-select @error('status') is-invalid @enderror">
                            <option value="1" @selected(old('status', '1') == '1')>Active</option>
                            <option value="0" @selected(old('status') === '0')>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <div class="fw-semibold"><i class="bi bi-tag me-1 text-danger"></i>Category sale banner</div>
                                    <div class="small text-muted">Upload a sale banner for this category — app users will see it for this category.</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="sale_active" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="sale_active" id="sale_active" value="1"
                                           @checked(old('sale_active'))>
                                    <label class="form-check-label" for="sale_active">Show on app</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="sale_title" class="form-label">Sale title</label>
                                    <input type="text" name="sale_title" id="sale_title"
                                           class="form-control @error('sale_title') is-invalid @enderror"
                                           value="{{ old('sale_title') }}"
                                           placeholder="e.g. Android Phones Mega Sale">
                                    @error('sale_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="sale_subtitle" class="form-label">Sale subtitle</label>
                                    <input type="text" name="sale_subtitle" id="sale_subtitle"
                                           class="form-control @error('sale_subtitle') is-invalid @enderror"
                                           value="{{ old('sale_subtitle') }}"
                                           placeholder="e.g. Up to 20% off this week">
                                    @error('sale_subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="sale_banner" class="form-label">Sale banner image</label>
                                    <input type="file" name="sale_banner" id="sale_banner" accept="image/jpeg,image/png,image/webp"
                                           class="form-control @error('sale_banner') is-invalid @enderror">
                                    @error('sale_banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Recommended wide banner (e.g. 1200×400). Max 5MB.</div>
                                    <div class="mt-2">
                                        <img id="saleBannerPreview" src="" alt="Sale banner preview"
                                             class="rounded border d-none w-100"
                                             style="max-height: 160px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Attributes</label>
                        @if ($attributes->isEmpty())
                            <p class="small text-muted mb-0">No active attributes available.</p>
                        @else
                            <div class="row g-2">
                                @foreach ($attributes as $attribute)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   class="form-check-input @error('attribute_ids') is-invalid @enderror"
                                                   name="attribute_ids[]"
                                                   id="attr_{{ $attribute->id }}"
                                                   value="{{ $attribute->id }}"
                                                   @checked(in_array($attribute->id, old('attribute_ids', [])))>
                                            <label class="form-check-label" for="attr_{{ $attribute->id }}">
                                                {{ $attribute->name }}
                                                <span class="text-muted small">({{ $attribute->type }})</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('attribute_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Create Category
                    </button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindPreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        input?.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) {
                preview.classList.add('d-none');
                preview.removeAttribute('src');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }
    bindPreview('image', 'imagePreview');
    bindPreview('sale_banner', 'saleBannerPreview');
});
</script>
@endpush
