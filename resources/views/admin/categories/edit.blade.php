@extends('admin.layouts.app')

@section('title', 'Edit Category')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Category',
        'breadcrumbs' => [
            'Catalog' => null,
            'Categories' => route('admin.categories.index'),
            $category->name => route('admin.categories.show', $category),
            'Edit',
        ],
    ])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.update', $category) }}" method="POST"
                  enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $category->name) }}" required>
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
                                <option value="{{ $parent->id }}"
                                    @selected(old('parent_id', $category->parent_id) == $parent->id)>
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
                            @php
                                $imageUrl = $category->image ? asset('storage/'.$category->image) : '';
                            @endphp
                            <img id="imagePreview"
                                 src="{{ $imageUrl }}"
                                 alt="Preview"
                                 class="rounded border {{ $imageUrl ? '' : 'd-none' }}"
                                 style="max-height: 120px; object-fit: contain;">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="sort_order" class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', $category->sort_order) }}">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status"
                                class="form-select @error('status') is-invalid @enderror">
                            <option value="1" @selected(old('status', $category->status ? '1' : '0') == '1')>Active</option>
                            <option value="0" @selected(old('status', $category->status ? '1' : '0') === '0')>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold"><i class="bi bi-tag me-1 text-danger"></i>Category sale banner</div>
                                    <div class="small text-muted">Is category pe sale chal rahi hai to banner upload karein — app me dikhega.</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="sale_active" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="sale_active" id="sale_active" value="1"
                                           @checked(old('sale_active', $category->sale_active))>
                                    <label class="form-check-label" for="sale_active">Show on app</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="sale_title" class="form-label">Sale title</label>
                                    <input type="text" name="sale_title" id="sale_title"
                                           class="form-control @error('sale_title') is-invalid @enderror"
                                           value="{{ old('sale_title', $category->sale_title) }}"
                                           placeholder="e.g. Refrigerator Festival">
                                    @error('sale_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="sale_subtitle" class="form-label">Sale subtitle</label>
                                    <input type="text" name="sale_subtitle" id="sale_subtitle"
                                           class="form-control @error('sale_subtitle') is-invalid @enderror"
                                           value="{{ old('sale_subtitle', $category->sale_subtitle) }}"
                                           placeholder="e.g. Extra exchange bonus">
                                    @error('sale_subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="sale_banner" class="form-label">Sale banner image</label>
                                    <input type="file" name="sale_banner" id="sale_banner" accept="image/jpeg,image/png,image/webp"
                                           class="form-control @error('sale_banner') is-invalid @enderror">
                                    @error('sale_banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Recommended wide banner. Leave empty to keep current.</div>
                                    @php
                                        $saleBannerUrl = $category->sale_banner ? asset('storage/'.$category->sale_banner) : '';
                                    @endphp
                                    <div class="mt-2">
                                        <img id="saleBannerPreview"
                                             src="{{ $saleBannerUrl }}"
                                             alt="Sale banner preview"
                                             class="rounded border {{ $saleBannerUrl ? '' : 'd-none' }} w-100"
                                             style="max-height: 160px; object-fit: cover;">
                                    </div>
                                    @if ($category->sale_banner)
                                        <div class="form-check mt-2">
                                            <input type="hidden" name="remove_sale_banner" value="0">
                                            <input class="form-check-input" type="checkbox" name="remove_sale_banner"
                                                   id="remove_sale_banner" value="1"
                                                   @checked(old('remove_sale_banner'))>
                                            <label class="form-check-label text-danger" for="remove_sale_banner">
                                                Remove current sale banner
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Attributes</label>
                        @if ($attributes->isEmpty())
                            <p class="small text-muted mb-0">No active attributes available.</p>
                        @else
                            @php
                                $selectedIds = old('attribute_ids', $category->attributes->pluck('id')->all());
                            @endphp
                            <div class="row g-2">
                                @foreach ($attributes as $attribute)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   name="attribute_ids[]"
                                                   id="attr_{{ $attribute->id }}"
                                                   value="{{ $attribute->id }}"
                                                   @checked(in_array($attribute->id, $selectedIds))>
                                            <label class="form-check-label" for="attr_{{ $attribute->id }}">
                                                {{ $attribute->name }}
                                                <span class="text-muted small">({{ $attribute->type }})</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Update Category
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
            if (!file) return;
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
