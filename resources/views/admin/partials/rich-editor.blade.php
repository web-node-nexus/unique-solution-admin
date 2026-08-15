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
        branding: false,
        promotion: false,
        resize: true,
        statusbar: true,
        elementpath: true,
        // Large specs paste / long documents
        paste_data_images: true,
        paste_as_text: false,
        smart_paste: true,
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
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
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
