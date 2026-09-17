@extends('admin.layouts.app')

@section('title', 'Add Offer')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Offer',
        'breadcrumbs' => ['Sales' => route('admin.sales.index'), 'Create'],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sales.store') }}" enctype="multipart/form-data" data-publish-form="sale">
                @csrf
                @include('admin.sales._form', ['sale' => null])
                <div class="mt-3 d-flex gap-2">
                    @include('admin.partials.preview-button', ['type' => 'sale'])
                    <button class="btn btn-primary" type="submit">Save Offer</button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.sales._form-scripts')
@endpush
