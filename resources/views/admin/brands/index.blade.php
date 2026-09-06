@extends('admin.layouts.app')

@section('title', 'Brands')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Brand Management',
        'breadcrumbs' => ['Catalog' => null, 'Brands'],
        'actions' => auth()->user()?->can('brands.create')
            ? '<a href="'.route('admin.brands.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Brand</a>'
            : null,
    ])

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="brandsTable">
                    <thead>
                        <tr>
                            <th style="width: 72px;">Logo</th>
                            <th>Brand Name</th>
                            <th>Categories</th>
                            <th>Products</th>
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
    $('#brandsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [[1, 'asc']],
        ajax: @json(route('admin.brands.datatable')),
        columns: [
            { data: 'logo_html', name: 'logo_html', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'products_count', name: 'products_count', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search brand name…',
            lengthMenu: '_MENU_ per page',
            emptyTable: 'No brands found',
        },
    });
});
</script>
@endpush
