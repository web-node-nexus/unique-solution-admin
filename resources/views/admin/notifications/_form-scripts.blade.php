<script>
(function () {
    const type = document.getElementById('link_type');
    const hidden = document.getElementById('link_value');
    const map = {
        category: document.getElementById('link_category'),
        brand: document.getElementById('link_brand'),
        product: document.getElementById('link_product'),
        sale: document.getElementById('link_sale'),
        coupon: document.getElementById('link_coupon'),
        url: document.getElementById('link_url'),
    };
    function sync() {
        document.querySelectorAll('.link-opt').forEach(el => el.classList.add('d-none'));
        const t = type.value;
        if (map[t]) map[t].classList.remove('d-none');
        if (t === 'none') hidden.value = '';
        else if (map[t]) hidden.value = map[t].value || '';
    }
    type?.addEventListener('change', sync);
    Object.values(map).forEach(el => {
        el?.addEventListener('change', sync);
        el?.addEventListener('input', sync);
    });
    sync();

    document.getElementById('image')?.addEventListener('change', function () {
        const preview = document.getElementById('notifImagePreview');
        const file = this.files?.[0];
        if (!file || !preview) return;
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.remove('d-none'); };
        reader.readAsDataURL(file);
    });
})();
</script>
