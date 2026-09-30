{{--
  Multi image uploader with live preview, remove, and admin-controlled sort order.
  Position 1 is always the main thumbnail.
  Props:
    $inputId (default: images)
    $inputName (default: images[])
    $existingImages (optional collection of ProductImage)
    $uploadUrl (optional) — upload each photo in the background and post tokens instead of files
    $help (optional string)
--}}
@php
    $inputId = $inputId ?? 'images';
    $inputName = $inputName ?? 'images[]';
    $existingImages = $existingImages ?? collect();
    $uploadUrl = $uploadUrl ?? null;
    $help = $help ?? image_upload_hint('Select many photos at once. The 1st photo becomes the thumbnail.');
@endphp

<div class="multi-image-uploader" data-uploader="{{ $inputId }}" @if ($uploadUrl) data-upload-url="{{ $uploadUrl }}" @endif>
    <label class="form-label" for="{{ $inputId }}">Product images</label>

    <div class="multi-image-dropzone" data-dropzone>
        <input type="file"
               name="{{ $inputName }}"
               id="{{ $inputId }}"
               class="multi-image-input @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
               accept="{{ config('uploads.image_accept') }}"
               multiple>
        <div class="multi-image-dropzone-inner">
            <i class="bi bi-images"></i>
            <div class="fw-semibold">Click or drop images here (bulk upload)</div>
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
            Drag cards or use arrows to reorder. <strong>Position 1 = main thumbnail</strong> — tap <i class="bi bi-star"></i> to make any photo the thumbnail.
        </div>
        <div class="form-text mb-0" data-file-count>No images yet.</div>
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
                    <button type="button" class="multi-image-sort-btn" data-move="first" title="Make thumbnail (move to 1st)" aria-label="Make thumbnail">
                        <i class="bi bi-star"></i>
                    </button>
                    <button type="button" class="multi-image-sort-btn" data-move="down" title="Move right / later" aria-label="Move later">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
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
