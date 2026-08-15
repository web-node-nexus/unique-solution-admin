@extends('admin.layouts.app')

@section('title', 'Refunds Report')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Refunds report',
        'breadcrumbs' => ['Reports', 'Refunds'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.refunds') }}" class="row g-3 align-items-end">
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
                        <a href="{{ route('admin.reports.refunds', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 'excel']) }}" class="btn btn-outline-success">Export Excel</a>
                        <a href="{{ route('admin.reports.refunds', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 'pdf']) }}" class="btn btn-outline-danger">Export PDF</a>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Refunds</div><div class="stat-value">{{ number_format($summary['count']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Approved amount</div><div class="stat-value">{{ format_money($summary['approved_amount']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Pending</div><div class="stat-value">{{ number_format($summary['pending_count']) }}</div></div></div></div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            @if ($refunds->isEmpty())
                @include('admin.partials.empty-state', ['title' => 'No refunds', 'message' => 'No refunds in this date range.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>ID</th><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @foreach ($refunds as $refund)
                                <tr>
                                    <td><a href="{{ route('admin.refunds.show', $refund) }}">#{{ $refund->id }}</a></td>
                                    <td>{{ $refund->order?->order_number ?? '—' }}</td>
                                    <td>{{ $refund->requestedBy?->name ?? '—' }}</td>
                                    <td>{{ format_money($refund->refund_amount) }}</td>
                                    <td>{{ ucfirst($refund->status) }}</td>
                                    <td class="small text-muted">{{ $refund->created_at?->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
