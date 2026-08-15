@extends('admin.layouts.app')

@section('title', 'Edit Banner')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Banner',
        'subtitle' => $banner->title,
        'breadcrumbs' => [
            'Banners' => route('admin.banners.index'),
            'Edit',
        ],
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data" id="bannerForm">
                @csrf
                @method('PUT')
                @include('admin.banners._form', ['banner' => $banner, 'nextSort' => $banner->sort_order])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" data-loading-text="Updating...">
                        <i class="bi bi-check-lg me-1"></i>Update Banner
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
