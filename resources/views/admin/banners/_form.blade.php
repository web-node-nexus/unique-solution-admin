@php
    $banner = $banner ?? null;
    $linkType = old('link_type', $banner->link_type ?? 'none');
    if ($linkType === 'url') {
        $linkType = 'none';
    }
    $linkValue = old('link_value', $banner->link_value ?? '');
    if ($linkType === 'none') {
        $linkValue = '';
    }
@endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $banner->title ?? '') }}" maxlength="255">
            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Optional. Image alone is enough to save a banner.</div>
        </div>

        <div class="mb-3">
            <label for="subtitle" class="form-label">Subtitle</label>
            <input type="text" name="subtitle" id="subtitle" class="form-control @error('subtitle') is-invalid @enderror"
                   value="{{ old('subtitle', $banner->subtitle ?? '') }}" maxlength="255">
            @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Optional line under the title in the app carousel.</div>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Banner image @if(!$banner)<span class="text-danger">*</span>@endif</label>
            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                   class="form-control @error('image') is-invalid @enderror" {{ $banner ? '' : 'required' }}>
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Recommended <strong>1920×1080</strong> (full HD, 16:9). App me edge-to-edge dikhega.</div>
            <div class="mt-2">
                <img id="imagePreview" src="{{ $banner?->image_url }}" alt=""
                     class="rounded border {{ $banner?->image_url ? '' : 'd-none' }}"
                     style="max-width:100%;max-height:180px;object-fit:cover;">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="link_type" class="form-label">Tap opens</label>
                <select name="link_type" id="link_type" class="form-select @error('link_type') is-invalid @enderror">
                    <option value="none" @selected($linkType === 'none')>No link</option>
                    <option value="category" @selected($linkType === 'category')>Category</option>
                    <option value="brand" @selected($linkType === 'brand')>Brand</option>
                    <option value="product" @selected($linkType === 'product')>Product</option>
                </select>
                @error('link_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-8">
                <label class="form-label">Link target</label>
                <div id="linkCategoryWrap" class="{{ $linkType === 'category' ? '' : 'd-none' }}">
                    <select id="link_category" class="form-select">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($linkType === 'category' && (string)$linkValue === (string)$category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="linkBrandWrap" class="{{ $linkType === 'brand' ? '' : 'd-none' }}">
                    <select id="link_brand" class="form-select">
                        <option value="">Select brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected($linkType === 'brand' && (string)$linkValue === (string)$brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="linkProductWrap" class="{{ $linkType === 'product' ? '' : 'd-none' }}">
                    <select id="link_product" class="form-select">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected($linkType === 'product' && (string)$linkValue === (string)$product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="linkNoneHint" class="form-text {{ $linkType === 'none' ? '' : 'd-none' }}">Banner will not navigate when tapped.</div>
                <input type="hidden" name="link_value" id="link_value" value="{{ $linkValue }}">
                @error('link_value') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mb-3">
            <label for="sort_order" class="form-label">Carousel position</label>
            <input type="number" name="sort_order" id="sort_order" min="0"
                   class="form-control @error('sort_order') is-invalid @enderror"
                   value="{{ old('sort_order', $nextSort ?? 0) }}">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Lower = earlier in carousel. Drag on list page anytime.</div>
        </div>

        <div class="mb-3 form-check form-switch">
            <input type="hidden" name="status" value="0">
            <input class="form-check-input" type="checkbox" role="switch" name="status" id="status" value="1"
                   @checked(filter_var(old('status', $banner?->status ?? false), FILTER_VALIDATE_BOOLEAN))>
            <label class="form-check-label" for="status">Active on app</label>
        </div>

        <div class="mb-3">
            <label for="starts_at" class="form-label">Starts at (optional)</label>
            <input type="datetime-local" name="starts_at" id="starts_at"
                   class="form-control @error('starts_at') is-invalid @enderror"
                   value="{{ old('starts_at', optional($banner?->starts_at)->format('Y-m-d\TH:i')) }}">
            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="ends_at" class="form-label">Ends at (optional)</label>
            <input type="datetime-local" name="ends_at" id="ends_at"
                   class="form-control @error('ends_at') is-invalid @enderror"
                   value="{{ old('ends_at', optional($banner?->ends_at)->format('Y-m-d\TH:i')) }}">
            @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
