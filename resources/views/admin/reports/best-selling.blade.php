@extends('admin.layouts.app')

@section('title', 'Best Selling')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Best selling products',
        'breadcrumbs' => ['Reports', 'Best selling'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.best-selling') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input type="date" name="from" id="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input type="date" name="to" id="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="limit">Limit</label>
                    <input type="number" min="5" max="100" name="limit" id="limit" class="form-control" value="{{ request('limit', 20) }}">
                </div>
                <div class="col-md-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @can('reports.export')
                        <a href="{{ route('admin.reports.best-selling', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'limit' => request('limit', 20), 'export' => 'pdf']) }}" class="btn btn-outline-danger">Export PDF</a>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            @if ($products->isEmpty())
                @include('admin.partials.empty-state', ['title' => 'No products', 'message' => 'No sales data for this range.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Product</th><th>Units sold</th><th>Revenue</th></tr></thead>
                        <tbody>
                            @foreach ($products as $i => $product)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $product->product_name }}</td>
                                    <td>{{ number_format($product->units_sold) }}</td>
                                    <td>{{ format_money($product->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
