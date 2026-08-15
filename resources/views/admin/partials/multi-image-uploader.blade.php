{{--
  Multi image uploader with live preview, remove, and admin-controlled sort order.
  Props:
    $inputId (default: images)
    $inputName (default: images[])
    $existingImages (optional collection of ProductImage)
    $help (optional string)
--}}
@php
    $inputId = $inputId ?? 'images';
    $inputName = $inputName ?? 'images[]';
    $existingImages = $existingImages ?? collect();
    $help = $help ?? 'JPG, PNG, WebP — drag cards to choose which photo shows 1st, 2nd, 3rd…';
@endphp

<div class="multi-image-uploader" data-uploader="{{ $inputId }}">
    <label class="form-label" for="{{ $inputId }}">
        {{ $existingImages->isNotEmpty() ? 'Product images' : 'Product images' }}
    </label>

    <div class="multi-image-dropzone" data-dropzone>
        <input type="file"
               name="{{ $inputName }}"
               id="{{ $inputId }}"
               class="multi-image-input @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
               accept="image/jpeg,image/png,image/webp,image/jpg"
               multiple>
        <div class="multi-image-dropzone-inner">
            <i class="bi bi-images"></i>
            <div class="fw-semibold">Click or drop images here</div>
            <div class="small text-muted">{{ $help }}</div>
        </div>
    </div>
    @error('images')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('images.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-2">
        <div class="small text-muted mb-0">
            Drag cards or use arrows to set display order. Position 1 is the main/primary image.
        </div>
        <div class="form-text mb-0" data-file-count>No new images selected.</div>
    </div>

    <div class="multi-image-grid" data-preview-grid id="{{ $inputId }}Preview">
        @foreach ($existingImages as $image)
            <div class="multi-image-card existing"
                 draggable="true"
                 data-existing-id="{{ $image->id }}"
                 data-sort-token="existing:{{ $image->id }}">
                <span class="multi-image-pos" data-pos></span>
                <img src="{{ asset('storage/'.$image->image_path) }}" alt="Product image">
                <button type="button"
                        class="multi-image-remove"
                        data-remove-existing="{{ $image->id }}"
                        title="Remove image"
                        aria-label="Remove image">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="multi-image-sort">
                    <button type="button" class="multi-image-sort-btn" data-move="up" title="Move left / earlier" aria-label="Move earlier">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="multi-image-sort-btn" data-move="down" title="Move right / later" aria-label="Move later">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <label class="multi-image-primary">
                    <input type="radio"
                           name="primary_image_id"
                           value="{{ $image->id }}"
                           @checked($image->is_primary || $loop->first)>
                    <span>Primary</span>
                </label>
                <input type="checkbox"
                       class="d-none existing-remove-flag"
                       name="remove_image_ids[]"
                       value="{{ $image->id }}"
                       id="remove_image_{{ $image->id }}">
            </div>
        @endforeach
    </div>
    <div data-gallery-order></div>
</div>
