@extends('admin.layouts.app')

@php
    $lockedStatus = $lockedStatus ?? null;
    $pageTitle = $lockedStatus ? ucfirst($lockedStatus).' orders' : 'Orders';
@endphp

@section('title', $pageTitle)

@section('content')
    @include('admin.partials.page-header', [
        'title' => $pageTitle,
        'subtitle' => $lockedStatus
            ? 'Orders currently in '.ucfirst($lockedStatus).' status'
            : 'All shop orders — filter by status from the dropdown',
        'breadcrumbs' => $lockedStatus
            ? ['Orders' => route('admin.orders.index'), ucfirst($lockedStatus)]
            : ['Orders'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="orderFilters" class="row g-3 align-items-end">
                @if ($lockedStatus)
                    <input type="hidden" id="order_status" name="order_status" value="{{ $lockedStatus }}">
                @else
                    <div class="col-md-2">
                        <label class="form-label" for="order_status">Order status</label>
                        <select id="order_status" name="order_status" class="form-select">
                            <option value="">All</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label" for="payment_status">Payment status</label>
                    <select id="payment_status" name="payment_status" class="form-select">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from">From</label>
                    <input type="date" id="from" name="from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to">To</label>
                    <input type="date" id="to" name="to" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="customer">Customer</label>
                    <input type="text" id="customer" name="customer" class="form-control" placeholder="Name, email, phone">
                </div>
                <div class="col-md-2">
                    <button type="button" id="applyOrderFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetOrderFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ordersTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
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
    const lockedStatus = @json($lockedStatus);
    const table = $('#ordersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.orders.datatable') }}',
            data: function (d) {
                d.order_status = $('#order_status').val();
                d.payment_status = $('#payment_status').val();
                d.from = $('#from').val();
                d.to = $('#to').val();
                d.customer = $('#customer').val();
            }
        },
        columns: [
            { data: 'order_number', name: 'order_number' },
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'total_formatted', name: 'total_amount' },
            { data: 'status', name: 'order_status', orderable: false, searchable: false },
            { data: 'payment', name: 'payment_status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[5, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search order #…',
            emptyTable: lockedStatus
                ? ('No ' + lockedStatus + ' orders found')
                : 'No orders found',
        },
    });

    $('#applyOrderFilters').on('click', function () { table.ajax.reload(); });
    $('#resetOrderFilters').on('click', function () {
        if (!lockedStatus) {
            $('#order_status').val('');
        }
        $('#payment_status, #from, #to, #customer').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
