{{-- Clear max-size notice for every image file field --}}
@php
    $extra = $extra ?? null;
@endphp
<div class="form-text image-upload-hint">{{ image_upload_hint($extra) }}</div>
