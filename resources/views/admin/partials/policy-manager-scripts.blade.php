<script>
(function () {
    const managers = document.querySelectorAll('[data-policy-manager]');
    if (!managers.length) return;

    function destroyEditor(textarea) {
        if (!textarea || typeof tinymce === 'undefined') return;
        const id = textarea.id;
        if (id && tinymce.get(id)) {
            tinymce.get(id).remove();
        }
    }

    function initEditor(textarea) {
        if (!textarea) return;
        if (!textarea.id) {
            textarea.id = 'policy_desc_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        }
        if (typeof window.initUniqueSolutionEditor === 'function') {
            window.initUniqueSolutionEditor('#' + textarea.id, { height: 280, minHeight: 200 });
        }
    }

    function triggerSaveAll() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
    }

    managers.forEach(function (root) {
        const field = root.getAttribute('data-field') || 'policies';
        const list = root.querySelector('[data-policy-list]');
        const empty = root.querySelector('[data-policy-empty]');
        let nextIndex = list.querySelectorAll('[data-policy-card]').length;

        function visibleCards() {
            return Array.from(list.querySelectorAll('[data-policy-card]')).filter(function (card) {
                return card.getAttribute('data-removed') !== '1';
            });
        }

        function refreshEmpty() {
            if (!empty) return;
            empty.classList.toggle('d-none', visibleCards().length > 0);
        }

        function collapseCard(card) {
            const panel = card.querySelector('[data-policy-editor-panel]');
            const summary = card.querySelector('[data-policy-summary]');
            const titleInput = card.querySelector('[data-policy-title-input]');
            const descInput = card.querySelector('[data-policy-description-input]');
            const titleText = card.querySelector('[data-policy-title-text]');

            triggerSaveAll();
            destroyEditor(descInput);

            if (titleText && titleInput) {
                titleText.textContent = (titleInput.value || '').trim() || 'Untitled policy';
            }

            if (panel) panel.hidden = true;
            if (summary) summary.hidden = false;
            card.classList.remove('is-open');
        }

        function expandCard(card) {
            visibleCards().forEach(function (other) {
                if (other !== card) collapseCard(other);
            });

            const panel = card.querySelector('[data-policy-editor-panel]');
            const summary = card.querySelector('[data-policy-summary]');
            const descInput = card.querySelector('[data-policy-description-input]');
            const titleInput = card.querySelector('[data-policy-title-input]');

            if (summary) summary.hidden = true;
            if (panel) panel.hidden = false;
            card.classList.add('is-open');

            initEditor(descInput);
            titleInput?.focus();
        }

        function removeCard(card) {
            const removeInput = card.querySelector('[data-policy-remove]');
            const id = card.querySelector('[data-policy-id]')?.value;
            const descInput = card.querySelector('[data-policy-description-input]');
            destroyEditor(descInput);

            if (id) {
                if (removeInput) removeInput.value = '1';
                card.setAttribute('data-removed', '1');
                card.classList.add('d-none');
            } else {
                card.remove();
            }
            refreshEmpty();
        }

        function createCard() {
            const index = nextIndex++;
            const descId = field + '_desc_' + index + '_' + Date.now();
            const card = document.createElement('div');
            card.className = 'policy-item';
            card.setAttribute('data-policy-card', '');
            card.setAttribute('data-index', String(index));
            card.innerHTML =
                '<input type="hidden" name="' + field + '[' + index + '][id]" value="" data-policy-id>' +
                '<input type="hidden" name="' + field + '[' + index + '][remove]" value="0" data-policy-remove>' +
                '<div class="policy-item-summary" data-policy-summary hidden>' +
                '  <div class="policy-item-summary-main">' +
                '    <div class="policy-item-icon" data-policy-icon-wrap><i class="bi bi-shield-check" data-policy-icon-fallback></i></div>' +
                '    <div class="policy-item-title" data-policy-title-text>New policy</div>' +
                '  </div>' +
                '  <div class="policy-item-actions">' +
                '    <button type="button" class="btn btn-sm btn-outline-primary" data-policy-edit><i class="bi bi-pencil me-1"></i>Edit</button>' +
                '    <button type="button" class="btn btn-sm btn-outline-danger" data-policy-delete><i class="bi bi-trash me-1"></i>Remove</button>' +
                '  </div>' +
                '</div>' +
                '<div class="policy-item-editor" data-policy-editor-panel>' +
                '  <div class="policy-item-editor-toolbar">' +
                '    <div class="fw-semibold text-muted small text-uppercase">Adding policy</div>' +
                '    <div class="d-flex gap-2">' +
                '      <button type="button" class="btn btn-sm btn-primary" data-policy-done><i class="bi bi-check-lg me-1"></i>Done</button>' +
                '      <button type="button" class="btn btn-sm btn-outline-danger" data-policy-delete><i class="bi bi-trash me-1"></i>Remove</button>' +
                '    </div>' +
                '  </div>' +
                '  <div class="mb-3">' +
                '    <label class="form-label">Tagline <span class="text-danger">*</span></label>' +
                '    <input type="text" class="form-control" name="' + field + '[' + index + '][title]" value="" maxlength="160" placeholder="e.g. 1 Year Manufacturer Warranty | Pan-India Service" data-policy-title-input>' +
                '  </div>' +
                '  <div class="mb-3">' +
                '    <label class="form-label">Policy icon / photo</label>' +
                '    <div class="policy-icon-upload">' +
                '      <div class="policy-icon-preview" data-policy-icon-wrap><i class="bi bi-shield-check" data-policy-icon-fallback></i></div>' +
                '      <div>' +
                '        <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-upload me-1"></i>Upload photo' +
                '          <input type="file" class="d-none" name="' + field + '[' + index + '][icon]" accept="' + (document.body.dataset.imageAccept || 'image/jpeg,image/png,image/webp') + '" data-policy-file-input>' +
                '        </label>' +
                '        <div class="form-text mt-1 image-upload-hint">Allowed: JPG, PNG, WebP. Maximum ' + (document.body.dataset.imageMaxMb || '5') + ' MB per image. Square icon works best.</div>' +
                '      </div>' +
                '    </div>' +
                '  </div>' +
                '  <div class="mb-0">' +
                '    <label class="form-label">Full policy details</label>' +
                '    <textarea class="form-control" rows="10" name="' + field + '[' + index + '][description]" id="' + descId + '" data-policy-description-input placeholder="HTML table / coverage / terms…"></textarea>' +
                '    <div class="form-text">You can paste HTML tables (Coverage / Period / Terms) like the store sheet.</div>' +
                '  </div>' +
                '</div>';

            list.appendChild(card);
            refreshEmpty();
            expandCard(card);
        }

        root.querySelectorAll('[data-policy-add]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                createCard();
            });
        });

        list.addEventListener('click', function (e) {
            const card = e.target.closest('[data-policy-card]');
            if (!card) return;

            if (e.target.closest('[data-policy-delete]')) {
                e.preventDefault();
                removeCard(card);
                return;
            }

            if (e.target.closest('[data-policy-edit]')) {
                e.preventDefault();
                expandCard(card);
                return;
            }

            if (e.target.closest('[data-policy-done]')) {
                e.preventDefault();
                const titleInput = card.querySelector('[data-policy-title-input]');
                if (!(titleInput?.value || '').trim()) {
                    titleInput?.classList.add('is-invalid');
                    titleInput?.focus();
                    return;
                }
                titleInput?.classList.remove('is-invalid');
                collapseCard(card);
            }
        });

        list.addEventListener('change', function (e) {
            const fileInput = e.target.closest('[data-policy-file-input]');
            if (!fileInput) return;
            const card = fileInput.closest('[data-policy-card]');
            const file = fileInput.files && fileInput.files[0];
            if (!card || !file) return;

            card.querySelectorAll('[data-policy-icon-wrap]').forEach(function (wrap) {
                let img = wrap.querySelector('[data-policy-icon-img]');
                wrap.querySelector('[data-policy-icon-fallback]')?.remove();
                if (!img) {
                    img = document.createElement('img');
                    img.setAttribute('data-policy-icon-img', '');
                    img.alt = '';
                    wrap.innerHTML = '';
                    wrap.appendChild(img);
                }
                img.src = URL.createObjectURL(file);
            });
        });

        const form = root.closest('form');
        form?.addEventListener('submit', function () {
            triggerSaveAll();
        });

        refreshEmpty();
    });
})();
</script>
