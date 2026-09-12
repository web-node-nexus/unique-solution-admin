@extends('admin.layouts.app')

@section('title', $category->name)

@section('content')
    @php
        $actions = '';
        if (auth()->user()?->can('categories.update')) {
            $actions .= '<a href="'.route('admin.categories.edit', $category).'" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>';
        }
    @endphp

    @include('admin.partials.page-header', [
        'title' => $category->name,
        'breadcrumbs' => [
            'Catalog' => null,
            'Categories' => route('admin.categories.index'),
            $category->name,
        ],
        'actions' => $actions !== '' ? $actions : null,
    ])

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Details</span>
                    <span class="badge bg-{{ $category->status ? 'success' : 'secondary' }}">
                        {{ $category->status ? 'Active' : 'Inactive' }}
                    </span>
                    @if ($category->hasActiveSaleBanner())
                        <span class="badge bg-danger">Sale ON</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($category->image)
                        <img src="{{ asset('storage/'.$category->image) }}"
                             alt="{{ $category->name }}"
                             class="w-100 rounded border mb-3"
                             style="max-height: 200px; object-fit: cover;">
                    @endif
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Slug</dt>
                        <dd class="col-sm-8"><code>{{ $category->slug }}</code></dd>
                        <dt class="col-sm-4">Sort order</dt>
                        <dd class="col-sm-8">{{ $category->sort_order }}</dd>
                        <dt class="col-sm-4">Sale</dt>
                        <dd class="col-sm-8">
                            @if ($category->hasActiveSaleBanner())
                                <span class="text-danger fw-semibold">Active on app</span>
                                @if ($category->sale_title)
                                    <div class="small">{{ $category->sale_title }}</div>
                                @endif
                                @if ($category->sale_subtitle)
                                    <div class="small text-muted">{{ $category->sale_subtitle }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                    @if ($category->sale_banner)
                        <hr>
                        <div class="fw-semibold mb-2">Sale banner</div>
                        <img src="{{ asset('storage/'.$category->sale_banner) }}"
                             alt="Sale banner"
                             class="w-100 rounded border"
                             style="max-height: 180px; object-fit: cover;">
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Linked attributes</div>
                <div class="card-body">
                    @forelse ($category->attributes as $attribute)
                        <div class="mb-3 {{ ! $loop->last ? 'border-bottom pb-3' : '' }}">
                            <div class="fw-semibold">
                                {{ $attribute->name }}
                                <span class="badge text-bg-light border fw-normal">{{ $attribute->type }}</span>
                            </div>
                            <div class="small text-muted mt-1">
                                {{ $attribute->values->pluck('value')->implode(', ') ?: 'No values' }}
                            </div>
                        </div>
                    @empty
                        @include('admin.partials.empty-state', [
                            'icon' => 'bi-sliders',
                            'title' => 'No attributes',
                            'message' => 'Link attributes when editing this category.',
                        ])
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
