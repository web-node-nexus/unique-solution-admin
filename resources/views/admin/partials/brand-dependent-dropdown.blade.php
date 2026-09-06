@php
    $brandOptions = ($brands ?? collect())->map(fn ($brand) => [
        'id' => (int) $brand->id,
        'name' => $brand->name,
    ])->values();
@endphp
<script>
window.US_BRANDS = @json($brandOptions);
window.US_CATEGORY_BRANDS = @json($categoryBrandMap ?? new \stdClass());

function filterBrandsByCategory(categoryId, options) {
    const brandSelect = document.getElementById('brand_id');
    if (!brandSelect) return;

    options = options || {};
    const previous = String(options.keepBrandId || brandSelect.value || '');
    const mapped = ((window.US_CATEGORY_BRANDS || {})[String(categoryId)] || []).map(Number);
    const allowed = new Set(mapped);
    if (options.forceKeep && previous) {
        allowed.add(Number(previous));
    }

    brandSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    if (!categoryId) {
        placeholder.textContent = 'Select category first';
        brandSelect.disabled = true;
        brandSelect.appendChild(placeholder);
        return;
    }

    brandSelect.disabled = false;
    placeholder.textContent = allowed.size ? '— Select brand —' : 'No brands mapped to this category';
    brandSelect.appendChild(placeholder);

    (window.US_BRANDS || []).forEach(function (brand) {
        if (!allowed.has(Number(brand.id))) return;
        const opt = document.createElement('option');
        opt.value = String(brand.id);
        opt.textContent = brand.name;
        if (previous && String(brand.id) === previous && allowed.has(Number(brand.id))) {
            opt.selected = true;
        }
        brandSelect.appendChild(opt);
    });
}
</script>
