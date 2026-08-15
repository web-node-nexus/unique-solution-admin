@extends('admin.layouts.app')

@section('title', 'Sales')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Sales & Promotions',
        'subtitle' => 'App pe dikhne wali sales — image, start & expiry date ke saath',
        'breadcrumbs' => ['Marketing' => null, 'Sales'],
        'actions' => auth()->user()?->can('sales.create')
            ? '<a href="'.route('admin.sales.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Sale</a>'
            : null,
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="salesTable">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>Window</th>
                            <th>Live</th>
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
$('#salesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: @json(route('admin.sales.datatable')),
    order: [[1, 'asc']],
    columns: [
        { data: 'preview', orderable: false, searchable: false },
        { data: 'title', name: 'title' },
        { data: 'window', orderable: false, searchable: false },
        { data: 'live', orderable: false, searchable: false },
        { data: 'status', orderable: false, searchable: false },
        { data: 'action', orderable: false, searchable: false },
    ],
});
</script>
@endpush
