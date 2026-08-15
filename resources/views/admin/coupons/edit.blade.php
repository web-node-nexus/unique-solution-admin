@extends('admin.layouts.app')

@section('title', 'Edit Coupon')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Edit coupon',
        'breadcrumbs' => [
            'Coupons' => route('admin.coupons.index'),
            'Edit',
        ],
    ])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="code" value="{{ old('code', $coupon->code) }}" class="form-control text-uppercase @error('code') is-invalid @enderror" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="title">Display title</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $coupon->title) }}" class="form-control @error('title') is-invalid @enderror">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $coupon->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="discount_type">Discount type <span class="text-danger">*</span></label>
                        <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror" required>
                            <option value="percentage" @selected(old('discount_type', $coupon->discount_type) === 'percentage')>Percentage</option>
                            <option value="fixed" @selected(old('discount_type', $coupon->discount_type) === 'fixed')>Fixed amount</option>
                        </select>
                        @error('discount_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="discount_value">Discount value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="discount_value" id="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" class="form-control @error('discount_value') is-invalid @enderror" required>
                        @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="min_order_value">Min order value</label>
                        <input type="number" step="0.01" min="0" name="min_order_value" id="min_order_value" value="{{ old('min_order_value', $coupon->min_order_value) }}" class="form-control @error('min_order_value') is-invalid @enderror">
                        @error('min_order_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="max_uses">Max uses</label>
                        <input type="number" min="1" name="max_uses" id="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}" class="form-control @error('max_uses') is-invalid @enderror">
                        @error('max_uses')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="start_date">Start date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $coupon->start_date?->format('Y-m-d')) }}" class="form-control @error('start_date') is-invalid @enderror">
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="expiry_date">Expiry date</label>
                        <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', $coupon->expiry_date?->format('Y-m-d')) }}" class="form-control @error('expiry_date') is-invalid @enderror">
                        @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="image">Coupon image / banner</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @php $imgUrl = $coupon->image ? asset('storage/'.$coupon->image) : ''; @endphp
                        <img id="couponImagePreview" src="{{ $imgUrl }}" alt="" class="rounded border mt-2 {{ $imgUrl ? '' : 'd-none' }}" style="max-height:140px;object-fit:cover;">
                        @if ($coupon->image)
                            <div class="form-check mt-2">
                                <input type="hidden" name="remove_image" value="0">
                                <input type="checkbox" class="form-check-input" name="remove_image" id="remove_image" value="1">
                                <label class="form-check-label text-danger" for="remove_image">Remove current image</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-3">
                            <input type="hidden" name="status" value="0">
                            <input type="checkbox" name="status" id="status" value="1" class="form-check-input" @checked(old('status', $coupon->status))>
                            <label class="form-check-label" for="status">Active</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <a href="{{ route('admin.coupons.usage', $coupon) }}" class="btn btn-outline-secondary">Usage report</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('image')?.addEventListener('change', function () {
    const preview = document.getElementById('couponImagePreview');
    const file = this.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.classList.remove('d-none'); };
    reader.readAsDataURL(file);
});
</script>
@endpush
