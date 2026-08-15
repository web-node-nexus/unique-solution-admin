@extends('admin.layouts.app')

@section('title', 'Inventory')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Inventory',
        'breadcrumbs' => ['Inventory'],
        'actions' => auth()->user()?->can('inventory.create')
            ? '<a href="'.route('admin.inventory.adjust').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Adjust stock</a>'
            : null,
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="inventoryFilters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="category_id">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="brand_id">Brand</label>
                    <select name="brand_id" id="brand_id" class="form-select">
                        <option value="">All brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="stock_filter">Stock status</label>
                    <select name="stock_filter" id="stock_filter" class="form-select">
                        <option value="">All</option>
                        <option value="low">Low stock</option>
                        <option value="out">Out of stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="applyInventoryFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetInventoryFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="inventoryTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>Stock</th>
                            <th>Threshold</th>
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
    const table = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.inventory.datatable') }}',
            data: function (d) {
                d.category_id = $('#category_id').val();
                d.brand_id = $('#brand_id').val();
                d.stock_filter = $('#stock_filter').val();
            }
        },
        columns: [
            { data: 'sku', name: 'sku' },
            { data: 'product_name', name: 'product_name', orderable: false },
            { data: 'variant_label', name: 'variant_label', orderable: false },
            { data: 'stock_quantity', name: 'stock_quantity' },
            { data: 'low_stock_threshold', name: 'low_stock_threshold' },
            { data: 'stock_badge', name: 'stock_badge', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'asc']],
        language: {
            search: '',
            searchPlaceholder: 'Search SKU…',
            emptyTable: 'No inventory records found',
        },
    });

    $('#applyInventoryFilters').on('click', function () { table.ajax.reload(); });
    $('#resetInventoryFilters').on('click', function () {
        $('#category_id, #brand_id, #stock_filter').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
