@extends('admin.layouts.app')

@section('title', 'Payments')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Payments',
        'breadcrumbs' => ['Payments'],
        'actions' => '<a href="'.route('admin.payments.reconciliation').'" class="btn btn-outline-primary"><i class="bi bi-clipboard-data me-1"></i>Reconciliation</a>',
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="paymentFilters" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="payment_method">Method</label>
                    <select id="payment_method" name="payment_method" class="form-select">
                        <option value="">All</option>
                        <option value="cod">COD</option>
                        <option value="razorpay">Razorpay</option>
                        <option value="payu">PayU</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
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
                <div class="col-md-4">
                    <button type="button" id="applyPaymentFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetPaymentFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="paymentsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Method</th>
                            <th>Txn ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.payments.partials.status-modal')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#paymentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.payments.datatable') }}',
            data: function (d) {
                d.status = $('#status').val();
                d.payment_method = $('#payment_method').val();
                d.from = $('#from').val();
                d.to = $('#to').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'order_number', name: 'order_number', orderable: false },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'transaction_id', name: 'transaction_id', defaultContent: '—' },
            { data: 'amount_formatted', name: 'amount' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            emptyTable: 'No payments found',
        },
    });

    $('#applyPaymentFilters').on('click', function () { table.ajax.reload(); });
    $('#resetPaymentFilters').on('click', function () {
        $('#status, #payment_method, #from, #to').val('');
        table.ajax.reload();
    });

    const modalEl = document.getElementById('paymentStatusModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    $(document).on('click', '.btn-update-payment', function () {
        const id = $(this).data('id');
        $('#payment_update_form').attr('action', @json(url('admin/payments')).replace(/\/?$/, '') + '/' + id + '/status');
        modal?.show();
    });
});
</script>
@endpush
