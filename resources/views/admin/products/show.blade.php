@extends('admin.layouts.app')

@section('title', $product->name)

@section('content')
    @php
        $actions = '';
        if (auth()->user()?->can('products.update')) {
            $actions .= '<a href="'.route('admin.products.edit', $product).'" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>';
        }
        if (auth()->user()?->can('products.create')) {
            $actions .= '<form action="'.route('admin.products.clone', $product).'" method="POST" class="d-inline" data-confirm="Clone this product?" data-confirm-title="Clone product" data-confirm-button="Yes, clone">'
                .csrf_field()
                .'<button type="submit" class="btn btn-outline-info"><i class="bi bi-copy me-1"></i>Clone</button></form>';
        }
    @endphp

    @include('admin.partials.page-header', [
        'title' => $product->name,
        'breadcrumbs' => [
            'Catalog' => null,
            'Products' => route('admin.products.index'),
            $product->name,
        ],
        'actions' => $actions !== '' ? $actions : null,
    ])

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    @php $primary = $product->images->firstWhere('is_primary', true) ?? $product->images->first(); @endphp
                    @if ($primary)
                        <img src="{{ asset('storage/'.$primary->image_path) }}"
                             alt="{{ $product->name }}"
                             class="w-100 rounded border mb-3"
                             style="max-height: 280px; object-fit: cover;">
                    @else
                        <div class="text-center text-muted py-5 mb-3 border rounded">
                            <i class="bi bi-image" style="font-size: 2.5rem;"></i>
                            <div class="small mt-2">No product image</div>
                        </div>
                    @endif

                    @if ($product->images->count() > 1)
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($product->images as $image)
                                <img src="{{ asset('storage/'.$image->image_path) }}"
                                     alt="" class="rounded border"
                                     style="width: 56px; height: 56px; object-fit: cover;">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Product details</span>
                    <span class="badge bg-{{ ['active' => 'success', 'inactive' => 'secondary', 'draft' => 'warning'][$product->status] ?? 'secondary' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Slug</dt>
                        <dd class="col-sm-9"><code>{{ $product->slug }}</code></dd>

                        <dt class="col-sm-3">Category</dt>
                        <dd class="col-sm-9">{{ $product->category?->name ?? '—' }}</dd>

                        <dt class="col-sm-3">Brand</dt>
                        <dd class="col-sm-9">{{ $product->brand?->name ?? '—' }}</dd>

                        <dt class="col-sm-3">Base price</dt>
                        <dd class="col-sm-9">{{ format_money($product->base_price) }}</dd>

                        <dt class="col-sm-3">Featured</dt>
                        <dd class="col-sm-9">{{ $product->is_featured ? 'Yes' : 'No' }}</dd>

                        <dt class="col-sm-3">Warranty</dt>
                        <dd class="col-sm-9">
                            @if ($product->warranty_info)
                                <div class="product-description prose-specs">{!! $product->warranty_info !!}</div>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-3">Created by</dt>
                        <dd class="col-sm-9">{{ $product->creator?->name ?? '—' }}</dd>

                        <dt class="col-sm-3">Meta title</dt>
                        <dd class="col-sm-9">{{ $product->meta_title ?: '—' }}</dd>

                        <dt class="col-sm-3">Meta description</dt>
                        <dd class="col-sm-9">{{ $product->meta_description ?: '—' }}</dd>
                    </dl>

                    @if ($product->description)
                        <hr>
                        <div class="fw-semibold mb-2">Full description / specifications</div>
                        <div class="product-description prose-specs">{!! $product->description !!}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Variants &amp; stock</span>
            <span class="badge text-bg-light border">{{ $product->variants->count() }} variants</span>
        </div>
        <div class="card-body p-0">
            @if ($product->variants->isEmpty())
                @include('admin.partials.empty-state', [
                    'icon' => 'bi-box',
                    'title' => 'No variants',
                    'message' => 'This product has no variants yet.',
                ])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>SKU</th>
                                <th>Attributes</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($product->variants as $variant)
                                @php
                                    $vImg = $variant->images->first();
                                    $stockClass = $variant->stock_quantity <= 0
                                        ? 'danger'
                                        : ($variant->stock_quantity <= $variant->low_stock_threshold ? 'warning' : 'success');
                                @endphp
                                <tr>
                                    <td>
                                        @if ($vImg)
                                            <img src="{{ asset('storage/'.$vImg->image_path) }}"
                                                 alt="" class="rounded border"
                                                 style="width: 44px; height: 44px; object-fit: cover;">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $variant->sku }}</code></td>
                                    <td>
                                        @forelse ($variant->attributeValues as $av)
                                            <span class="badge text-bg-light border me-1">
                                                {{ $av->attribute?->name ? $av->attribute->name.': ' : '' }}{{ $av->value }}
                                            </span>
                                        @empty
                                            <span class="text-muted">Default</span>
                                        @endforelse
                                    </td>
                                    <td>{{ format_money($variant->price) }}</td>
                                    <td>{{ $variant->discount_price !== null ? format_money($variant->discount_price) : '—' }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $stockClass }}">
                                            {{ $variant->stock_quantity }}
                                        </span>
                                        <div class="small text-muted">Low ≤ {{ $variant->low_stock_threshold }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $variant->status ? 'success' : 'secondary' }}">
                                            {{ $variant->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
<style>
    .prose-specs { line-height: 1.65; color: #1e293b; }
    .prose-specs h1, .prose-specs h2, .prose-specs h3, .prose-specs h4 { margin-top: 1rem; color: #0f172a; }
    .prose-specs table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
    .prose-specs th, .prose-specs td { border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; }
    .prose-specs th { background: #f1f5f9; }
    .prose-specs img { max-width: 100%; height: auto; }
    .prose-specs ul, .prose-specs ol { padding-left: 1.25rem; }
</style>
@endpush
