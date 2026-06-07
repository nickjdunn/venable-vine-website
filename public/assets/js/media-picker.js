(function () {
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function escAttr(s) {
        return esc(s).replace(/"/g, '&quot;');
    }

    /** Upload-only image field helper for page builder inspector. */
    function imageFieldHtml(name, label, value) {
        const path = value || '';
        const url = path ? '/' + path.replace(/^\//, '') : '';
        return `<div class="media-picker-field pb-media-field" data-media-picker data-pb-field="${escAttr(name)}">
            <label>${esc(label)}</label>
            <input type="hidden" name="${escAttr(name)}" value="${escAttr(path)}" data-media-input>
            <div class="media-picker-preview"${path ? '' : ' style="display:none"'}>
                <img src="${escAttr(url)}" alt="" data-media-preview>
            </div>
            <div class="media-picker-actions">
                <button type="button" class="btn btn-sm btn-outline" data-media-upload>Upload Image</button>
                <button type="button" class="btn btn-sm btn-muted" data-media-clear"${path ? '' : ' style="display:none"'}>Clear</button>
            </div>
            <input type="file" accept="image/*" data-media-file hidden>
        </div>`;
    }

    async function uploadFile(file) {
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('photo', file);
        fd.append('_csrf', window.CSRF_TOKEN || '');
        const res = await fetch('/api/media.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message || 'Upload failed');
        }
        return data.item;
    }

    function setFieldValue(field, path) {
        const input = field.querySelector('[data-media-input]');
        const preview = field.querySelector('.media-picker-preview');
        const img = field.querySelector('[data-media-preview]');
        const clearBtn = field.querySelector('[data-media-clear]');
        if (input) {
            input.value = path || '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (path) {
            if (preview) preview.style.display = '';
            if (img) img.src = '/' + path.replace(/^\//, '');
            if (clearBtn) clearBtn.style.display = '';
        } else {
            if (preview) preview.style.display = 'none';
            if (img) img.src = '';
            if (clearBtn) clearBtn.style.display = 'none';
        }
    }

    function bindField(field) {
        if (field.dataset.mediaBound) return;
        field.dataset.mediaBound = '1';

        field.querySelector('[data-media-upload]')?.addEventListener('click', () => {
            field.querySelector('[data-media-file]')?.click();
        });

        field.querySelector('[data-media-file]')?.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            try {
                const item = await uploadFile(file);
                setFieldValue(field, item.file_path);
            } catch (err) {
                alert(err.message || 'Upload failed');
            }
            e.target.value = '';
        });

        field.querySelector('[data-media-clear]')?.addEventListener('click', () => {
            setFieldValue(field, '');
        });
    }

    function init(root = document) {
        root.querySelectorAll('[data-media-picker]').forEach(bindField);
    }

    window.MediaPicker = {
        init,
        upload: uploadFile,
        imageFieldHtml,
        bindField,
        setFieldValue,
    };

    document.addEventListener('DOMContentLoaded', () => init());
})();
