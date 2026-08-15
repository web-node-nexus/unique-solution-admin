@extends('admin.layouts.app')

@section('title', 'Add Banner')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Carousel Banner',
        'subtitle' => 'Upload a banner image for the app home carousel.',
        'breadcrumbs' => [
            'Banners' => route('admin.banners.index'),
            'Create',
        ],
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" id="bannerForm">
                @csrf
                @include('admin.banners._form', ['banner' => null, 'nextSort' => $nextSort])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving...">
                        <i class="bi bi-check-lg me-1"></i>Save Banner
                    </button>
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.banners._form-scripts')
@endpush
