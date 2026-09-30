@extends('admin.layouts.app')

@section('title', 'Edit Product')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit Product',
        'breadcrumbs' => [
            'Products' => route('admin.products.index'),
            $product->name => route('admin.products.show', $product),
            'Edit',
        ],
        'actions' => '<a href="'.route('admin.products.show', $product).'" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>View</a>',
    ])

    @include('admin.products.partials.wizard', ['product' => $product, 'wizardVariants' => $wizardVariants])
@endsection
