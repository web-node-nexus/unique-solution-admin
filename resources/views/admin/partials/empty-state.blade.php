@php
    $icon = $icon ?? 'bi-inbox';
    $title = $title ?? 'Nothing here yet';
    $message = $message ?? 'There is no data to display.';
@endphp

<div class="empty-state">
    <div class="empty-icon">
        <i class="bi {{ $icon }}"></i>
    </div>
    <div class="empty-title">{{ $title }}</div>
    <p class="empty-message">{{ $message }}</p>
    @isset($action)
        <div class="empty-action">
            {!! $action !!}
        </div>
    @endisset
</div>
