{{-- Full product specification rich text editor (TinyMCE) --}}
<script>
window.initUniqueSolutionEditor = function (selector, options) {
    if (typeof tinymce === 'undefined') {
        console.warn('TinyMCE not loaded');
        return;
    }

    options = options || {};
    const sel = selector || '#description';
    const id = sel.replace('#', '');

    if (tinymce.get(id)) {
        tinymce.get(id).remove();
    }

    tinymce.init({
        selector: sel,
        license_key: 'gpl',
        height: options.height || 520,
        min_height: options.minHeight || 280,
        max_height: 900,
        menubar: 'file edit view insert format tools table',
        menu: {
            edit: { title: 'Edit', items: 'undo redo | cut copy custompaste custompastetext | selectall' },
        },
        branding: false,
        promotion: false,
        resize: true,
        statusbar: true,
        elementpath: true,
        // Large specs paste / long documents
        paste_data_images: true,
        paste_as_text: false,
        smart_paste: true,
        contextmenu: 'cut copy custompaste custompastetext | link image inserttable',
        browser_spellcheck: true,
        entity_encoding: 'raw',
        verify_html: false,
        cleanup: false,
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
        // No artificial content length cap in editor
        maxlength: 0,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'wordcount', 'autoresize',
            'directionality', 'pagebreak'
        ].join(' '),
        toolbar: [
            'undo redo | cut copy custompaste custompastetext | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
            'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote hr',
            'table tabledelete | link image media | searchreplace | removeformat code fullscreen preview'
        ].join(' | '),
        toolbar_mode: 'sliding',
        font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
        font_family_formats:
            'Plus Jakarta Sans=Plus Jakarta Sans,sans-serif;Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;Georgia=georgia,palatino;Tahoma=tahoma,arial;Times New Roman=times new roman,times;Verdana=verdana,geneva',
        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre',
        table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
        content_style: `
            body {
                font-family: 'Plus Jakarta Sans', Arial, sans-serif;
                font-size: 15px;
                line-height: 1.6;
                color: #1e293b;
                max-width: 100%;
                padding: 12px 16px;
            }
            h1,h2,h3,h4 { color: #0f172a; margin-top: 1.1em; }
            table { border-collapse: collapse; width: 100%; }
            table td, table th { border: 1px solid #cbd5e1; padding: 8px 10px; }
            table th { background: #f1f5f9; font-weight: 600; }
            img { max-width: 100%; height: auto; }
            ul, ol { padding-left: 1.4rem; }
        `,
        setup: function (editor) {
            const insertPlain = function (text) {
                const html = editor.dom.encode(text || '').replace(/\r\n|\r|\n/g, '<br>');
                editor.insertContent(html);
            };

            const pasteFromClipboard = async function (asPlainText) {
                try {
                    if (navigator.clipboard && navigator.clipboard.read) {
                        const items = await navigator.clipboard.read();
                        for (const item of items) {
                            if (!asPlainText && item.types.includes('text/html')) {
                                const html = await (await item.getType('text/html')).text();
                                editor.insertContent(html);
                                return true;
                            }
                            if (item.types.includes('text/plain')) {
                                insertPlain(await (await item.getType('text/plain')).text());
                                return true;
                            }
                        }
                    }
                    if (navigator.clipboard && navigator.clipboard.readText) {
                        insertPlain(await navigator.clipboard.readText());
                        return true;
                    }
                } catch (e) {
                    return false;
                }
                return false;
            };

            const openPasteFallback = function (asPlainText) {
                editor.windowManager.open({
                    title: asPlainText ? 'Paste as text' : 'Paste',
                    body: {
                        type: 'panel',
                        items: [{
                            type: 'textarea',
                            name: 'content',
                            label: 'Paste here with Ctrl+V / Cmd+V, then click Insert',
                        }],
                    },
                    size: 'medium',
                    buttons: [
                        { type: 'cancel', text: 'Cancel' },
                        { type: 'submit', text: 'Insert', primary: true },
                    ],
                    onSubmit: function (api) {
                        insertPlain(api.getData().content || '');
                        api.close();
                    },
                });
            };

            const doPaste = async function (asPlainText) {
                editor.focus();
                const ok = await pasteFromClipboard(asPlainText);
                if (!ok) {
                    openPasteFallback(asPlainText);
                }
            };

            editor.ui.registry.addButton('custompaste', {
                icon: 'paste',
                tooltip: 'Paste (Ctrl+V / Cmd+V)',
                onAction: function () { doPaste(false); },
            });
            editor.ui.registry.addButton('custompastetext', {
                icon: 'paste-text',
                tooltip: 'Paste as plain text',
                onAction: function () { doPaste(true); },
            });
            editor.ui.registry.addMenuItem('custompaste', {
                text: 'Paste from clipboard',
                icon: 'paste',
                shortcut: 'Meta+V',
                onAction: function () { doPaste(false); },
            });
            editor.ui.registry.addMenuItem('custompastetext', {
                text: 'Paste as text',
                icon: 'paste-text',
                onAction: function () { doPaste(true); },
            });

            editor.on('change keyup SetContent', function () {
                editor.save();
                const el = document.querySelector(sel);
                if (el) {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        },
    });
};

document.addEventListener('DOMContentLoaded', function () {
    if (window.__usRichEditorBound) {
        return;
    }
    window.__usRichEditorBound = true;
    document.querySelectorAll('[data-rich-editor]').forEach(function (el) {
        if (!el.id) return;
        const height = parseInt(el.getAttribute('data-editor-height') || '0', 10) || undefined;
        window.initUniqueSolutionEditor('#' + el.id, { height: height, minHeight: height ? Math.min(height, 280) : undefined });
    });
});

document.addEventListener('submit', function () {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
}, true);
</script>
