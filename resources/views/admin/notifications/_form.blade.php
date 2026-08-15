@php
    $notification = $notification ?? null;
    $linkType = old('link_type', $notification->link_type ?? 'none');
    $linkValue = old('link_value', $notification->link_value ?? '');
@endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="mb-3">
            <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $notification->title ?? '') }}" required maxlength="255">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="body">Message <span class="text-danger">*</span></label>
            <textarea name="body" id="body" rows="5" class="form-control @error('body') is-invalid @enderror" required>{{ old('body', $notification->body ?? '') }}</textarea>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="image">Announcement image</label>
            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                   class="form-control @error('image') is-invalid @enderror">
            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Shown in the app inbox and as the FCM push image.</div>
            @php $imgUrl = $notification?->image ? asset('storage/'.$notification->image) : ''; @endphp
            <img id="notifImagePreview" src="{{ $imgUrl }}" alt=""
                 class="rounded border mt-2 {{ $imgUrl ? '' : 'd-none' }}" style="max-height:160px;object-fit:cover;">
            @if ($notification?->image)
                <div class="form-check mt-2">
                    <input type="hidden" name="remove_image" value="0">
                    <input type="checkbox" class="form-check-input" name="remove_image" id="remove_image" value="1">
                    <label class="form-check-label text-danger" for="remove_image">Remove current image</label>
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label" for="audience">Audience</label>
            <select name="audience" id="audience" class="form-select">
                <option value="all" @selected(old('audience', $notification->audience ?? 'all') === 'all')>All app users</option>
                <option value="customers" @selected(old('audience', $notification->audience ?? '') === 'customers')>Customers only</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="link_type">Tap opens</label>
            <select name="link_type" id="link_type" class="form-select">
                <option value="none" @selected($linkType === 'none')>No link</option>
                <option value="category" @selected($linkType === 'category')>Category</option>
                <option value="brand" @selected($linkType === 'brand')>Brand</option>
                <option value="product" @selected($linkType === 'product')>Product</option>
                <option value="sale" @selected($linkType === 'sale')>Sale</option>
                <option value="coupon" @selected($linkType === 'coupon')>Coupon</option>
                <option value="url" @selected($linkType === 'url')>URL</option>
            </select>
        </div>
        <div class="mb-3">
            <select id="link_category" class="form-select link-opt {{ $linkType === 'category' ? '' : 'd-none' }}">
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($linkType === 'category' && (string)$linkValue === (string)$category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select id="link_brand" class="form-select link-opt {{ $linkType === 'brand' ? '' : 'd-none' }}">
                <option value="">Select brand</option>
                @foreach (($brands ?? []) as $brand)
                    <option value="{{ $brand->id }}" @selected($linkType === 'brand' && (string)$linkValue === (string)$brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <select id="link_product" class="form-select link-opt {{ $linkType === 'product' ? '' : 'd-none' }}">
                <option value="">Select product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected($linkType === 'product' && (string)$linkValue === (string)$product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
            <select id="link_sale" class="form-select link-opt {{ $linkType === 'sale' ? '' : 'd-none' }}">
                <option value="">Select sale</option>
                @foreach ($sales as $saleItem)
                    <option value="{{ $saleItem->id }}" @selected($linkType === 'sale' && (string)$linkValue === (string)$saleItem->id)>{{ $saleItem->title }}</option>
                @endforeach
            </select>
            <select id="link_coupon" class="form-select link-opt {{ $linkType === 'coupon' ? '' : 'd-none' }}">
                <option value="">Select coupon</option>
                @foreach ($coupons as $coupon)
                    <option value="{{ $coupon->id }}" @selected($linkType === 'coupon' && (string)$linkValue === (string)$coupon->id)>{{ $coupon->code }}</option>
                @endforeach
            </select>
            <input type="url" id="link_url" class="form-control link-opt {{ $linkType === 'url' ? '' : 'd-none' }}"
                   placeholder="https://..." value="{{ $linkType === 'url' ? $linkValue : '' }}">
            <input type="hidden" name="link_value" id="link_value" value="{{ $linkValue }}">
        </div>
        <div class="alert alert-info small mb-0">
            <i class="bi bi-broadcast me-1"></i>
            <strong>Announce</strong> sends an in-app notification plus FCM push to every registered device.
        </div>
    </div>
</div>
