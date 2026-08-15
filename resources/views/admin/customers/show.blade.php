@extends('admin.layouts.app')

@section('title', $customer->name)

@section('content')
    @php
        $lifetimeSpend = (float) $customer->orders()->sum('total_amount');
        $ordersCount = (int) $customer->orders()->count();
        $reviews = $customer->reviews()->with('product:id,name')->latest()->limit(20)->get();
    @endphp

    @include('admin.partials.page-header', [
        'title' => $customer->name,
        'breadcrumbs' => [
            'Customers' => route('admin.customers.index'),
            $customer->name,
        ],
        'actions' => auth()->user()?->can('customers.update')
            ? '<form action="'.route('admin.customers.toggle-block', $customer).'" method="POST" class="d-inline" data-confirm="'.($customer->is_active ? 'Block' : 'Unblock').' this customer?">'
                .csrf_field()
                .'<button type="submit" class="btn '.($customer->is_active ? 'btn-outline-warning' : 'btn-outline-success').'">'
                .($customer->is_active ? 'Block customer' : 'Unblock customer')
                .'</button></form>'
            : null,
    ])

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Profile</div>
                <div class="card-body">
                    <div class="fw-semibold fs-5">{{ $customer->name }}</div>
                    <div class="text-muted">{{ $customer->email }}</div>
                    <div class="text-muted">{{ $customer->phone ?? '—' }}</div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Status</span>
                        <span class="badge bg-{{ $customer->is_active ? 'success' : 'danger' }}">{{ $customer->is_active ? 'Active' : 'Blocked' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2"><span>Orders</span><span>{{ $ordersCount }}</span></div>
                    <div class="d-flex justify-content-between mt-2"><span>Lifetime spend</span><span>{{ format_money($lifetimeSpend) }}</span></div>
                    <div class="d-flex justify-content-between mt-2"><span>Joined</span><span class="small text-muted">{{ $customer->created_at?->format('d M Y') }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card table-card h-100">
                <div class="card-header">Order history</div>
                <div class="card-body p-0">
                    @if ($customer->orders->isEmpty())
                        @include('admin.partials.empty-state', ['icon' => 'bi-bag', 'title' => 'No orders', 'message' => 'This customer has not placed any orders yet.'])
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customer->orders as $order)
                                        <tr>
                                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                            <td>{{ format_money($order->total_amount) }}</td>
                                            <td>{{ ucfirst($order->order_status) }}</td>
                                            <td>{{ ucfirst($order->payment_status) }}</td>
                                            <td class="small text-muted">{{ $order->created_at?->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Addresses</div>
                <div class="card-body">
                    @forelse ($customer->addresses as $address)
                        <div class="@if(!$loop->last) mb-3 pb-3 border-bottom @endif">
                            <div class="fw-semibold">{{ $address->label ?? 'Address' }} @if($address->is_default)<span class="badge bg-teal-soft text-bg-secondary">Default</span>@endif</div>
                            <div class="small text-muted">{{ $address->address }}, {{ $address->city }}, {{ $address->state }} — {{ $address->pincode }}</div>
                        </div>
                    @empty
                        @include('admin.partials.empty-state', ['icon' => 'bi-geo-alt', 'title' => 'No addresses', 'message' => 'No saved addresses.'])
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Reviews</div>
                <div class="card-body">
                    @forelse ($reviews as $review)
                        <div class="@if(!$loop->last) mb-3 pb-3 border-bottom @endif">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ $review->product?->name ?? 'Product' }}</span>
                                <span class="small">{{ $review->rating }}/5</span>
                            </div>
                            <div class="small text-muted">{{ $review->comment }}</div>
                            <span class="badge bg-secondary">{{ $review->status }}</span>
                        </div>
                    @empty
                        @include('admin.partials.empty-state', ['icon' => 'bi-star', 'title' => 'No reviews', 'message' => 'This customer has not left reviews.'])
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
