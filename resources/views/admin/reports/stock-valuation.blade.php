@extends('admin.layouts.app')

@section('title', 'Stock Valuation')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Stock valuation',
        'breadcrumbs' => ['Reports', 'Stock valuation'],
        'actions' => auth()->user()?->can('reports.export')
            ? '<a href="'.route('admin.reports.stock-valuation', ['export' => 'excel']).'" class="btn btn-outline-success me-2">Export Excel</a>'
              .'<a href="'.route('admin.reports.stock-valuation', ['export' => 'pdf']).'" class="btn btn-outline-danger">Export PDF</a>'
            : null,
    ])

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">SKUs</div><div class="stat-value">{{ number_format($summary['sku_count']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Total units</div><div class="stat-value">{{ number_format($summary['total_units']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Total value</div><div class="stat-value">{{ format_money($summary['total_value']) }}</div></div></div></div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            @if ($variants->isEmpty())
                @include('admin.partials.empty-state', ['title' => 'No stock', 'message' => 'No product variants found.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Unit price</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($variants as $variant)
                                @php $unit = (float) ($variant->discount_price ?? $variant->price); @endphp
                                <tr>
                                    <td>{{ $variant->sku }}</td>
                                    <td>{{ $variant->product?->name ?? '—' }}</td>
                                    <td>{{ $variant->stock_quantity }}</td>
                                    <td>{{ format_money($unit) }}</td>
                                    <td>{{ format_money($unit * (int) $variant->stock_quantity) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
