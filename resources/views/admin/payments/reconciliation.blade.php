@extends('admin.layouts.app')

@section('title', 'Payment Reconciliation')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Payment reconciliation',
        'breadcrumbs' => [
            'Payments' => route('admin.payments.index'),
            'Reconciliation',
        ],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.payments.reconciliation') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input type="date" name="from" id="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input type="date" name="to" id="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @forelse ($summary as $row)
            <div class="col-6 col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-label">{{ ucfirst($row->status) }}</div>
                        <div class="stat-value">{{ format_money($row->total) }}</div>
                        <div class="stat-meta">{{ $row->count }} payment(s)</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                @include('admin.partials.empty-state', [
                    'icon' => 'bi-credit-card',
                    'title' => 'No payments in range',
                    'message' => 'Try a different date range.',
                ])
            </div>
        @endforelse
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-label">Orders without payment</div>
                    <div class="stat-value">{{ number_format($ordersWithoutPayment) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header">Mismatched paid orders</div>
        <div class="card-body p-0">
            @if ($mismatched->isEmpty())
                @include('admin.partials.empty-state', [
                    'icon' => 'bi-check2-circle',
                    'title' => 'No mismatches',
                    'message' => 'No paid orders missing a paid payment record in this range.',
                ])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Payment status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mismatched as $order)
                                <tr>
                                    <td>{{ $order->order_number }}</td>
                                    <td>{{ $order->user?->name ?? '—' }}</td>
                                    <td>{{ format_money($order->total_amount) }}</td>
                                    <td>{{ $order->payment_status }}</td>
                                    <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
