@extends('admin.layouts.app')

@section('title', 'Sales')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Offers & Promotions',
        'subtitle' => 'Offers shown in the app. Active items still wait for their start date and time.',
        'breadcrumbs' => ['Marketing' => null, 'Sales'],
        'actions' => auth()->user()?->can('sales.create')
            ? '<a href="'.route('admin.sales.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Offer</a>'
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
