/**
 * Unique Solution — publishing toggles, activation checks, mobile preview
 */
(function (window, $) {
    'use strict';

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatMoney(value) {
        const n = Number(value);
        if (!Number.isFinite(n) || n <= 0) {
            return '';
        }
        return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    function firstFileUrl(selector) {
        const input = document.querySelector(selector);
        const file = input?.files?.[0];
        if (file) {
            return URL.createObjectURL(file);
        }
        return null;
    }

    function existingImage(selector) {
        const img = document.querySelector(selector);
        if (!img || img.classList.contains('d-none')) {
            return null;
        }
        return img.getAttribute('src') || null;
    }

    function firstGalleryImage() {
        const fileUrl = firstFileUrl('input[name="images[]"], input[name="images"]');
        if (fileUrl) {
            return fileUrl;
        }
        const card = document.querySelector('.miu-card img, .gallery-card img, [data-existing-image] img');
        return card?.getAttribute('src') || null;
    }

    function setAlert(panel, messages) {
        const box = panel?.querySelector('[data-activation-alert]');
        if (!box) {
            return;
        }
        if (!messages || messages.length === 0) {
            box.classList.add('d-none');
            box.innerHTML = '';
            return;
        }
        box.classList.remove('d-none');
        box.innerHTML = '<strong>Cannot activate yet</strong><ul class="mb-0 mt-2">'
            + messages.map((m) => '<li>' + escapeHtml(m) + '</li>').join('')
            + '</ul>';
    }

    function collectIssues(type) {
        const issues = [];
        const val = (id) => (document.getElementById(id)?.value || '').trim();

        if (type === 'product') {
            if (!val('name')) issues.push('Add a product name before activating.');
            if (!val('category_id')) issues.push('Select a category before activating.');
            if (!val('base_price') || Number(val('base_price')) <= 0) issues.push('Enter a valid MRP / price before activating.');
            const hasGallery = !!(firstGalleryImage() || existingImage('#imagePreview'));
            const hasVariantPhoto = Array.from(
                document.querySelectorAll('#variantsBody input[type="file"][name*="[image]"]')
            ).some((input) => input.files && input.files.length > 0);
            if (!hasGallery && !hasVariantPhoto) {
                issues.push('Upload at least one product photo (gallery or variant) before activating.');
            }
        }
        if (type === 'category') {
            if (!val('name')) issues.push('Add a category name before activating.');
            if (!firstFileUrl('#image') && !existingImage('#imagePreview')) {
                issues.push('Upload a category image before activating.');
            }
        }
        if (type === 'brand') {
            if (!val('name')) issues.push('Add a brand name before activating.');
            if (!document.querySelector('input[name="category_ids[]"]:checked')) {
                issues.push('Map this brand to at least one category before activating.');
            }
            if (!firstFileUrl('#logo') && !existingImage('#logoPreview')) {
                issues.push('Upload a brand logo before activating.');
            }
        }
        if (type === 'banner') {
            if (!firstFileUrl('#image') && !existingImage('#imagePreview')) {
                issues.push('Upload a banner image before activating.');
            }
        }
        if (type === 'sale') {
            if (!val('title')) issues.push('Add an offer title before activating.');
            if (!firstFileUrl('#image') && !existingImage('#saleImagePreview')) {
                issues.push('Upload an offer image before activating.');
            }
        }
        if (type === 'coupon') {
            if (!val('code')) issues.push('Add a coupon code before activating.');
            if (!val('discount_value') || Number(val('discount_value')) <= 0) {
                issues.push('Enter a discount value before activating.');
            }
        }
        if (type === 'announcement') {
            if (!val('title')) issues.push('Add an announcement title before activating.');
            if (!val('body')) issues.push('Add announcement message text before activating.');
        }

        return issues;
    }

    function formType(form) {
        return form?.getAttribute('data-publish-form') || '';
    }

    function syncFormToggle(toggle) {
        const panel = toggle.closest('[data-publish-panel]');
        const on = panel?.querySelector('.js-publish-on');
        const off = panel?.querySelector('.js-publish-off');
        on?.classList.toggle('d-none', !toggle.checked);
        off?.classList.toggle('d-none', toggle.checked);
        if (!toggle.checked) {
            setAlert(panel, []);
        }
    }

    function collectPreview(type) {
        const val = (id) => (document.getElementById(id)?.value || '').trim();
        const shop = 'Unique Solution';

        if (type === 'product') {
            const mrp = formatMoney(val('base_price'));
            const sale = formatMoney(val('sale_price'));
            return {
                eyebrow: 'Product',
                image: firstGalleryImage(),
                title: val('name') || 'Product name',
                subtitle: sale && mrp && sale !== mrp ? sale + '  ·  ' + mrp : (mrp || 'Price pending'),
                body: (document.getElementById('description')?.value || '').replace(/<[^>]+>/g, ' ').slice(0, 140),
            };
        }
        if (type === 'category') {
            return {
                eyebrow: 'Category',
                image: firstFileUrl('#image') || existingImage('#imagePreview'),
                title: val('name') || 'Category name',
                subtitle: 'Shop this category',
                tile: true,
            };
        }
        if (type === 'brand') {
            return {
                eyebrow: 'Brand',
                image: firstFileUrl('#logo') || existingImage('#logoPreview'),
                title: val('name') || 'Brand name',
                subtitle: 'Brand store',
                tile: true,
            };
        }
        if (type === 'banner') {
            return {
                eyebrow: 'Home banner',
                image: firstFileUrl('#image') || existingImage('#imagePreview'),
                title: val('title') || shop,
                subtitle: val('subtitle') || 'Carousel slide',
                hero: true,
            };
        }
        if (type === 'sale') {
            return {
                eyebrow: 'Offer',
                image: firstFileUrl('#image') || existingImage('#saleImagePreview'),
                title: val('title') || 'Offer title',
                subtitle: val('subtitle') || val('description') || 'Limited-time offer',
                hero: true,
            };
        }
        if (type === 'coupon') {
            return {
                eyebrow: 'Coupon',
                title: val('title') || val('code') || 'Coupon',
                subtitle: val('code'),
                body: val('description') || 'Apply this coupon at checkout.',
                image: firstFileUrl('#image') || existingImage('#couponImagePreview'),
            };
        }
        if (type === 'announcement') {
            return {
                eyebrow: 'Announcement',
                image: firstFileUrl('#image') || existingImage('#notifImagePreview'),
                title: val('title') || 'Announcement',
                body: val('body') || '',
            };
        }

        return { title: shop, subtitle: 'Preview' };
    }

    function renderPreview(data) {
        const img = data.image
            ? '<img src="' + escapeHtml(data.image) + '" alt="">'
            : '<div class="phone-image-fallback"><i class="bi bi-image"></i></div>';

        if (data.hero) {
            return '<div class="phone-home-preview">'
                + '<div class="phone-topbar"><span>Unique Solution</span><i class="bi bi-bell"></i></div>'
                + '<div class="phone-hero">' + img
                + '<div class="phone-hero-copy"><div class="phone-kicker">' + escapeHtml(data.eyebrow || '') + '</div>'
                + '<div class="phone-hero-title">' + escapeHtml(data.title) + '</div>'
                + '<div class="phone-hero-sub">' + escapeHtml(data.subtitle || '') + '</div></div></div>'
                + '<div class="phone-chips"><span></span><span></span><span></span></div>'
                + '</div>';
        }

        if (data.tile) {
            return '<div class="phone-home-preview">'
                + '<div class="phone-topbar"><span>Unique Solution</span></div>'
                + '<div class="phone-kicker">' + escapeHtml(data.eyebrow || '') + '</div>'
                + '<div class="phone-tile">' + img + '<div><strong>' + escapeHtml(data.title) + '</strong><div>' + escapeHtml(data.subtitle || '') + '</div></div></div>'
                + '</div>';
        }

        return '<div class="phone-home-preview">'
            + '<div class="phone-topbar"><span>Unique Solution</span></div>'
            + '<div class="phone-kicker">' + escapeHtml(data.eyebrow || '') + '</div>'
            + '<div class="phone-card">' + img
            + '<div class="phone-card-body"><strong>' + escapeHtml(data.title) + '</strong>'
            + (data.subtitle ? '<div class="phone-price">' + escapeHtml(data.subtitle) + '</div>' : '')
            + (data.body ? '<p>' + escapeHtml(data.body) + '</p>' : '')
            + '</div></div></div>';
    }

    function bindListToggles() {
        document.addEventListener('change', function (event) {
            const input = event.target;
            if (!input.classList?.contains('js-publish-toggle')) {
                return;
            }

            const url = input.getAttribute('data-url');
            if (!url) {
                return;
            }

            const previous = !input.checked;
            const cell = input.closest('.publish-toggle-cell');
            const label = cell?.querySelector('.publish-toggle-label');
            input.disabled = true;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ active: input.checked ? 1 : 0 }),
            })
                .then(async (res) => {
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        input.checked = previous;
                        const errors = json.errors || [json.message || 'Could not update status.'];
                        if (window.toastr) {
                            toastr.error(Array.isArray(errors) ? errors[0] : errors);
                        }
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cannot activate yet',
                                html: '<ul class="text-start mb-0">' + (Array.isArray(errors) ? errors : [errors]).map((m) => '<li>' + escapeHtml(m) + '</li>').join('') + '</ul>',
                                confirmButtonColor: '#dc2626',
                            });
                        }
                        return;
                    }
                    if (label) {
                        label.textContent = input.checked ? 'Active' : 'Deactive';
                        label.classList.toggle('is-on', input.checked);
                    }
                    if (window.toastr) {
                        toastr.success(json.message || (input.checked ? 'Activated' : 'Deactivated'));
                    }
                    if (window.jQuery && $.fn.DataTable) {
                        const table = $(input).closest('table').DataTable();
                        if (table) {
                            table.ajax.reload(null, false);
                        }
                    }
                })
                .catch(() => {
                    input.checked = previous;
                    if (window.toastr) {
                        toastr.error('Could not update status.');
                    }
                })
                .finally(() => {
                    input.disabled = false;
                });
        });
    }

    function bindFormToggles() {
        document.querySelectorAll('.js-form-status-toggle').forEach((toggle) => {
            syncFormToggle(toggle);
            toggle.addEventListener('change', function () {
                const form = toggle.closest('form');
                const type = formType(form);
                const panel = toggle.closest('[data-publish-panel]');
                if (toggle.checked && type) {
                    const issues = collectIssues(type);
                    if (issues.length) {
                        toggle.checked = false;
                        setAlert(panel, issues);
                        if (window.toastr) {
                            toastr.error(issues[0]);
                        }
                        syncFormToggle(toggle);
                        return;
                    }
                }
                syncFormToggle(toggle);
            });
        });

        document.querySelectorAll('form[data-publish-form]').forEach((form) => {
            form.addEventListener('submit', function (event) {
                const toggle = form.querySelector('.js-form-status-toggle');
                if (!toggle?.checked) {
                    return;
                }
                const issues = collectIssues(formType(form));
                if (!issues.length) {
                    return;
                }
                event.preventDefault();
                setAlert(toggle.closest('[data-publish-panel]'), issues);
                toggle.checked = false;
                syncFormToggle(toggle);
                if (window.toastr) {
                    toastr.error(issues[0]);
                }
            });
        });
    }

    function bindPreview() {
        const modalEl = document.getElementById('appPreviewModal');
        if (!modalEl) {
            return;
        }
        const modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;
        const screen = document.getElementById('appPreviewScreen');

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.js-app-preview');
            if (!btn) {
                return;
            }
            const type = btn.getAttribute('data-preview');
            const data = collectPreview(type);
            if (screen) {
                screen.innerHTML = renderPreview(data);
            }
            modal?.show();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindListToggles();
        bindFormToggles();
        bindPreview();
    });
})(window, window.jQuery);
