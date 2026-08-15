@extends('admin.layouts.app')

@section('title', 'Coupon Usage')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Usage — '.$coupon->code,
        'breadcrumbs' => [
            'Coupons' => route('admin.coupons.index'),
            'Usage',
        ],
        'actions' => auth()->user()?->can('coupons.update')
            ? '<a href="'.route('admin.coupons.edit', $coupon).'" class="btn btn-outline-primary">Edit coupon</a>'
            : null,
    ])

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card"><div class="card-body"><div class="stat-label">Code</div><div class="stat-value fs-4">{{ $coupon->code }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card"><div class="card-body"><div class="stat-label">Used</div><div class="stat-value">{{ number_format($coupon->used_count ?? 0) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card"><div class="card-body"><div class="stat-label">Max uses</div><div class="stat-value">{{ $coupon->max_uses ?? '∞' }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card"><div class="card-body"><div class="stat-label">Remaining</div><div class="stat-value">{{ $remaining === null ? '∞' : number_format($remaining) }}</div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="stat-label">Discount</div><div>{{ $coupon->discount_type === 'percentage' ? rtrim(rtrim(number_format((float)$coupon->discount_value, 2), '0'), '.').'%' : format_money($coupon->discount_value) }}</div></div>
                <div class="col-md-4"><div class="stat-label">Min order</div><div>{{ $coupon->min_order_value !== null ? format_money($coupon->min_order_value) : '—' }}</div></div>
                <div class="col-md-4"><div class="stat-label">Expiry</div><div>{{ $coupon->expiry_date?->format('d M Y') ?? 'No expiry' }}</div></div>
            </div>
        </div>
    </div>
@endsection
