@php
    $sale = $sale ?? null;
    $linkType = old('link_type', $sale->link_type ?? 'none');
    $linkValue = old('link_value', $sale->link_value ?? '');
    $notifyChecked = old('notify_users', $sale->notify_users ?? in_array($linkType, ['category', 'brand'], true));
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $sale->title ?? '') }}" required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="sort_order">Sort order</label>
        <input type="number" min="0" name="sort_order" id="sort_order" class="form-control"
               value="{{ old('sort_order', $sale->sort_order ?? 0) }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="subtitle">Subtitle</label>
        <input type="text" name="subtitle" id="subtitle" class="form-control"
               value="{{ old('subtitle', $sale->subtitle ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $sale->description ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="starts_at">Start date &amp; time</label>
        <input type="datetime-local" name="starts_at" id="starts_at" class="form-control @error('starts_at') is-invalid @enderror"
               value="{{ old('starts_at', optional($sale?->starts_at)->format('Y-m-d\TH:i')) }}">
        @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="ends_at">Expiry date &amp; time</label>
        <input type="datetime-local" name="ends_at" id="ends_at" class="form-control @error('ends_at') is-invalid @enderror"
               value="{{ old('ends_at', optional($sale?->ends_at)->format('Y-m-d\TH:i')) }}">
        @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="image">Sale banner image</label>
        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
               class="form-control @error('image') is-invalid @enderror">
        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @php $imgUrl = $sale?->image ? asset('storage/'.$sale->image) : ''; @endphp
        <img id="saleImagePreview" src="{{ $imgUrl }}" class="rounded border mt-2 {{ $imgUrl ? '' : 'd-none' }} w-100" style="max-height:160px;object-fit:cover;" alt="">
        @if ($sale?->image)
            <div class="form-check mt-2">
                <input type="hidden" name="remove_image" value="0">
                <input type="checkbox" class="form-check-input" name="remove_image" id="remove_image" value="1">
                <label class="form-check-label text-danger" for="remove_image">Remove current image</label>
            </div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label" for="link_type">Tap opens / targets</label>
        <select name="link_type" id="link_type" class="form-select">
            <option value="none" @selected($linkType === 'none')>No link</option>
            <option value="category" @selected($linkType === 'category')>Category</option>
            <option value="brand" @selected($linkType === 'brand')>Brand</option>
            <option value="product" @selected($linkType === 'product')>Product</option>
            <option value="coupon" @selected($linkType === 'coupon')>Coupon</option>
            <option value="url" @selected($linkType === 'url')>URL</option>
        </select>
        <div class="mt-2">
            <select id="link_category" class="form-select link-opt {{ $linkType === 'category' ? '' : 'd-none' }}">
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($linkType === 'category' && (string)$linkValue === (string)$category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select id="link_brand" class="form-select link-opt {{ $linkType === 'brand' ? '' : 'd-none' }}">
                <option value="">Select brand</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected($linkType === 'brand' && (string)$linkValue === (string)$brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <select id="link_product" class="form-select link-opt {{ $linkType === 'product' ? '' : 'd-none' }}">
                <option value="">Select product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected($linkType === 'product' && (string)$linkValue === (string)$product->id)>{{ $product->name }}</option>
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
        <div class="form-check form-switch mt-3">
            <input type="hidden" name="status" value="0">
            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" @checked(old('status', $sale->status ?? true))>
            <label class="form-check-label" for="status">Active</label>
        </div>
        <div class="border rounded-3 p-3 mt-3 bg-light">
            <div class="form-check form-switch">
                <input type="hidden" name="notify_users" value="0">
                <input class="form-check-input" type="checkbox" name="notify_users" id="notify_users" value="1" @checked($notifyChecked)>
                <label class="form-check-label fw-semibold" for="notify_users">Notify all users</label>
            </div>
            <div class="form-text mb-0">
                Sends in-app message + FCM push (with sale image) to every registered app device.
                Recommended when targeting a <strong>category</strong> or <strong>brand</strong>.
            </div>
            @if ($sale?->notification_sent_at)
                <div class="small text-muted mt-2">Last notified: {{ $sale->notification_sent_at->format('d M Y H:i') }}</div>
                <div class="form-check mt-2">
                    <input type="hidden" name="send_notification_now" value="0">
                    <input class="form-check-input" type="checkbox" name="send_notification_now" id="send_notification_now" value="1">
                    <label class="form-check-label" for="send_notification_now">Send notification again on save</label>
                </div>
            @else
                <input type="hidden" name="send_notification_now" value="1">
            @endif
        </div>
    </div>
</div>
