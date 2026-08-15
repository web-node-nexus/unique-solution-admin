@extends('admin.layouts.app')

@section('title', 'Add Sale')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Sale',
        'breadcrumbs' => ['Sales' => route('admin.sales.index'), 'Create'],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sales.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.sales._form', ['sale' => null])
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Sale</button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('admin.sales._form-scripts')
@endpush
