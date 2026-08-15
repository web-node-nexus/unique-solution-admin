@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Customers',
        'breadcrumbs' => ['Customers'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="customerFilters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="is_active">Status</label>
                    <select id="is_active" class="form-select">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Blocked</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="applyCustomerFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetCustomerFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="customersTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Lifetime spend</th>
                            <th>Last order</th>
                            <th>Status</th>
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
    const table = $('#customersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.customers.datatable') }}',
            data: function (d) {
                d.is_active = $('#is_active').val();
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone', defaultContent: '—' },
            { data: 'orders_count', name: 'orders_count' },
            { data: 'lifetime_spend_formatted', name: 'lifetime_spend', orderable: false, searchable: false },
            { data: 'last_order', name: 'last_order_at', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'asc']],
        language: {
            search: '',
            searchPlaceholder: 'Search customers…',
            emptyTable: 'No customers found',
        },
    });

    $('#applyCustomerFilters').on('click', function () { table.ajax.reload(); });
    $('#resetCustomerFilters').on('click', function () {
        $('#is_active').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
