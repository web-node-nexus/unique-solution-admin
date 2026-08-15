@extends('admin.layouts.app')

@section('title', 'Coupons')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Coupons',
        'breadcrumbs' => ['Coupons'],
        'actions' => auth()->user()?->can('coupons.create')
            ? '<a href="'.route('admin.coupons.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add coupon</a>'
            : null,
    ])

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="couponsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Usage</th>
                            <th>Min order</th>
                            <th>Expiry</th>
                            <th>Status</th>
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
    $('#couponsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.coupons.datatable') }}',
        columns: [
            { data: 'code', name: 'code' },
            { data: 'discount_label', name: 'discount_label', orderable: false },
            { data: 'usage', name: 'usage', orderable: false },
            { data: 'min_order_value', name: 'min_order_value' },
            { data: 'expiry_date', name: 'expiry_date' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'asc']],
    });
});
</script>
@endpush
