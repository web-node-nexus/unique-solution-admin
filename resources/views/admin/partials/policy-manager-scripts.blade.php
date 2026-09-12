<script>
(function () {
    const managers = document.querySelectorAll('[data-policy-manager]');
    if (!managers.length) return;

    const palette = ['#EEF2FF', '#ECFDF5', '#FFF7ED', '#FDF2F8', '#EFF6FF', '#F0FDF4'];

    managers.forEach(function (root) {
        const field = root.getAttribute('data-field') || 'policies';
        const list = root.querySelector('[data-policy-list]');
        const editor = root.querySelector('[data-policy-editor]');
        const idle = root.querySelector('[data-policy-editor-idle]');
        const empty = root.querySelector('[data-policy-empty]');
        const titleInput = root.querySelector('[data-policy-editor-title]');
        const descInput = root.querySelector('[data-policy-editor-description]');
        const iconInput = root.querySelector('[data-policy-editor-icon]');
        const preview = root.querySelector('[data-policy-editor-preview]');
        const label = root.querySelector('[data-policy-editor-label]');
        const saveBtn = root.querySelector('[data-policy-save]');

        let activeCard = null;
        let pendingIconFile = null;
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

        function openEditor(card, isNew) {
            activeCard = card;
            pendingIconFile = null;
            if (iconInput) iconInput.value = '';
            titleInput.value = card.querySelector('[data-policy-title-input]')?.value || '';
            descInput.value = card.querySelector('[data-policy-description-input]')?.value || '';
            const img = card.querySelector('[data-policy-icon-img]');
            if (img && img.getAttribute('src')) {
                preview.src = img.getAttribute('src');
                preview.classList.remove('d-none');
            } else {
                preview.removeAttribute('src');
                preview.classList.add('d-none');
            }
            label.textContent = isNew ? 'Adding policy' : 'Editing policy';
            saveBtn.textContent = isNew ? 'Save' : 'Update';
            editor.hidden = false;
            idle?.classList.add('d-none');
            titleInput.focus();
        }

        function closeEditor() {
            activeCard = null;
            pendingIconFile = null;
            if (iconInput) iconInput.value = '';
            editor.hidden = true;
            idle?.classList.remove('d-none');
        }

        function applyToCard(card) {
            const title = (titleInput.value || '').trim();
            const description = descInput.value || '';
            card.querySelector('[data-policy-title-input]').value = title;
            card.querySelector('[data-policy-description-input]').value = description;
            card.querySelector('[data-policy-title-text]').textContent = title || 'Untitled policy';
            card.querySelector('[data-policy-description-text]').textContent =
                description.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 90) || 'No description';

            if (pendingIconFile) {
                const wrap = card.querySelector('[data-policy-icon-wrap]');
                let img = card.querySelector('[data-policy-icon-img]');
                const fallback = card.querySelector('[data-policy-icon-fallback]');
                if (!img) {
                    img = document.createElement('img');
                    img.setAttribute('data-policy-icon-img', '');
                    img.alt = '';
                    wrap.innerHTML = '';
                    wrap.appendChild(img);
                }
                img.src = URL.createObjectURL(pendingIconFile);
                fallback?.remove();

                const index = card.getAttribute('data-index');
                let fileInput = card.querySelector('[data-policy-file-input]');
                if (!fileInput) {
                    fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.name = field + '[' + index + '][icon]';
                    fileInput.className = 'd-none';
                    fileInput.setAttribute('data-policy-file-input', '');
                    card.appendChild(fileInput);
                }
                const dt = new DataTransfer();
                dt.items.add(pendingIconFile);
                fileInput.files = dt.files;
            }
        }

        function createCard() {
            const index = nextIndex++;
            const tint = palette[index % palette.length];
            const card = document.createElement('div');
            card.className = 'policy-card';
            card.setAttribute('data-policy-card', '');
            card.setAttribute('data-index', String(index));
            card.style.setProperty('--policy-tint', tint);
            card.innerHTML =
                '<input type="hidden" name="' + field + '[' + index + '][id]" value="" data-policy-id>' +
                '<input type="hidden" name="' + field + '[' + index + '][remove]" value="0" data-policy-remove>' +
                '<input type="hidden" name="' + field + '[' + index + '][title]" value="" data-policy-title-input>' +
                '<input type="hidden" name="' + field + '[' + index + '][description]" value="" data-policy-description-input>' +
                '<div class="policy-card-icon" data-policy-icon-wrap><i class="bi bi-shield-check" data-policy-icon-fallback></i></div>' +
                '<div class="policy-card-body">' +
                '<div class="policy-card-title" data-policy-title-text>New policy</div>' +
                '<div class="policy-card-desc" data-policy-description-text>Add title and description</div>' +
                '</div>' +
                '<div class="policy-card-actions">' +
                '<button type="button" class="btn btn-link btn-sm text-decoration-none" data-policy-edit>Edit</button>' +
                '<button type="button" class="btn btn-link btn-sm text-danger text-decoration-none" data-policy-delete>Delete</button>' +
                '</div>';
            list.appendChild(card);
            refreshEmpty();
            openEditor(card, true);
        }

        root.querySelectorAll('[data-policy-add]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                createCard();
            });
        });

        list.addEventListener('click', function (e) {
            const editBtn = e.target.closest('[data-policy-edit]');
            const deleteBtn = e.target.closest('[data-policy-delete]');
            const card = e.target.closest('[data-policy-card]');
            if (!card) return;

            if (deleteBtn) {
                e.preventDefault();
                const removeInput = card.querySelector('[data-policy-remove]');
                const id = card.querySelector('[data-policy-id]')?.value;
                if (id) {
                    removeInput.value = '1';
                    card.setAttribute('data-removed', '1');
                    card.classList.add('d-none');
                } else {
                    card.remove();
                }
                if (activeCard === card) closeEditor();
                refreshEmpty();
                return;
            }

            if (editBtn || card) {
                e.preventDefault();
                openEditor(card, false);
            }
        });

        iconInput?.addEventListener('change', function () {
            const file = iconInput.files && iconInput.files[0];
            pendingIconFile = file || null;
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
            }
        });

        root.querySelector('[data-policy-save]')?.addEventListener('click', function () {
            if (!activeCard) return;
            if (!(titleInput.value || '').trim()) {
                titleInput.focus();
                titleInput.classList.add('is-invalid');
                return;
            }
            titleInput.classList.remove('is-invalid');
            applyToCard(activeCard);
            closeEditor();
        });

        root.querySelector('[data-policy-cancel]')?.addEventListener('click', function () {
            if (activeCard && !(activeCard.querySelector('[data-policy-title-input]')?.value || '').trim()
                && !(activeCard.querySelector('[data-policy-id]')?.value || '')) {
                activeCard.remove();
                refreshEmpty();
            }
            closeEditor();
        });

        refreshEmpty();
    });
})();
</script>
