@extends('admin.layouts.app')

@section('title', 'Add Brand')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Brand',
        'breadcrumbs' => [
            'Catalog' => null,
            'Brands' => route('admin.brands.index'),
            'Add',
        ],
    ])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data" novalidate>
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

                    <div class="col-md-6">
                        <label for="logo" class="form-label">Logo</label>
                        <input type="file" name="logo" id="logo" accept="image/*"
                               class="form-control @error('logo') is-invalid @enderror">
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="mt-2">
                            <img id="logoPreview" src="" alt="Logo preview" class="rounded border d-none"
                                 style="max-height: 96px; object-fit: contain;">
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="warranty" class="form-label">Brand warranty</label>
                        <textarea name="warranty" id="warranty" rows="10"
                                  class="form-control @error('warranty') is-invalid @enderror"
                                  data-rich-editor="1"
                                  data-editor-height="360">{{ old('warranty') }}</textarea>
                        @error('warranty')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            This warranty auto-fills when a product uses this brand. Changing it here does not change already-saved products.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Create Brand
                    </button>
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('logo');
    const preview = document.getElementById('logoPreview');

    input?.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) {
            preview.classList.add('d-none');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush

@push('scripts')
@include('admin.partials.rich-editor')
@endpush
