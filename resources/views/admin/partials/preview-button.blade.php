@php
    $hint = $hint ?? null;
    $hidden = (bool) ($hidden ?? false);
    $wrapId = $wrapId ?? null;
@endphp

<div @if ($wrapId) id="{{ $wrapId }}" @endif
     class="d-inline-flex flex-column align-items-end gap-1 {{ $hidden ? 'd-none' : '' }}"
     data-preview-wrap>
    <button type="button"
            class="btn btn-outline-primary js-app-preview"
            data-preview="{{ $type }}"
            title="Preview how this looks in the app">
        <i class="bi bi-phone me-1"></i>Preview
    </button>
    @if ($hint)
        <span class="small text-muted">{{ $hint }}</span>
    @endif
</div>
