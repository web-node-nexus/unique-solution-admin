@extends('admin.layouts.app')

@php $editing = $splash->exists; @endphp

@section('title', $editing ? 'Edit splash schedule' : 'Schedule a splash')

@section('content')
    @include('admin.partials.page-header', [
        'title' => $editing ? 'Edit splash schedule' : 'Schedule a splash',
        'subtitle' => 'The splash turns on at 12:00 AM on the start date and turns off at 12:00 AM the day after the end date.',
        'breadcrumbs' => [
            'Splash screens' => route('admin.splashes.index'),
            $editing ? 'Edit' : 'Schedule',
        ],
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST"
                  action="{{ $editing ? route('admin.splashes.update', $splash) : route('admin.splashes.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div class="row g-4">
                    <div class="col-md-5">
                        @if ($editing && $splash->imageUrl())
                            <img src="{{ $splash->imageUrl() }}" alt="" class="img-fluid rounded border mb-3" style="max-height:360px;">
                        @endif
                        <label class="form-label" for="image">Splash image {{ $editing ? '(optional)' : '' }}</label>
                        <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/*" @required(! $editing)>
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ image_upload_hint('Portrait image works best.') }}</div>
                    </div>
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label" for="starts_on">Start date</label>
                            <input type="date" name="starts_on" id="starts_on" class="form-control @error('starts_on') is-invalid @enderror"
                                   value="{{ old('starts_on', $splash->starts_on?->toDateString()) }}" required>
                            @error('starts_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Goes live at 12:00:00 AM on this date.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="ends_on">End date</label>
                            <input type="date" name="ends_on" id="ends_on" class="form-control @error('ends_on') is-invalid @enderror"
                                   value="{{ old('ends_on', $splash->ends_on?->toDateString()) }}" required>
                            @error('ends_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Stays up through 11:59:59 PM on this date. The next day at 12:00 AM the default splash returns.</div>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_enabled" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_enabled" id="is_enabled" value="1"
                                   @checked(filter_var(old('is_enabled', $splash->is_enabled ?? true), FILTER_VALIDATE_BOOLEAN))>
                            <label class="form-check-label" for="is_enabled">Enabled</label>
                        </div>
                        <p class="text-muted small mt-3 mb-0">If these dates overlap another enabled schedule, save is blocked. Disable or edit the other one first so only one splash is on at a time.</p>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editing ? 'Save schedule' : 'Schedule splash' }}</button>
                    <a href="{{ route('admin.splashes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
