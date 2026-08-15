@extends('admin.layouts.app')

@section('title', 'Add Attribute')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Attribute',
        'breadcrumbs' => [
            'Catalog' => null,
            'Attributes' => route('admin.attributes.index'),
            'Add',
        ],
    ])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.attributes.store') }}" method="POST" novalidate>
                @csrf

                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="type"
                                class="form-select @error('type') is-invalid @enderror" required>
                            @foreach (['text' => 'Text', 'dropdown' => 'Dropdown', 'color-swatch' => 'Color swatch', 'number' => 'Number'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
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
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Create Attribute
                    </button>
                    <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
