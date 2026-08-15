@php
    $categories = $categories ?? collect();
    $brands = $brands ?? collect();
@endphp

<div class="row g-2 align-items-end product-filters">
    <div class="col-6 col-md-3 col-xl-2">
        <label for="filterCategory" class="form-label small text-muted mb-1">Category</label>
        <select id="filterCategory" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label for="filterBrand" class="form-label small text-muted mb-1">Brand</label>
        <select id="filterBrand" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label for="filterStatus" class="form-label small text-muted mb-1">Status</label>
        <select id="filterStatus" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="draft">Draft</option>
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label for="filterStock" class="form-label small text-muted mb-1">Stock</label>
        <select id="filterStock" class="form-select form-select-sm">
            <option value="">All levels</option>
            <option value="in_stock">In stock</option>
            <option value="low_stock">Low stock</option>
            <option value="out_of_stock">Out of stock</option>
        </select>
    </div>
    <div class="col-12 col-xl-auto ms-xl-auto">
        <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
        </button>
    </div>
</div>
