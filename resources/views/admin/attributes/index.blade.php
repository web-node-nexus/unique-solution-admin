@extends('admin.layouts.app')

@section('title', 'Attributes')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Attributes',
        'breadcrumbs' => ['Catalog' => null, 'Attributes'],
        'actions' => auth()->user()?->can('attributes.create')
            ? '<a href="'.route('admin.attributes.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Attribute</a>'
            : null,
    ])

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="attributesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Values</th>
                            <th>Status</th>
                            <th style="width: 120px;">Actions</th>
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
    $('#attributesTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [[0, 'asc']],
        ajax: @json(route('admin.attributes.datatable')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'type', name: 'type' },
            { data: 'values_count', name: 'values_count', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            lengthMenu: '_MENU_ per page',
            emptyTable: 'No attributes found',
        },
    });
});
</script>
@endpush
