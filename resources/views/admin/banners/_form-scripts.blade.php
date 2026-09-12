<script>
(function () {
    const typeSelect = document.getElementById('link_type');
    const hidden = document.getElementById('link_value');
    const wraps = {
        none: document.getElementById('linkNoneHint'),
        category: document.getElementById('linkCategoryWrap'),
        brand: document.getElementById('linkBrandWrap'),
        product: document.getElementById('linkProductWrap'),
    };
    const fields = {
        category: document.getElementById('link_category'),
        brand: document.getElementById('link_brand'),
        product: document.getElementById('link_product'),
    };

    function sync() {
        const type = typeSelect.value;
        Object.entries(wraps).forEach(([key, el]) => {
            if (el) el.classList.toggle('d-none', key !== type);
        });
        if (type === 'none') {
            hidden.value = '';
        } else if (fields[type]) {
            hidden.value = fields[type].value || '';
        }
    }

    typeSelect?.addEventListener('change', sync);
    Object.values(fields).forEach((el) => el?.addEventListener('change', sync));
    sync();

    document.getElementById('image')?.addEventListener('change', function (e) {
        const file = e.target.files?.[0];
        const preview = document.getElementById('imagePreview');
        if (!file || !preview) return;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    });
})();
</script>
