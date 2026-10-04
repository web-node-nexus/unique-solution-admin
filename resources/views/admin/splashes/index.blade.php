@extends('admin.layouts.app')

@section('title', 'Splash screens')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Splash screens',
        'subtitle' => 'The app loads this screen from the server. Change the default or schedule a poster — no app update is needed.',
        'breadcrumbs' => ['Splash screens'],
        'actions' => auth()->user()?->can('banners.create')
            ? '<a href="'.route('admin.splashes.create').'" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Schedule a splash</a>'
            : '',
    ])

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            @if ($live?->imageUrl())
                <img src="{{ $live->imageUrl() }}" alt="" class="rounded" style="width:72px;height:128px;object-fit:cover;">
            @endif
            <div>
                <div class="small text-muted text-uppercase">Showing in the app today ({{ \Illuminate\Support\Carbon::parse($today)->format('d M Y') }})</div>
                <div class="fw-semibold">
                    @if ($live?->is_default)
                        Default splash
                    @elseif ($live)
                        Scheduled splash · {{ $live->starts_on?->format('d M Y') }} to {{ $live->ends_on?->format('d M Y') }}
                    @else
                        Bundled poster
                    @endif
                </div>
                <div class="small text-muted">A schedule starts at 12:00 AM on the start date and stays up through the end date. The next day at 12:00 AM the default splash returns.</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Default splash</div>
                <div class="card-body">
                    <p class="text-muted small">Shown whenever no scheduled splash is active. You can replace this image any time.</p>
                    @if ($default?->imageUrl())
                        <img src="{{ $default->imageUrl() }}" alt="Default splash" class="img-fluid rounded border mb-3" style="max-height:320px;">
                    @endif
                    @can('banners.update')
                        <form method="POST" action="{{ route('admin.splashes.default') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <label class="form-label" for="default_image">Replace image</label>
                            <input type="file" name="image" id="default_image" class="form-control @error('image') is-invalid @enderror" accept="image/*" required>
                            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text mb-3">{{ image_upload_hint('Portrait image works best.') }}</div>
                            <button type="submit" class="btn btn-primary">Save default</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Scheduled splashes</div>
                <div class="card-body p-0">
                    @if ($schedules->isEmpty())
                        @include('admin.partials.empty-state', [
                            'icon' => 'bi-phone',
                            'title' => 'No schedules yet',
                            'message' => 'The default splash stays on until you add a date range.',
                        ])
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($schedules as $splash)
                                        @php
                                            $state = $splash->state($today);
                                            $badge = match ($state) {
                                                'live' => 'text-bg-success',
                                                'scheduled' => 'text-bg-primary',
                                                'expired' => 'text-bg-secondary',
                                                default => 'text-bg-warning',
                                            };
                                            $label = match ($state) {
                                                'live' => 'Active now',
                                                'scheduled' => 'Upcoming',
                                                'expired' => 'Expired',
                                                default => 'Disabled',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                @if ($splash->imageUrl())
                                                    <img src="{{ $splash->imageUrl() }}" alt="" class="rounded" style="width:48px;height:84px;object-fit:cover;">
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $splash->starts_on?->format('d M Y') }}</div>
                                                <div class="small text-muted">to {{ $splash->ends_on?->format('d M Y') }} · 12:00 AM</div>
                                            </td>
                                            <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                                            <td class="text-end text-nowrap">
                                                @can('banners.update')
                                                    <a href="{{ route('admin.splashes.edit', $splash) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form action="{{ route('admin.splashes.toggle', $splash) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            {{ $splash->is_enabled ? 'Disable' : 'Enable' }}
                                                        </button>
                                                    </form>
                                                @endcan
                                                @can('banners.delete')
                                                    <form action="{{ route('admin.splashes.destroy', $splash) }}" method="POST" class="d-inline" data-confirm="Delete this scheduled splash?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
