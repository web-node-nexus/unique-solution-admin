/**
 * Unique Solution — Admin UI helpers
 */
(function (window, $) {
    'use strict';

    const Admin = {
        init() {
            this.setupAjaxCsrf();
            this.initSidebar();
            this.initNestedMenus();
            this.initConfirmDeletes();
            this.initTooltips();
            this.initHtmlComposers();
            this.setupToastr();
        },

        setupAjaxCsrf() {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            if (!token || !$) {
                return;
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token,
                },
            });
        },

        setupToastr() {
            if (typeof toastr === 'undefined') {
                return;
            }

            toastr.options = {
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                preventDuplicates: true,
                positionClass: 'toast-top-right',
                timeOut: 4000,
                extendedTimeOut: 1500,
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut',
                showDuration: 250,
                hideDuration: 250,
            };
        },

        initSidebar() {
            const body = document.body;
            const toggleBtn = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            const storageKey = 'us_admin_sidebar_collapsed';

            if (window.innerWidth > 768 && localStorage.getItem(storageKey) === '1') {
                body.classList.add('sidebar-collapsed');
            }

            const closeMobile = () => {
                body.classList.remove('sidebar-open-mobile');
            };

            toggleBtn?.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    body.classList.toggle('sidebar-open-mobile');
                    return;
                }

                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(
                    storageKey,
                    body.classList.contains('sidebar-collapsed') ? '1' : '0'
                );
            });

            backdrop?.addEventListener('click', closeMobile);

            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) {
                    closeMobile();
                }
            });
        },

        initNestedMenus() {
            document.querySelectorAll('[data-menu-toggle]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();

                    const targetId = trigger.getAttribute('data-menu-toggle');
                    const submenu = document.getElementById(targetId);
                    if (!submenu) {
                        return;
                    }

                    const isOpen = submenu.classList.contains('open');

                    // Close sibling submenus in same group
                    const parent = trigger.closest('.sidebar-nav');
                    parent?.querySelectorAll('.submenu.open').forEach((openMenu) => {
                        if (openMenu !== submenu) {
                            openMenu.classList.remove('open');
                            const siblingTrigger = parent.querySelector(
                                `[data-menu-toggle="${openMenu.id}"]`
                            );
                            siblingTrigger?.setAttribute('aria-expanded', 'false');
                        }
                    });

                    submenu.classList.toggle('open', !isOpen);
                    trigger.setAttribute('aria-expanded', String(!isOpen));
                });
            });

            // Keep parent open when a child route is active
            document.querySelectorAll('.submenu').forEach((submenu) => {
                if (submenu.querySelector('.nav-link.active')) {
                    submenu.classList.add('open');
                    const trigger = document.querySelector(
                        `[data-menu-toggle="${submenu.id}"]`
                    );
                    trigger?.setAttribute('aria-expanded', 'true');
                    trigger?.classList.add('active');
                }
            });
        },

        initConfirmDeletes() {
            document.addEventListener('click', (event) => {
                const el = event.target.closest('[data-confirm]');
                if (!el) {
                    return;
                }

                event.preventDefault();

                const message =
                    el.getAttribute('data-confirm') ||
                    'Are you sure you want to delete this item?';
                const title = el.getAttribute('data-confirm-title') || 'Confirm';
                const confirmText = el.getAttribute('data-confirm-button') || 'Yes, delete';
                const cancelText = el.getAttribute('data-cancel-button') || 'Cancel';

                const runAction = () => {
                    if (el.tagName === 'A' && el.getAttribute('href')) {
                        window.location.href = el.getAttribute('href');
                        return;
                    }

                    const form = el.closest('form');
                    if (form) {
                        form.submit();
                        return;
                    }

                    if (typeof el.onclick === 'function') {
                        el.onclick();
                    }
                };

                if (typeof Swal === 'undefined') {
                    if (window.confirm(message)) {
                        runAction();
                    }
                    return;
                }

                Swal.fire({
                    title,
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0d9488',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    reverseButtons: true,
                    focusCancel: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        runAction();
                    }
                });
            });
        },

        initTooltips() {
            if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
                return;
            }

            document
                .querySelectorAll('[data-bs-toggle="tooltip"]')
                .forEach((el) => new bootstrap.Tooltip(el));
        },

        initHtmlComposers() {
            const previewCss = `
                html, body { margin: 0; padding: 0; background: #ffffff; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
                    font-size: 15px;
                    line-height: 1.65;
                    color: #0f172a;
                    padding: 12px 14px 20px;
                    word-wrap: break-word;
                }
                img, video, iframe { max-width: 100%; height: auto; }
                table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
                th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; vertical-align: top; }
                th { background: #f1f5f9; font-weight: 600; }
                h1, h2, h3, h4 { color: #0f172a; margin: 1em 0 0.4em; line-height: 1.3; }
                h1 { font-size: 1.35rem; }
                h2 { font-size: 1.15rem; }
                p { margin: 0.55em 0; }
                ul, ol { padding-left: 1.25rem; margin: 0.55em 0; }
                a { color: #0d9488; }
            `;

            const wrapHtml = (raw) => {
                const html = String(raw || '');
                const trimmed = html.trim();
                if (!trimmed) {
                    return `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${previewCss}</style></head>
                        <body><p style="color:#94a3b8;text-align:center;margin-top:2.5rem;">Paste HTML on the left to preview.</p></body></html>`;
                }
                if (/<html[\s>]/i.test(trimmed)) {
                    if (/<head[\s>]/i.test(trimmed)) {
                        return trimmed.replace(/<head([^>]*)>/i, `<head$1><meta charset="utf-8"><style>${previewCss}</style>`);
                    }
                    return trimmed.replace(/<html([^>]*)>/i, `<html$1><head><meta charset="utf-8"><style>${previewCss}</style></head>`);
                }
                return `<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>${previewCss}</style></head><body>${html}</body></html>`;
            };

            const paint = (root) => {
                const source = root.querySelector('[data-html-source]');
                const frame = root.querySelector('[data-html-frame]');
                if (!source || !frame) {
                    return;
                }
                const doc = frame.contentDocument;
                if (!doc) {
                    return;
                }
                doc.open();
                doc.write(wrapHtml(source.value));
                doc.close();
            };

            document.querySelectorAll('[data-html-composer]').forEach((root) => {
                if (root.dataset.bound === '1') {
                    return;
                }
                root.dataset.bound = '1';

                const source = root.querySelector('[data-html-source]');
                const preview = root.querySelector('[data-html-preview]');
                let timer = null;

                const schedule = () => {
                    if (preview?.hidden) {
                        return;
                    }
                    clearTimeout(timer);
                    timer = setTimeout(() => paint(root), 80);
                };

                source?.addEventListener('input', schedule);
                source?.addEventListener('paste', () => setTimeout(schedule, 0));

                root.querySelectorAll('[data-html-mode]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const mode = btn.getAttribute('data-html-mode');
                        root.querySelectorAll('[data-html-mode]').forEach((b) => {
                            b.classList.toggle('is-active', b === btn);
                        });
                        const showPreview = mode === 'preview';
                        root.classList.toggle('is-preview', showPreview);
                        if (preview) {
                            preview.hidden = !showPreview;
                        }
                        if (showPreview) {
                            paint(root);
                        }
                    });
                });
            });
        },

        /**
         * Toggle loading spinner state on a button.
         * @param {HTMLElement|jQuery|string} btn
         * @param {boolean} loading
         */
        setButtonLoading(btn, loading) {
            const el =
                typeof btn === 'string'
                    ? document.querySelector(btn)
                    : btn && btn.jquery
                      ? btn[0]
                      : btn;

            if (!el) {
                return;
            }

            if (loading) {
                if (!el.dataset.originalHtml) {
                    el.dataset.originalHtml = el.innerHTML;
                }
                el.classList.add('is-loading');
                el.setAttribute('disabled', 'disabled');
                el.setAttribute('aria-busy', 'true');
            } else {
                el.classList.remove('is-loading');
                el.removeAttribute('disabled');
                el.removeAttribute('aria-busy');
                if (el.dataset.originalHtml) {
                    el.innerHTML = el.dataset.originalHtml;
                    delete el.dataset.originalHtml;
                }
            }
        },
    };

    window.Admin = Admin;
    window.setButtonLoading = Admin.setButtonLoading.bind(Admin);

    document.addEventListener('DOMContentLoaded', () => Admin.init());
})(window, window.jQuery);
