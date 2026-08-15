@extends('admin.layouts.app')

@section('title', 'Categories')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Categories',
        'breadcrumbs' => ['Catalog' => null, 'Categories'],
        'actions' => auth()->user()?->can('categories.create')
            ? '<a href="'.route('admin.categories.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Category</a>'
            : null,
    ])

    <div class="card table-card">
        <div class="card-header">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="filterStatus" class="form-label small text-muted mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-auto">
                    <p class="small text-muted mb-0 mt-2 mt-md-0">
                        <i class="bi bi-grip-vertical me-1"></i>Drag rows, or use ↑↓ buttons — app shows this order live
                    </p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="categoriesTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Products</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .reorder-handle {
        cursor: grab;
        display: inline-flex;
        padding: 0.25rem;
        font-size: 1.1rem;
    }
    .reorder-handle:active { cursor: grabbing; }
    #categoriesTable tbody tr.sortable-ghost {
        opacity: 0.45;
        background: #f0fdfa;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let sortableInstance = null;
    const canReorder = @json(auth()->user()?->can('categories.update') ?? false);

    const table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        order: [[4, 'asc']],
        rowId: 'DT_RowId',
        ajax: {
            url: @json(route('admin.categories.datatable')),
            data: function (d) {
                d.status = $('#filterStatus').val();
            },
        },
        columns: [
            { data: 'reorder', name: 'reorder', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'parent_name', name: 'parent_name', orderable: false },
            { data: 'products_count', name: 'products_count', searchable: false },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            lengthMenu: '_MENU_ per page',
            emptyTable: 'No categories found',
        },
        drawCallback: function () {
            if (!canReorder || typeof Sortable === 'undefined') {
                return;
            }

            const tbody = document.querySelector('#categoriesTable tbody');
            if (!tbody) {
                return;
            }

            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }

            sortableInstance = Sortable.create(tbody, {
                handle: '.reorder-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    const order = [];
                    tbody.querySelectorAll('tr[data-id]').forEach(function (row) {
                        const id = parseInt(row.getAttribute('data-id'), 10);
                        if (id) {
                            order.push(id);
                        }
                    });

                    if (!order.length) {
                        return;
                    }

                    $.ajax({
                        url: @json(route('admin.categories.reorder')),
                        method: 'POST',
                        data: { order: order },
                        success: function (res) {
                            toastr.success((res && res.message) || 'Categories reordered');
                            table.ajax.reload(null, false);
                        },
                        error: function (xhr) {
                            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Reorder failed');
                            table.ajax.reload(null, false);
                        },
                    });
                },
            });
        },
    });

    $('#filterStatus').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-move-category', function () {
        const btn = $(this);
        const id = btn.data('id');
        const direction = btn.data('direction');
        if (!id || !direction) return;

        btn.prop('disabled', true);
        $.ajax({
            url: @json(url('/admin/categories')) + '/' + id + '/move',
            method: 'POST',
            data: {
                direction: direction,
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (res) {
                toastr.success((res && res.message) || 'Category moved');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not move category');
                btn.prop('disabled', false);
            },
        });
    });
});
</script>
@endpush
