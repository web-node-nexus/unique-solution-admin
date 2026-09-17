@extends('admin.layouts.app')

@section('title', 'Edit Announcement')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Announcement',
        'breadcrumbs' => ['Announcements' => route('admin.notifications.index'), 'Edit'],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.notifications.update', $notification) }}" enctype="multipart/form-data" data-publish-form="announcement">
                @csrf
                @method('PUT')
                @include('admin.notifications._form', ['notification' => $notification])
                <div class="mt-3 d-flex flex-wrap gap-2">
                    @include('admin.partials.preview-button', ['type' => 'announcement'])
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save announcement
                    </button>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.notifications._form-scripts')
@endpush
