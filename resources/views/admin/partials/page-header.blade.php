@php
    $title = $title ?? '';
    $breadcrumbs = $breadcrumbs ?? [];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if (! empty($breadcrumbs))
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                    @foreach ($breadcrumbs as $label => $url)
                        @if (is_int($label))
                            <li class="breadcrumb-item active" aria-current="page">{{ $url }}</li>
                        @elseif ($loop->last || $url === null || $url === '#')
                            <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
    @isset($actions)
        <div class="page-actions">
            {!! $actions !!}
        </div>
    @endisset
</div>
