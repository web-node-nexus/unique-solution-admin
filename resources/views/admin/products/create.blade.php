@extends('admin.layouts.app')

@section('title', 'Add Product')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Add Product',
        'breadcrumbs' => [
            'Products' => route('admin.products.index'),
            'Add',
        ],
    ])

    @include('admin.products.partials.wizard', ['product' => null, 'wizardVariants' => null])
@endsection
