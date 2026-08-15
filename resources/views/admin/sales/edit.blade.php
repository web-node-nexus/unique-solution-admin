@extends('admin.layouts.app')

@section('title', 'Edit Sale')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Sale',
        'breadcrumbs' => ['Sales' => route('admin.sales.index'), 'Edit'],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sales.update', $sale) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.sales._form', ['sale' => $sale])
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Update Sale</button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.sales._form-scripts')
@endpush
