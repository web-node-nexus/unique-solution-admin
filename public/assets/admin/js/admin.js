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
