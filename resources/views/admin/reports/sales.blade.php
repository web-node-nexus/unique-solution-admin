@extends('admin.layouts.app')

@section('title', 'Sales Report')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Sales report',
        'breadcrumbs' => ['Reports', 'Sales'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input type="date" name="from" id="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input type="date" name="to" id="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @can('reports.export')
                        <a href="{{ route('admin.reports.sales', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 'excel']) }}" class="btn btn-outline-success">Export Excel</a>
                        <a href="{{ route('admin.reports.sales', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 'pdf']) }}" class="btn btn-outline-danger">Export PDF</a>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Orders</div><div class="stat-value">{{ number_format($summary['orders_count']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Revenue</div><div class="stat-value">{{ format_money($summary['revenue']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Avg order</div><div class="stat-value">{{ format_money($summary['avg_order']) }}</div></div></div></div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            @if ($orders->isEmpty())
                @include('admin.partials.empty-state', ['title' => 'No sales', 'message' => 'No orders found for this date range.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                    <td>{{ $order->user?->name ?? 'Guest' }}</td>
                                    <td>{{ ucfirst($order->order_status) }}</td>
                                    <td>{{ format_money($order->total_amount) }}</td>
                                    <td class="small text-muted">{{ $order->created_at?->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
