@php
    $name = $name ?? 'status';
    $id = $id ?? 'status';
    $onValue = $onValue ?? '1';
    $offValue = $offValue ?? '0';
    $checked = (bool) ($checked ?? false);
    $scheduled = (bool) ($scheduled ?? false);
    $title = $title ?? 'App visibility';
@endphp

<div class="publish-panel" data-publish-panel>
    <div class="d-flex align-items-start justify-content-between gap-3">
        <div>
            <div class="fw-semibold">{{ $title }}</div>
            <div class="small text-muted mb-0">
                New items stay <strong>Deactive</strong> until you review them and turn this on.
            </div>
        </div>
        <label class="publish-switch publish-switch-lg mb-0">
            <input type="hidden" name="{{ $name }}" value="{{ $offValue }}">
            <input class="js-form-status-toggle" type="checkbox" role="switch"
                   name="{{ $name }}" id="{{ $id }}" value="{{ $onValue }}"
                   @checked($checked)>
            <span class="publish-slider"></span>
        </label>
    </div>
    <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
        <span class="badge {{ $checked ? 'd-none' : '' }} bg-secondary js-publish-off">Deactive</span>
        <span class="badge {{ $checked ? '' : 'd-none' }} bg-success js-publish-on">Active</span>
        @if ($scheduled)
            <span class="badge bg-info-subtle text-info-emphasis">Schedule controlled</span>
        @endif
    </div>
    @if ($scheduled)
        <p class="small text-muted mt-2 mb-0">
            Turning this on does not show it immediately. It appears on the app only at the start date and time, and hides automatically when the end time is reached.
        </p>
    @endif
    <div class="activation-alert alert alert-danger d-none mt-3 mb-0" data-activation-alert role="alert"></div>
</div>
