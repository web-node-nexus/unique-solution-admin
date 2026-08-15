/**
 * Multi-image picker: preview, remove, and admin-controlled sort order.
 * Gallery order is posted as gallery_order[] = existing:{id} | new:{index}
 * New files stay in the same order as gallery_order new: tokens.
 */
(function (window, document) {
    'use strict';

    function bytesLabel(n) {
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function initUploader(root) {
        const input = root.querySelector('.multi-image-input');
        const grid = root.querySelector('[data-preview-grid]');
        const countEl = root.querySelector('[data-file-count]');
        const dropzone = root.querySelector('[data-dropzone]');
        const orderBox = root.querySelector('[data-gallery-order]');
        if (!input || !grid) return;

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
            visibleCards().forEach(function (card) {
                const token = card.getAttribute('data-sort-token');
                if (!token) return;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'gallery_order[]';
                hidden.value = token;
                orderBox.appendChild(hidden);
            });

            visibleCards().forEach(function (card, index) {
                const pos = card.querySelector('[data-pos]');
                if (pos) pos.textContent = String(index + 1);
                card.classList.toggle('is-first', index === 0);
            });
        }

        function syncInputFilesFromGrid() {
            const orderedNew = [];
            visibleCards().forEach(function (card) {
                const token = card.getAttribute('data-sort-token') || '';
                if (token.indexOf('new:') !== 0) return;
                const idx = parseInt(token.slice(4), 10);
                if (files[idx]) orderedNew.push(files[idx]);
            });

            // Rebuild new: tokens to match compacted file indexes
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

            if (countEl) {
                countEl.textContent = files.length
                    ? files.length + ' new image(s) ready to upload'
                    : 'No new images selected.';
            }

            syncOrderInputs();
            root.dispatchEvent(new CustomEvent('images:changed', {
                bubbles: true,
                detail: { count: files.length },
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
                '<button type="button" class="multi-image-sort-btn" data-move="down" title="Move right / later" aria-label="Move later"><i class="bi bi-chevron-right"></i></button>' +
                '</div>' +
                '<div class="multi-image-meta">' +
                '<span class="multi-image-name"></span>' +
                '<span class="multi-image-size"></span>' +
                '</div>';

            card.querySelector('.multi-image-name').textContent = file.name;
            card.querySelector('.multi-image-size').textContent = bytesLabel(file.size);

            const img = card.querySelector('img');
            const reader = new FileReader();
            reader.onload = function (ev) {
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);

            card.querySelector('[data-remove-new]').addEventListener('click', function () {
                card.remove();
                syncInputFilesFromGrid();
            });

            bindCard(card);
            return card;
        }

        function addFiles(fileList) {
            const incoming = Array.from(fileList || []);
            incoming.forEach(function (file) {
                if (!file.type || !file.type.startsWith('image/')) return;
                const dup = files.some(function (f) {
                    return f.name === file.name && f.size === file.size && f.lastModified === file.lastModified;
                });
                if (dup) return;
                const index = files.length;
                files.push(file);
                grid.appendChild(makeNewCard(file, index));
            });
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

                const primary = card.querySelector('input[name="primary_image_id"]');
                if (removing && primary && primary.checked) {
                    const next = root.querySelector('.multi-image-card.existing:not(.is-removed) input[name="primary_image_id"]');
                    if (next) next.checked = true;
                }
                syncInputFilesFromGrid();
            });
        });

        grid.querySelectorAll('.multi-image-card').forEach(bindCard);
        syncOrderInputs();

        root._getNewImageCount = function () {
            return files.length;
        };
    }

    function boot() {
        document.querySelectorAll('[data-uploader]').forEach(initUploader);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.UniqueMultiImageUploader = { initAll: boot };
})(window, document);
