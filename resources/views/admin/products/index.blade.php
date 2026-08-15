@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
    @php
        $headerActions = '';
        if (auth()->user()?->can('products.create')) {
            $headerActions .= '<a href="'.route('admin.products.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Product</a>';
            $headerActions .= '<button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-1"></i>Import</button>';
            $headerActions .= '<a href="'.route('admin.products.import-template').'" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-down me-1"></i>Template</a>';
        }
        if (auth()->user()?->can('products.export')) {
            $headerActions .= '<a href="'.route('admin.products.export').'" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export</a>';
        }
    @endphp

    @include('admin.partials.page-header', [
        'title' => 'Products',
        'breadcrumbs' => ['Catalog' => null, 'Products'],
        'actions' => $headerActions !== '' ? $headerActions : null,
    ])

    <div class="card table-card">
        <div class="card-header">
            @include('admin.products.partials.filters')
        </div>

        @can('products.update')
            <div class="px-3 pt-3">
                <div class="d-flex flex-wrap align-items-center gap-2 bulk-actions-bar">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="selectAllProducts">
                        <label class="form-check-label small" for="selectAllProducts">Select all</label>
                    </div>
                    <span class="small text-muted" id="bulkSelectedCount">0 selected</span>
                    <div class="vr d-none d-md-block"></div>
                    <button type="button" class="btn btn-sm btn-outline-success btn-bulk" data-action="activate" disabled>Activate</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-bulk" data-action="deactivate" disabled>Deactivate</button>
                    @can('products.delete')
                        <button type="button" class="btn btn-sm btn-outline-danger btn-bulk" data-action="delete" disabled>Delete</button>
                    @endcan
                </div>
            </div>
        @endcan

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="productsTable">
                    <thead>
                        <tr>
                            <th style="width: 36px;"></th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Variants</th>
                            <th>Status</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Import modal --}}
    @can('products.create')
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="importModalLabel">Import products</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="importFile" class="form-label">Excel / CSV file</label>
                        <input type="file" name="file" id="importFile" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <p class="small text-muted mt-2 mb-0">
                            Use the template for the correct column layout.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- Quick edit modal --}}
    @can('products.update')
        <div class="modal fade" id="quickEditModal" tabindex="-1" aria-labelledby="quickEditModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="quickEditForm" class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="quickEditModalLabel">Quick edit</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="qeUrl">
                        <div class="mb-3">
                            <label for="qeName" class="form-label">Name</label>
                            <input type="text" id="qeName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="qeStatus" class="form-label">Status</label>
                            <select id="qeStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="qeFeatured">
                            <label class="form-check-label" for="qeFeatured">Featured</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="qeSubmitBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#productsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [[1, 'asc']],
        ajax: {
            url: @json(route('admin.products.datatable')),
            data: function (d) {
                d.category_id = $('#filterCategory').val();
                d.brand_id = $('#filterBrand').val();
                d.status = $('#filterStatus').val();
                d.stock_level = $('#filterStock').val();
            },
        },
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'brand_name', name: 'brand_name', orderable: false, searchable: false },
            { data: 'base_price', name: 'base_price' },
            { data: 'variants_count', name: 'variants_count', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            lengthMenu: '_MENU_ per page',
            emptyTable: 'No products found',
        },
        drawCallback: function () {
            updateBulkState();
            $('#selectAllProducts').prop('checked', false);
        },
    });

    $('#filterCategory, #filterBrand, #filterStatus, #filterStock').on('change', function () {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        $('#filterCategory, #filterBrand, #filterStatus, #filterStock').val('');
        table.ajax.reload();
    });

    function selectedIds() {
        return $('.product-row-check:checked').map(function () {
            return parseInt(this.value, 10);
        }).get();
    }

    function updateBulkState() {
        const ids = selectedIds();
        $('#bulkSelectedCount').text(ids.length + ' selected');
        $('.btn-bulk').prop('disabled', ids.length === 0);
    }

    $(document).on('change', '.product-row-check', updateBulkState);

    $('#selectAllProducts').on('change', function () {
        $('.product-row-check').prop('checked', this.checked);
        updateBulkState();
    });

    $('.btn-bulk').on('click', function () {
        const action = $(this).data('action');
        const ids = selectedIds();
        if (!ids.length) {
            return;
        }

        const messages = {
            activate: 'Activate selected products?',
            deactivate: 'Deactivate selected products?',
            delete: 'Delete selected products? This cannot be undone.',
        };

        const run = function () {
            $.ajax({
                url: @json(route('admin.products.bulk-action')),
                method: 'POST',
                data: { action: action, ids: ids },
                success: function (res) {
                    toastr.success((res && res.message) || 'Bulk action completed');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Bulk action failed');
                },
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm',
                text: messages[action] || 'Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, continue',
                reverseButtons: true,
                focusCancel: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    run();
                }
            });
        } else if (window.confirm(messages[action] || 'Continue?')) {
            run();
        }
    });

    const quickModalEl = document.getElementById('quickEditModal');
    const quickModal = quickModalEl ? new bootstrap.Modal(quickModalEl) : null;

    $(document).on('click', '.btn-quick-edit', function () {
        const btn = $(this);
        $('#qeUrl').val(btn.data('url'));
        $('#qeName').val(btn.data('name'));
        $('#qeStatus').val(btn.data('status'));
        $('#qeFeatured').prop('checked', String(btn.data('featured')) === '1');
        quickModal?.show();
    });

    $('#quickEditForm').on('submit', function (e) {
        e.preventDefault();
        const url = $('#qeUrl').val();
        const btn = document.getElementById('qeSubmitBtn');
        window.setButtonLoading(btn, true);

        $.ajax({
            url: url,
            method: 'PATCH',
            data: {
                name: $('#qeName').val(),
                status: $('#qeStatus').val(),
                is_featured: $('#qeFeatured').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                toastr.success((res && res.message) || 'Product updated');
                quickModal?.hide();
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not update');
            },
            complete: function () {
                window.setButtonLoading(btn, false);
            },
        });
    });
});
</script>
@endpush
