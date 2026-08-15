@extends('admin.layouts.app')

@section('title', 'Edit Attribute')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Attribute',
        'breadcrumbs' => [
            'Catalog' => null,
            'Attributes' => route('admin.attributes.index'),
            $attribute->name,
        ],
    ])

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Attribute details</div>
                <div class="card-body">
                    <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $attribute->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="type"
                                    class="form-select @error('type') is-invalid @enderror" required>
                                @foreach (['text' => 'Text', 'dropdown' => 'Dropdown', 'color-swatch' => 'Color swatch', 'number' => 'Number'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $attribute->type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="1" @selected(old('status', $attribute->status ? '1' : '0') == '1')>Active</option>
                                <option value="0" @selected(old('status', $attribute->status ? '1' : '0') === '0')>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Update Attribute
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Used in categories</div>
                <div class="card-body">
                    @forelse ($attribute->categories as $category)
                        <a href="{{ route('admin.categories.edit', $category) }}"
                           class="badge text-bg-light border text-decoration-none me-1 mb-1">
                            {{ $category->name }}
                        </a>
                    @empty
                        <p class="text-muted small mb-0">Not linked to any category yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Attribute values</span>
                    <span class="badge text-bg-light border">{{ $attribute->values->count() }}</span>
                </div>
                <div class="card-body">
                    @if ($attribute->values->isEmpty())
                        @include('admin.partials.empty-state', [
                            'icon' => 'bi-list-ul',
                            'title' => 'No values yet',
                            'message' => 'Add values below so products can use this attribute.',
                        ])
                    @else
                        <div class="table-responsive mb-3">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Value</th>
                                        @if ($attribute->type === 'color-swatch')
                                            <th>Preview</th>
                                        @endif
                                        <th style="width: 90px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($attribute->values as $value)
                                        <tr>
                                            <td class="fw-medium">{{ $value->value }}</td>
                                            @if ($attribute->type === 'color-swatch')
                                                <td>
                                                    @php
                                                        $hex = $value->extra_data['hex'] ?? null;
                                                        $img = $value->image_url;
                                                    @endphp
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if ($img)
                                                            <img src="{{ $img }}" alt="{{ $value->value }}"
                                                                 class="rounded border"
                                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                                        @elseif ($hex)
                                                            <span class="rounded border d-inline-block"
                                                                  style="width: 28px; height: 28px; background: {{ $hex }};"></span>
                                                        @else
                                                            —
                                                        @endif
                                                        @if ($hex)
                                                            <code class="small">{{ $hex }}</code>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                            <td class="text-end">
                                                <form action="{{ route('admin.attributes.values.destroy', [$attribute, $value]) }}"
                                                      method="POST" class="d-inline"
                                                      data-confirm="Delete this value?"
                                                      data-confirm-title="Delete value"
                                                      data-confirm-button="Yes, delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <hr>

                    <h3 class="h6 mb-3">Add value</h3>
                    <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST"
                          enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-{{ $attribute->type === 'color-swatch' ? '12' : '8' }}">
                                <label for="value" class="form-label">
                                    {{ $attribute->type === 'color-swatch' ? 'Color name' : 'Value' }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="value" id="value"
                                       class="form-control @error('value') is-invalid @enderror"
                                       value="{{ old('value') }}" required
                                       placeholder="{{ $attribute->type === 'color-swatch' ? 'e.g. Midnight Blue' : '' }}">
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($attribute->type === 'color-swatch')
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="color_mode"
                                                   id="color_mode_picker" value="picker"
                                                   @checked(old('color_mode', 'picker') === 'picker')>
                                            <label class="form-check-label" for="color_mode_picker">
                                                Choose color (picker)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="color_mode"
                                                   id="color_mode_image" value="image"
                                                   @checked(old('color_mode') === 'image')>
                                            <label class="form-check-label" for="color_mode_image">
                                                Name + product image (no picker)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4" data-color-picker-wrap>
                                    <label for="hex" class="form-label">Color (hex)</label>
                                    <input type="color" name="extra_data[hex]" id="hex"
                                           class="form-control form-control-color w-100 @error('extra_data.hex') is-invalid @enderror"
                                           value="{{ old('extra_data.hex', '#0d9488') }}">
                                    @error('extra_data.hex')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5" data-color-image-wrap>
                                    <label for="swatch_image" class="form-label">Color product image</label>
                                    <input type="file" name="swatch_image" id="swatch_image"
                                           accept="image/jpeg,image/png,image/webp"
                                           class="form-control @error('swatch_image') is-invalid @enderror">
                                    @error('swatch_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Photo of this color on a product. Shown when choosing colors later.</div>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <button type="submit" class="btn btn-soft w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Add
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($attribute->type === 'color-swatch')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('color_mode_picker');
    const image = document.getElementById('color_mode_image');
    const pickerWrap = document.querySelector('[data-color-picker-wrap]');
    const imageWrap = document.querySelector('[data-color-image-wrap]');
    const hex = document.getElementById('hex');
    const file = document.getElementById('swatch_image');

    function sync() {
        const useImage = image?.checked;
        pickerWrap?.classList.toggle('d-none', !!useImage);
        imageWrap?.classList.toggle('d-none', !useImage);
        if (hex) hex.disabled = !!useImage;
        if (file) file.required = !!useImage;
    }

    picker?.addEventListener('change', sync);
    image?.addEventListener('change', sync);
    sync();
});
</script>
@endpush
@endif
