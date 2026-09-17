@extends('admin.layouts.app')

@section('title', 'Edit Sale')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Offer',
        'breadcrumbs' => ['Sales' => route('admin.sales.index'), 'Edit'],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sales.update', $sale) }}" enctype="multipart/form-data" data-publish-form="sale">
                @csrf
                @method('PUT')
                @include('admin.sales._form', ['sale' => $sale])
                <div class="mt-3 d-flex gap-2">
                    @include('admin.partials.preview-button', ['type' => 'sale'])
                    <button class="btn btn-primary" type="submit">Update Offer</button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.sales._form-scripts')
@endpush
