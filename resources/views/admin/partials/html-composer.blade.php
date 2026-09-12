@php
    $id = $id ?? 'description';
    $name = $name ?? $id;
    $value = $value ?? '';
    $rows = $rows ?? 18;
    $label = $label ?? 'HTML';
    $hint = $hint ?? 'Paste HTML as-is. It is saved unchanged and shown in the app in the same format.';
    $invalid = $invalid ?? false;
    $error = $error ?? null;
@endphp

<div class="html-composer {{ $invalid ? 'is-invalid' : '' }}" data-html-composer>
    <div class="html-composer-head">
        <div>
            <label for="{{ $id }}" class="form-label mb-0">{{ $label }}</label>
            <p class="html-composer-kicker">Raw HTML · saved exactly as written</p>
        </div>
        <div class="html-composer-switch" role="group" aria-label="Description view">
            <button type="button" class="html-composer-btn is-active" data-html-mode="write">
                <i class="bi bi-code-slash"></i>
                HTML
            </button>
            <button type="button" class="html-composer-btn" data-html-mode="preview">
                <i class="bi bi-eye"></i>
                Preview
            </button>
        </div>
    </div>

    <div class="html-composer-panes">
        <textarea
            name="{{ $name }}"
            id="{{ $id }}"
            rows="{{ $rows }}"
            spellcheck="false"
            autocomplete="off"
            wrap="off"
            class="html-composer-source {{ $invalid ? 'is-invalid' : '' }}"
            placeholder="Paste or type HTML here — tables, lists, headings, inline styles…"
            data-html-source
        >{{ $value }}</textarea>

        <div class="html-composer-preview" data-html-preview hidden>
            <div class="html-preview-device">
                <div class="html-preview-chrome">
                    <span></span><span></span><span></span>
                    <em>App preview</em>
                </div>
                <iframe
                    title="Description preview"
                    sandbox="allow-same-origin"
                    data-html-frame
                ></iframe>
            </div>
        </div>
    </div>

    @if ($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif
    <div class="form-text">{{ $hint }}</div>
</div>
