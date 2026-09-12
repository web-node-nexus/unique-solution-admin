@extends('admin.layouts.app')

@php
    $lockedTab = $lockedTab ?? null;
    $activeKey = $lockedTab ?? 'all';
    $pageTitle = $lockedTab
        ? \App\Services\OrderService::tabLabel($lockedTab).' orders'
        : 'Orders';
    $count = (int) ($tabCounts[$activeKey] ?? 0);
@endphp

@section('title', $pageTitle)

@section('content')
    <div class="orders-board card">
        <div class="orders-board-head">
            <div class="orders-board-title-wrap">
                <div class="orders-board-title-row">
                    <span class="orders-dot" style="background: {{ $activeMeta['color'] }}"></span>
                    <h1 class="orders-board-title">{{ $activeMeta['title'] }}</h1>
                    <span class="orders-count-pill" style="--pill: {{ $activeMeta['color'] }}">
                        {{ $count }} {{ $activeMeta['badge'] }}
                    </span>
                </div>
                <p class="orders-board-sub">{{ $activeMeta['subtitle'] }}</p>
            </div>

            <nav class="orders-status-tabs" aria-label="Order status">
                @foreach ($boardTabs as $tab)
                    @php
                        $isActive = $lockedTab === $tab;
                        $tabColor = $boardMeta[$tab]['color'] ?? '#64748b';
                    @endphp
                    <a href="{{ route('admin.orders.status', $tab) }}"
                       class="orders-tab {{ $isActive ? 'is-active' : '' }}"
                       style="--tab: {{ $tabColor }}">
                        {{ \App\Services\OrderService::tabLabel($tab) }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="orders-board-tools">
            <form id="orderFilters" class="orders-filters row g-2 align-items-end">
                <input type="hidden" id="board_tab" name="board_tab" value="{{ $lockedTab }}">
                <div class="col-md-2">
                    <label class="form-label" for="payment_status">Payment</label>
                    <select id="payment_status" name="payment_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from">From</label>
                    <input type="date" id="from" name="from" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to">To</label>
                    <input type="date" id="to" name="to" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="customer">Search</label>
                    <input type="text" id="customer" name="customer" class="form-control form-control-sm" placeholder="Order #, name, phone, address">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="button" id="applyOrderFilters" class="btn btn-sm btn-outline-primary">Apply</button>
                    <button type="button" id="resetOrderFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    @if ($lockedTab)
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-link text-muted ms-auto">All orders</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="orders-table-wrap">
            <div class="table-responsive">
                <table id="ordersTable" class="table orders-table w-100">
                    <thead>
                        <tr>
                            <th>ऑर्डर ID व तारीख</th>
                            <th>कस्टमर विवरण</th>
                            <th>डिलीवरी पता</th>
                            <th>प्रोडक्ट व कुल कीमत</th>
                            <th>पेमेंट मोड</th>
                            <th class="text-uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const lockedTab = @json($lockedTab);

    const table = $('#ordersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.orders.datatable') }}',
            data: function (d) {
                d.board_tab = $('#board_tab').val();
                d.payment_status = $('#payment_status').val();
                d.from = $('#from').val();
                d.to = $('#to').val();
                d.customer = $('#customer').val();
            }
        },
        columns: [
            { data: 'order_block', name: 'order_number', orderable: true },
            { data: 'customer_block', name: 'user_id', orderable: false, searchable: false },
            { data: 'address_block', name: 'shipping_address', orderable: false },
            { data: 'product_block', name: 'total_amount', orderable: true, searchable: false },
            { data: 'payment_block', name: 'payment_status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'rtip',
        language: {
            emptyTable: lockedTab
                ? ('No ' + lockedTab + ' orders found')
                : 'No orders found',
            processing: 'Loading orders…',
            paginate: {
                previous: '‹',
                next: '›',
            },
        },
        drawCallback: function () {
            // keep board chrome tight
        },
    });

    $('#applyOrderFilters').on('click', function () { table.ajax.reload(); });
    $('#resetOrderFilters').on('click', function () {
        $('#payment_status, #from, #to, #customer').val('');
        table.ajax.reload();
    });

    $('#ordersTable').on('click', '.ord-btn-confirm, .ord-btn-cancel', function () {
        const btn = this;
        const url = btn.getAttribute('data-status-url');
        const status = btn.getAttribute('data-status');
        if (!url || !status) return;

        const isCancel = status === 'cancelled';
        const title = isCancel ? 'ऑर्डर कैंसिल करें?' : 'ऑर्डर कन्फर्म करें?';
        const text = isCancel
            ? 'यह ऑर्डर cancelled हो जाएगा।'
            : 'यह ऑर्डर confirmed हो जाएगा।';
        const confirmText = isCancel ? 'हाँ, कैंसिल' : 'हाँ, कन्फर्म';

        Swal.fire({
            title,
            text,
            icon: isCancel ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'वापस',
            confirmButtonColor: isCancel ? '#dc2626' : '#16a34a',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            btn.disabled = true;
            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    order_status: status,
                    remarks: null,
                }),
            })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || data.success === false) {
                        throw new Error(data.message || 'Update failed');
                    }
                    toastr.success(data.message || 'Order updated');
                    window.location.reload();
                })
                .catch((err) => {
                    toastr.error(err.message || 'Could not update order');
                    btn.disabled = false;
                });
        });
    });
});
</script>
@endpush
