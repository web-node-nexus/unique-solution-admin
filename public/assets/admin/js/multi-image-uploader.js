/**
 * Multi-image picker: preview, remove, and admin-controlled sort order.
 * Gallery order is posted as gallery_order[] = existing:{id} | new:{index}
 *
 * With data-upload-url on the root, each photo is uploaded in the background as soon as it
 * is picked and only a signed token (gallery_uploads[]) is posted with the form — so any
 * number of photos can be added without hitting PHP's per-request upload limits.
 * Position 1 is always the main thumbnail.
 */
(function (window, document) {
    'use strict';

    function bytesLabel(n) {
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function notify(type, message) {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            window.alert(message);
        }
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /* ---------- Shared background upload queue ---------- */
    const StagedUpload = (function () {
        const MAX_PARALLEL = 3;
        const queue = [];
        let active = 0;
        let pending = 0;

        function pump() {
            while (active < MAX_PARALLEL && queue.length) {
                const job = queue.shift();
                active += 1;
                send(job).finally(function () {
                    active -= 1;
                    pending -= 1;
                    pump();
                });
            }
        }

        function send(job) {
            return new Promise(function (resolve) {
                const fd = new FormData();
                fd.append('image', job.file);
                fd.append('folder', job.folder || 'products');
                const xhr = new XMLHttpRequest();
                xhr.open('POST', job.url);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable && job.onProgress) job.onProgress(e.loaded / e.total);
                };
                xhr.onload = function () {
                    let body = null;
                    try { body = JSON.parse(xhr.responseText); } catch (err) { body = null; }
                    if (xhr.status >= 200 && xhr.status < 300 && body && body.token) {
                        job.resolve(body);
                    } else {
                        const msg = (body && (body.message || (body.errors && Object.values(body.errors)[0]?.[0])))
                            || ('Upload failed (' + xhr.status + ')');
                        job.reject(new Error(msg));
                    }
                    resolve();
                };
                xhr.onerror = function () {
                    job.reject(new Error('Network error while uploading ' + job.file.name));
                    resolve();
                };
                xhr.send(fd);
            });
        }

        return {
            upload: function (url, file, folder, onProgress) {
                pending += 1;
                return new Promise(function (resolve, reject) {
                    queue.push({ url: url, file: file, folder: folder, onProgress: onProgress, resolve: resolve, reject: reject });
                    pump();
                });
            },
            pendingCount: function () {
                return pending;
            },
        };
    })();

    window.UniqueStagedUpload = StagedUpload;

    function validateImageFile(file) {
        const maxBytes = Number(document.body?.dataset?.imageMaxBytes || 5 * 1024 * 1024);
        const maxMb = Number(document.body?.dataset?.imageMaxMb || 5);
        if (!file.type || !file.type.startsWith('image/')) return false;
        if (file.size > maxBytes) {
            notify('error', 'Image too large. Maximum allowed is ' + maxMb + ' MB per image. (' + file.name + ')');
            return false;
        }
        return true;
    }

    window.UniqueValidateImageFile = validateImageFile;

    // Block saving while photos are still uploading.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.querySelector('[data-upload-url]')) return;
        if (StagedUpload.pendingCount() > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            notify('warning', 'Photos are still uploading — please wait a moment and save again.');
        } else if (form.querySelector('.is-upload-failed:not(.is-removed)')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            notify('error', 'Some photos failed to upload. Remove them (red cards) and try again.');
        }
    }, true);

    function initUploader(root) {
        const input = root.querySelector('.multi-image-input');
        const grid = root.querySelector('[data-preview-grid]');
        const countEl = root.querySelector('[data-file-count]');
        const dropzone = root.querySelector('[data-dropzone]');
        const orderBox = root.querySelector('[data-gallery-order]');
        if (!input || !grid) return;

        const uploadUrl = root.getAttribute('data-upload-url') || '';
        const asyncMode = uploadUrl !== '';
        const uploadName = root.getAttribute('data-upload-name') || 'gallery_uploads[]';
        if (asyncMode) {
            input.removeAttribute('name');
        }

        let files = [];
        let dragCard = null;

        function visibleCards() {
            return Array.from(grid.querySelectorAll('.multi-image-card')).filter(function (card) {
                return !card.classList.contains('is-removed');
            });
        }

        function syncOrderInputs() {
            if (!orderBox) return;
            orderBox.innerHTML = '';
            let newIndex = 0;
            visibleCards().forEach(function (card) {
                let token = card.getAttribute('data-sort-token');
                if (asyncMode && card.classList.contains('is-new')) {
                    const uploadToken = card._uploadToken;
                    if (!uploadToken) return;
                    const up = document.createElement('input');
                    up.type = 'hidden';
                    up.name = uploadName;
                    up.value = uploadToken;
                    orderBox.appendChild(up);
                    token = 'new:' + newIndex;
                    newIndex += 1;
                }
                if (!token) return;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'gallery_order[]';
                hidden.value = token;
                orderBox.appendChild(hidden);
            });

            visibleCards().forEach(function (card, index) {
                const pos = card.querySelector('[data-pos]');
                if (pos) pos.textContent = index === 0 ? '1 · Thumbnail' : String(index + 1);
                card.classList.toggle('is-first', index === 0);
            });
        }

        function updateCount() {
            if (!countEl) return;
            const total = visibleCards().length;
            const uploading = grid.querySelectorAll('.is-uploading').length;
            let text = total ? total + ' photo(s)' : 'No images yet.';
            if (uploading) text += ' · uploading ' + uploading + '…';
            countEl.textContent = text;
        }

        function syncInputFilesFromGrid() {
            if (!asyncMode) {
                const orderedNew = [];
                visibleCards().forEach(function (card) {
                    const token = card.getAttribute('data-sort-token') || '';
                    if (token.indexOf('new:') !== 0) return;
                    const idx = parseInt(token.slice(4), 10);
                    if (files[idx]) orderedNew.push(files[idx]);
                });

                files = orderedNew;
                const dt = new DataTransfer();
                files.forEach(function (f) {
                    dt.items.add(f);
                });
                input.files = dt.files;

                let newIndex = 0;
                visibleCards().forEach(function (card) {
                    const token = card.getAttribute('data-sort-token') || '';
                    if (token.indexOf('new:') === 0) {
                        card.setAttribute('data-sort-token', 'new:' + newIndex);
                        newIndex += 1;
                    }
                });
            }

            updateCount();
            syncOrderInputs();
            root.dispatchEvent(new CustomEvent('images:changed', {
                bubbles: true,
                detail: { count: visibleCards().length },
            }));
        }

        function bindCard(card) {
            card.setAttribute('draggable', 'true');

            card.addEventListener('dragstart', function (e) {
                if (e.target.closest('button, input, label, a')) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            card.addEventListener('dragend', function () {
                card.classList.remove('is-dragging');
                dragCard = null;
                grid.querySelectorAll('.is-drop-target').forEach(function (el) {
                    el.classList.remove('is-drop-target');
                });
            });
            card.addEventListener('dragover', function (e) {
                e.preventDefault();
                if (!dragCard || dragCard === card) return;
                card.classList.add('is-drop-target');
            });
            card.addEventListener('dragleave', function () {
                card.classList.remove('is-drop-target');
            });
            card.addEventListener('drop', function (e) {
                e.preventDefault();
                card.classList.remove('is-drop-target');
                if (!dragCard || dragCard === card) return;
                const cards = Array.from(grid.children);
                const from = cards.indexOf(dragCard);
                const to = cards.indexOf(card);
                if (from < 0 || to < 0) return;
                if (from < to) {
                    grid.insertBefore(dragCard, card.nextSibling);
                } else {
                    grid.insertBefore(dragCard, card);
                }
                syncInputFilesFromGrid();
            });

            card.querySelectorAll('[data-move]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const dir = btn.getAttribute('data-move');
                    if (dir === 'first') {
                        grid.insertBefore(card, grid.firstElementChild);
                        syncInputFilesFromGrid();
                        return;
                    }
                    const sibling = dir === 'up' ? card.previousElementSibling : card.nextElementSibling;
                    if (!sibling) return;
                    if (dir === 'up') {
                        grid.insertBefore(card, sibling);
                    } else {
                        grid.insertBefore(sibling, card);
                    }
                    syncInputFilesFromGrid();
                });
            });
        }

        function makeNewCard(file, index) {
            const card = document.createElement('div');
            card.className = 'multi-image-card is-new';
            card.setAttribute('data-sort-token', 'new:' + index);
            card.innerHTML =
                '<span class="multi-image-pos" data-pos></span>' +
                '<img alt="">' +
                '<button type="button" class="multi-image-remove" data-remove-new title="Remove" aria-label="Remove">' +
                '<i class="bi bi-x-lg"></i></button>' +
                '<div class="multi-image-sort">' +
                '<button type="button" class="multi-image-sort-btn" data-move="up" title="Move left / earlier" aria-label="Move earlier"><i class="bi bi-chevron-left"></i></button>' +
                '<button type="button" class="multi-image-sort-btn" data-move="first" title="Make thumbnail (move to 1st)" aria-label="Make thumbnail"><i class="bi bi-star"></i></button>' +
                '<button type="button" class="multi-image-sort-btn" data-move="down" title="Move right / later" aria-label="Move later"><i class="bi bi-chevron-right"></i></button>' +
                '</div>' +
                '<div class="multi-image-progress"><span></span></div>' +
                '<div class="multi-image-meta">' +
                '<span class="multi-image-name"></span>' +
                '<span class="multi-image-size"></span>' +
                '</div>';

            card.querySelector('.multi-image-name').textContent = file.name;
            card.querySelector('.multi-image-size').textContent = bytesLabel(file.size);

            const img = card.querySelector('img');
            img.src = URL.createObjectURL(file);

            card.querySelector('[data-remove-new]').addEventListener('click', function () {
                card.remove();
                syncInputFilesFromGrid();
            });

            bindCard(card);
            return card;
        }

        function startUpload(card, file) {
            const bar = card.querySelector('.multi-image-progress span');
            card.classList.add('is-uploading');
            StagedUpload.upload(uploadUrl, file, 'products', function (ratio) {
                if (bar) bar.style.width = Math.round(ratio * 100) + '%';
            }).then(function (res) {
                card._uploadToken = res.token;
                card.classList.remove('is-uploading');
                card.classList.add('is-uploaded');
            }).catch(function (err) {
                card.classList.remove('is-uploading');
                card.classList.add('is-upload-failed');
                notify('error', err.message || 'Upload failed');
            }).finally(function () {
                if (card.isConnected) syncInputFilesFromGrid();
            });
        }

        function addFiles(fileList) {
            const incoming = Array.from(fileList || []);
            incoming.forEach(function (file) {
                if (!validateImageFile(file)) return;
                const dup = files.some(function (f) {
                    return f.name === file.name && f.size === file.size && f.lastModified === file.lastModified;
                });
                if (dup) return;
                const index = files.length;
                files.push(file);
                const card = makeNewCard(file, index);
                grid.appendChild(card);
                if (asyncMode) startUpload(card, file);
            });
            if (asyncMode) input.value = '';
            syncInputFilesFromGrid();
        }

        input.addEventListener('change', function () {
            addFiles(input.files);
        });

        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                addFiles(e.dataTransfer.files);
            });
        }

        root.querySelectorAll('[data-remove-existing]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.getAttribute('data-remove-existing');
                const card = root.querySelector('[data-existing-id="' + id + '"]');
                const flag = document.getElementById('remove_image_' + id);
                if (!card || !flag) return;

                const removing = !flag.checked;
                flag.checked = removing;
                card.classList.toggle('is-removed', removing);
                btn.title = removing ? 'Undo remove' : 'Remove image';
                btn.setAttribute('aria-label', btn.title);
                btn.innerHTML = removing ? '<i class="bi bi-arrow-counterclockwise"></i>' : '<i class="bi bi-x-lg"></i>';
                syncInputFilesFromGrid();
            });
        });

        grid.querySelectorAll('.multi-image-card').forEach(bindCard);
        syncOrderInputs();
        updateCount();

        root._getNewImageCount = function () {
            return asyncMode ? grid.querySelectorAll('.multi-image-card.is-new').length : files.length;
        };
    }

    function boot() {
        document.querySelectorAll('[data-uploader]').forEach(function (root) {
            if (root._uploaderReady) return;
            root._uploaderReady = true;
            initUploader(root);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.UniqueMultiImageUploader = { initAll: boot };
})(window, document);
