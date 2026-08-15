@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Dashboard',
        'breadcrumbs' => ['Overview'],
    ])

    @php
        $stats = $stats ?? [];
        $salesTrend = $salesTrend ?? ['labels' => [], 'data' => []];
        $categorySales = $categorySales ?? ['labels' => [], 'data' => []];
        $topProducts = $topProducts ?? collect();
        $recentOrders = $recentOrders ?? collect();
        $lowStockVariants = $lowStockVariants ?? collect();
        $orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    @endphp

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
                    <div>
                        <div class="stat-label">Revenue</div>
                        <div class="stat-value">{{ format_money($stats['total_revenue'] ?? 0) }}</div>
                        <div class="stat-meta">Today {{ format_money($stats['revenue_today'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon info"><i class="bi bi-bag-check"></i></div>
                    <div>
                        <div class="stat-label">Orders</div>
                        <div class="stat-value">{{ number_format($stats['total_orders'] ?? 0) }}</div>
                        <div class="stat-meta">{{ $stats['orders_today'] ?? 0 }} today · {{ $stats['pending_orders'] ?? 0 }} pending</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon success"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="stat-label">Products</div>
                        <div class="stat-value">{{ number_format($stats['active_products'] ?? 0) }}</div>
                        <div class="stat-meta">{{ $stats['total_products'] ?? 0 }} total catalog</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon warning"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div class="stat-label">Low stock</div>
                        <div class="stat-value">{{ number_format($stats['low_stock_variants'] ?? $lowStockVariants->count()) }}</div>
                        <div class="stat-meta">{{ $stats['out_of_stock_variants'] ?? 0 }} out of stock</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Sales trend (30 days)</span>
                    <span class="small text-muted">{{ setting('currency_symbol', '₹') }}</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="salesTrend" aria-label="Sales trend chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Category sales</div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="categorySales" aria-label="Category sales chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Top products</div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="topProducts" aria-label="Top products chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card table-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent orders</span>
                    @if (Route::has('admin.orders.index'))
                        <a href="{{ route('admin.orders.index') }}" class="small fw-semibold">View all</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($recentOrders->isEmpty())
                        @include('admin.partials.empty-state', [
                            'icon' => 'bi-bag',
                            'title' => 'No orders yet',
                            'message' => 'New orders will appear here once customers start buying.',
                        ])
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td>
                                                @if (Route::has('admin.orders.show'))
                                                    <a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold">
                                                        {{ $order->order_number }}
                                                    </a>
                                                @else
                                                    <span class="fw-semibold">{{ $order->order_number }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $order->user?->name ?? 'Guest' }}</div>
                                                <div class="small text-muted">{{ $order->user?->email }}</div>
                                            </td>
                                            <td>{{ format_money($order->total_amount) }}</td>
                                            <td style="min-width: 140px;">
                                                @can('orders.update')
                                                    <select class="form-select form-select-sm order-status-select"
                                                            data-order-id="{{ $order->id }}"
                                                            @if (Route::has('admin.orders.update-status'))
                                                                data-url="{{ route('admin.orders.update-status', $order) }}"
                                                            @endif>
                                                        @foreach ($orderStatuses as $status)
                                                            <option value="{{ $status }}" @selected($order->order_status === $status)>
                                                                {{ ucfirst($status) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="badge-status {{ $order->order_status }}">{{ $order->order_status }}</span>
                                                @endcan
                                            </td>
                                            <td class="text-muted small">{{ $order->created_at?->format('d M Y, h:i A') }}</td>
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

    {{-- Low stock widget --}}
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Low stock alerts</span>
                    @if (Route::has('admin.inventory.index'))
                        <a href="{{ route('admin.inventory.index') }}" class="small fw-semibold">Inventory</a>
                    @endif
                </div>
                <div class="card-body">
                    @if ($lowStockVariants->isEmpty())
                        @include('admin.partials.empty-state', [
                            'icon' => 'bi-check2-circle',
                            'title' => 'Stock looks healthy',
                            'message' => 'No variants are below their low-stock threshold.',
                        ])
                    @else
                        @foreach ($lowStockVariants as $variant)
                            <div class="low-stock-item">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">
                                        {{ $variant->product?->name ?? 'Product' }}
                                    </div>
                                    <div class="small text-muted">
                                        SKU: {{ $variant->sku ?? '—' }}
                                        @if (! empty($variant->name))
                                            · {{ $variant->name }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge rounded-pill {{ ($variant->stock_quantity ?? 0) <= 0 ? 'text-bg-danger' : 'text-bg-warning' }}">
                                    {{ $variant->stock_quantity ?? 0 }} left
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Quick snapshot</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="stat-label">Customers</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['total_customers'] ?? 0) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-label">This month</div>
                            <div class="fs-4 fw-bold">{{ format_money($stats['revenue_this_month'] ?? 0) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-label">Processing</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['processing_orders'] ?? 0) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-label">Pending refunds</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['pending_refunds'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const accent = '#0d9488';
    const accentSoft = 'rgba(13, 148, 136, 0.15)';
    const palette = ['#0d9488', '#0284c7', '#d97706', '#059669', '#64748b', '#dc2626', '#7c3aed', '#0891b2'];

    const salesTrend = @json($salesTrend);
    const categorySales = @json($categorySales);
    const topProductsRaw = @json($topProducts);
    const topLabels = (topProductsRaw || []).map(function (p) {
        return p.product_name || p.name || 'Product';
    });
    const topData = (topProductsRaw || []).map(function (p) {
        return Number(p.units_sold || p.quantity || 0);
    });

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 10,
                cornerRadius: 8,
                titleFont: { family: 'Plus Jakarta Sans', weight: '600' },
                bodyFont: { family: 'Plus Jakarta Sans' },
            },
        },
    };

    if (document.getElementById('salesTrend') && typeof Chart !== 'undefined') {
        new Chart(document.getElementById('salesTrend'), {
            type: 'line',
            data: {
                labels: salesTrend.labels || [],
                datasets: [{
                    label: 'Sales',
                    data: salesTrend.data || [],
                    borderColor: accent,
                    backgroundColor: accentSoft,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                }],
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 8, font: { family: 'Plus Jakarta Sans', size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } },
                    },
                },
            },
        });
    }

    if (document.getElementById('categorySales') && typeof Chart !== 'undefined') {
        new Chart(document.getElementById('categorySales'), {
            type: 'doughnut',
            data: {
                labels: categorySales.labels || [],
                datasets: [{
                    data: categorySales.data || [],
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                ...chartDefaults,
                cutout: '68%',
                plugins: {
                    ...chartDefaults.plugins,
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 12,
                            font: { family: 'Plus Jakarta Sans', size: 11 },
                        },
                    },
                },
            },
        });
    }

    if (document.getElementById('topProducts') && typeof Chart !== 'undefined') {
        new Chart(document.getElementById('topProducts'), {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: 'Units sold',
                    data: topData,
                    backgroundColor: accent,
                    borderRadius: 6,
                    maxBarThickness: 28,
                }],
            },
            options: {
                ...chartDefaults,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } },
                    },
                },
            },
        });
    }

    // Inline status update (optional — requires admin.orders.update-status)
    document.querySelectorAll('.order-status-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const url = select.getAttribute('data-url');
            if (!url || typeof jQuery === 'undefined') {
                return;
            }

            jQuery.ajax({
                url: url,
                method: 'PATCH',
                data: { order_status: select.value },
                success: function (res) {
                    toastr.success((res && res.message) || 'Order status updated');
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not update status');
                },
            });
        });
    });
});
</script>
@endpush
