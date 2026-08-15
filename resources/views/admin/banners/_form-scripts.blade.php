<script>
(function () {
    const typeSelect = document.getElementById('link_type');
    const hidden = document.getElementById('link_value');
    const wraps = {
        none: document.getElementById('linkNoneHint'),
        category: document.getElementById('linkCategoryWrap'),
        product: document.getElementById('linkProductWrap'),
        url: document.getElementById('linkUrlWrap'),
    };
    const fields = {
        category: document.getElementById('link_category'),
        product: document.getElementById('link_product'),
        url: document.getElementById('link_url'),
    };

    function sync() {
        const type = typeSelect.value;
        Object.entries(wraps).forEach(([key, el]) => {
            if (el) el.classList.toggle('d-none', key !== type);
        });
        if (type === 'none') {
            hidden.value = '';
        } else if (type === 'category') {
            hidden.value = fields.category?.value || '';
        } else if (type === 'product') {
            hidden.value = fields.product?.value || '';
        } else if (type === 'url') {
            hidden.value = fields.url?.value || '';
        }
    }

    typeSelect?.addEventListener('change', sync);
    fields.category?.addEventListener('change', sync);
    fields.product?.addEventListener('change', sync);
    fields.url?.addEventListener('input', sync);
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
