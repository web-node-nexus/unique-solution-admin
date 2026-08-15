@extends('admin.layouts.app')

@section('title', 'New Announcement')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'New Announcement',
        'breadcrumbs' => ['Announcements' => route('admin.notifications.index'), 'Create'],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.notifications.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.notifications._form', ['notification' => null])
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button type="submit" name="send_now" value="0" class="btn btn-outline-primary">Save draft</button>
                    <button type="submit" name="send_now" value="1" class="btn btn-primary">
                        <i class="bi bi-broadcast me-1"></i>Announce to all users
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
