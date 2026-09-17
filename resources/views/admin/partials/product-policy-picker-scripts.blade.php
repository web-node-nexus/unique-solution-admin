<script>
(function () {
    const root = document.querySelector('[data-policy-picker]');
    if (!root) return;

    const listEl = root.querySelector('[data-policy-picker-list]');
    const emptyEl = root.querySelector('[data-policy-picker-empty]');
    const noneEl = root.querySelector('[data-policy-picker-none]');
    const brandSelect = document.getElementById('brand_id');
    const baseUrl = root.getAttribute('data-warranty-url-base') || '';
    const palette = ['#EEF2FF', '#ECFDF5', '#FFF7ED', '#FDF2F8', '#EFF6FF', '#F0FDF4'];

    let selected = new Set((function () {
        try {
            return JSON.parse(root.getAttribute('data-selected') || '[]').map(Number);
        } catch (e) {
            return [];
        }
    })());
    let currentBrandId = '';
    let lastPolicies = [];

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showState(state) {
        emptyEl?.classList.toggle('d-none', state !== 'empty');
        noneEl?.classList.toggle('d-none', state !== 'none');
        listEl?.classList.toggle('d-none', state !== 'list');
    }

    function render(policies, selectAllMissing) {
        lastPolicies = policies || [];
        if (!currentBrandId) {
            listEl.innerHTML = '';
            showState('empty');
            return;
        }
        if (!lastPolicies.length) {
            listEl.innerHTML = '';
            selected = new Set();
            showState('none');
            return;
        }

        if (selectAllMissing) {
            lastPolicies.forEach(function (p) {
                if (!selected.has(Number(p.id))) {
                    selected.add(Number(p.id));
                }
            });
        }

        // Drop selections that are not in this brand
        const allowed = new Set(lastPolicies.map(function (p) { return Number(p.id); }));
        selected = new Set(Array.from(selected).filter(function (id) { return allowed.has(id); }));

        showState('list');
        listEl.innerHTML = lastPolicies.map(function (policy, index) {
            const id = Number(policy.id);
            const checked = selected.has(id);
            const tint = palette[index % palette.length];
            const icon = policy.icon_url
                ? '<img src="' + escapeHtml(policy.icon_url) + '" alt="">'
                : '<i class="bi bi-shield-check"></i>';
            const desc = (policy.description || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

            return (
                '<div class="policy-picker-card' + (checked ? '' : ' is-off') + '" style="--policy-tint:' + tint + '" data-policy-id="' + id + '">' +
                    '<label class="policy-picker-check">' +
                        '<input type="checkbox" name="brand_policy_ids[]" value="' + id + '" ' + (checked ? 'checked' : '') + '>' +
                        '<span>Show on product</span>' +
                    '</label>' +
                    '<div class="policy-picker-icon">' + icon + '</div>' +
                    '<div class="policy-picker-body">' +
                        '<div class="policy-picker-title">' + escapeHtml(policy.title || '') + '</div>' +
                        (desc ? '<div class="policy-picker-desc">' + escapeHtml(desc.slice(0, 90)) + '</div>' : '') +
                    '</div>' +
                    '<button type="button" class="btn btn-link btn-sm text-danger text-decoration-none policy-picker-remove" ' + (checked ? '' : 'hidden') + '>' +
                        'Remove' +
                    '</button>' +
                '</div>'
            );
        }).join('');
    }

    function loadBrand(brandId, opts) {
        opts = opts || {};
        currentBrandId = String(brandId || '');
        if (!currentBrandId) {
            selected = new Set();
            render([], false);
            return;
        }

        fetch(baseUrl + '/' + currentBrandId + '/warranty', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (json) {
                if (!json) {
                    render([], false);
                    return;
                }
                const policies = Array.isArray(json.policies) ? json.policies : [];
                if (opts.resetSelection) {
                    selected = new Set(policies.map(function (p) { return Number(p.id); }));
                }
                render(policies, !!opts.selectAllIfEmpty && selected.size === 0);
            })
            .catch(function () {
                render([], false);
            });
    }

    listEl?.addEventListener('change', function (event) {
        const input = event.target.closest('input[type="checkbox"][name="brand_policy_ids[]"]');
        if (!input) return;
        const id = Number(input.value);
        const card = input.closest('.policy-picker-card');
        if (input.checked) {
            selected.add(id);
            card?.classList.remove('is-off');
            card?.querySelector('.policy-picker-remove')?.removeAttribute('hidden');
        } else {
            selected.delete(id);
            card?.classList.add('is-off');
            card?.querySelector('.policy-picker-remove')?.setAttribute('hidden', 'hidden');
        }
    });

    listEl?.addEventListener('click', function (event) {
        const btn = event.target.closest('.policy-picker-remove');
        if (!btn) return;
        const card = btn.closest('.policy-picker-card');
        const input = card?.querySelector('input[type="checkbox"]');
        if (!input) return;
        input.checked = false;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    brandSelect?.addEventListener('change', function () {
        // Brand changed → load that brand’s policies and select all by default
        loadBrand(this.value, { resetSelection: true });
    });

    // Initial load (edit / old input)
    const initial = root.getAttribute('data-initial-brand') || brandSelect?.value || '';
    const autoSelect = root.getAttribute('data-auto-select') === '1';
    if (initial) {
        loadBrand(initial, {
            resetSelection: autoSelect && selected.size === 0,
            selectAllIfEmpty: autoSelect && selected.size === 0,
        });
    } else {
        showState('empty');
    }
})();
</script>
