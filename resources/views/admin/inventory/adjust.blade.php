@extends('admin.layouts.app')

@section('title', 'Adjust Inventory')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Adjust inventory',
        'breadcrumbs' => [
            'Inventory' => route('admin.inventory.index'),
            'Adjust',
        ],
    ])

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Stock adjustment</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.inventory.adjust.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="variant_id">Variant <span class="text-danger">*</span></label>
                            <select name="variant_id" id="variant_id" class="form-select @error('variant_id') is-invalid @enderror" required>
                                <option value="">Select variant</option>
                                @foreach ($variants as $v)
                                    <option value="{{ $v->id }}"
                                        @selected(old('variant_id', $variant?->id) == $v->id)
                                        data-threshold="{{ $v->low_stock_threshold }}"
                                        data-stock="{{ $v->stock_quantity }}">
                                        {{ $v->sku }} — {{ $v->product?->name ?? 'Product' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('variant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($variant)
                            <div class="alert alert-light border mb-3">
                                <div class="fw-semibold">{{ $variant->display_name }}</div>
                                <div class="small text-muted">SKU: {{ $variant->sku }} · Current stock: {{ $variant->stock_quantity }} · Threshold: {{ $variant->low_stock_threshold }}</div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="stock_in" @selected(old('type') === 'stock_in')>Stock in</option>
                                <option value="stock_out" @selected(old('type') === 'stock_out')>Stock out</option>
                                <option value="adjustment" @selected(old('type') === 'adjustment')>Adjustment (set absolute)</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="quantity">Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="quantity" id="quantity" value="{{ old('quantity') }}"
                                   class="form-control @error('quantity') is-invalid @enderror" required>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="reason">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save adjustment</button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Low-stock threshold override</div>
                <div class="card-body">
                    @if ($variant && auth()->user()?->can('inventory.update'))
                        <form method="POST" action="{{ route('admin.inventory.threshold', $variant) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label" for="low_stock_threshold">Threshold</label>
                                <input type="number" min="0" name="low_stock_threshold" id="low_stock_threshold"
                                       value="{{ old('low_stock_threshold', $variant->low_stock_threshold) }}"
                                       class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Update threshold</button>
                        </form>
                    @else
                        <p class="text-muted mb-0 small">Select a variant from inventory (Adjust link) to override its low-stock threshold.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
