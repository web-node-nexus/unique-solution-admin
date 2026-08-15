@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Order '.$order->order_number,
        'breadcrumbs' => [
            'Orders' => route('admin.orders.index'),
            $order->order_number,
        ],
        'actions' => '<a href="'.route('admin.orders.invoice', $order).'" class="btn btn-outline-primary me-2"><i class="bi bi-file-earmark-pdf me-1"></i>Invoice PDF</a>'
            .'<a href="'.route('admin.orders.packing-slip', $order).'" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-printer me-1"></i>Packing Slip</a>',
    ])

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Customer</div>
                <div class="card-body">
                    <div class="fw-semibold">{{ $order->user?->name ?? 'Guest' }}</div>
                    <div class="text-muted small">{{ $order->user?->email }}</div>
                    <div class="text-muted small">{{ $order->user?->phone }}</div>
                    @if ($order->user)
                        <a href="{{ route('admin.customers.show', $order->user) }}" class="small fw-semibold">View profile</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Order summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between"><span>Status</span><span class="badge-status {{ $order->order_status }}">{{ $order->order_status }}</span></div>
                    <div class="d-flex justify-content-between mt-2"><span>Payment</span><span class="badge bg-secondary">{{ $order->payment_status }}</span></div>
                    <div class="d-flex justify-content-between mt-2"><span>Placed</span><span class="small text-muted">{{ $order->created_at?->format('d M Y, h:i A') }}</span></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ format_money($order->subtotal) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Discount</span><span>{{ format_money($order->discount) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Tax</span><span>{{ format_money($order->tax) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Shipping</span><span>{{ format_money($order->shipping_charge) }}</span></div>
                    <div class="d-flex justify-content-between fw-bold mt-2"><span>Total</span><span>{{ format_money($order->total_amount) }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Update status</div>
                <div class="card-body">
                    @can('orders.update')
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label" for="order_status">Status</label>
                                <select name="order_status" id="order_status" class="form-select" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected($order->order_status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="remarks">Remarks</label>
                                <textarea name="remarks" id="remarks" rows="2" class="form-control" placeholder="Optional notes">{{ old('remarks') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update status</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">You do not have permission to update order status.</p>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Shipping address</div>
                <div class="card-body"><pre class="mb-0 small" style="white-space:pre-wrap;font-family:inherit;">{{ $order->shipping_address }}</pre></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Billing address</div>
                <div class="card-body"><pre class="mb-0 small" style="white-space:pre-wrap;font-family:inherit;">{{ $order->billing_address }}</pre></div>
            </div>
        </div>
    </div>

    <div class="card table-card mb-3">
        <div class="card-header">Line items</div>
        <div class="card-body p-0">
            @if ($order->items->isEmpty())
                @include('admin.partials.empty-state', ['title' => 'No items', 'message' => 'This order has no line items.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variant</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->product_name_snapshot }}</td>
                                    <td class="small text-muted">
                                        @if (is_array($item->variant_details_snapshot))
                                            @foreach ($item->variant_details_snapshot as $k => $v)
                                                <div>{{ is_string($k) ? $k.': ' : '' }}{{ is_array($v) ? implode(', ', $v) : $v }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->variant_details_snapshot }}
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ format_money($item->price) }}</td>
                                    <td>{{ format_money($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Payments</div>
                <div class="card-body p-0">
                    @if ($order->payments->isEmpty())
                        @include('admin.partials.empty-state', ['icon' => 'bi-credit-card', 'title' => 'No payments', 'message' => 'No payment records for this order.'])
                    @else
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead><tr><th>Method</th><th>Txn</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach ($order->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_method }}</td>
                                            <td class="small">{{ $payment->transaction_id ?? '—' }}</td>
                                            <td>{{ format_money($payment->amount) }}</td>
                                            <td><span class="badge bg-secondary">{{ $payment->status }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Status timeline</div>
                <div class="card-body">
                    @if ($order->statusHistory->isEmpty())
                        <p class="text-muted mb-0">No status history yet.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($order->statusHistory->sortByDesc('created_at') as $history)
                                <li class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-semibold">{{ ucfirst($history->status) }}</span>
                                        <span class="small text-muted">{{ $history->created_at?->format('d M Y, h:i A') }}</span>
                                    </div>
                                    <div class="small text-muted">By {{ $history->changedBy?->name ?? 'System' }}</div>
                                    @if ($history->remarks)
                                        <div class="small mt-1">{{ $history->remarks }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
