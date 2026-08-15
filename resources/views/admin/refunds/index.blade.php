@extends('admin.layouts.app')

@section('title', 'Refunds')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Refunds',
        'breadcrumbs' => ['Refunds'],
        'actions' => '<a href="'.route('admin.refunds.export').'" class="btn btn-outline-primary"><i class="bi bi-download me-1"></i>Export</a>',
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="refundFilters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="applyRefundFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetRefundFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="refundsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#refundsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.refunds.datatable') }}',
            data: function (d) {
                d.status = $('#status').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'order_number', name: 'order_number', orderable: false },
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'amount_formatted', name: 'refund_amount' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
    });

    $('#applyRefundFilters').on('click', function () { table.ajax.reload(); });
    $('#resetRefundFilters').on('click', function () {
        $('#status').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
