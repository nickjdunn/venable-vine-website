(function () {
    const IMAGE_KEYS = ['src', 'background_image', 'logo_image', 'image'];

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function escAttr(s) {
        return esc(s).replace(/"/g, '&quot;');
    }

    let modal = null;
    let onSelectCallback = null;
    let cachedItems = null;

    function ensureModal() {
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'media-picker-modal';
        modal.hidden = true;
        modal.innerHTML = `
            <div class="media-picker-backdrop" data-media-close></div>
            <div class="media-picker-dialog" role="dialog" aria-label="Choose image">
                <div class="media-picker-header">
                    <h3>Select Image</h3>
                    <button type="button" class="media-picker-close" data-media-close aria-label="Close">&times;</button>
                </div>
                <div class="media-picker-toolbar">
                    <label class="btn btn-sm btn-outline media-picker-upload-label">
                        Upload New Image
                        <input type="file" accept="image/*" hidden data-media-modal-upload>
                    </label>
                    <span class="media-picker-status" data-media-status></span>
                </div>
                <div class="media-picker-grid" data-media-grid></div>
            </div>`;
        document.body.appendChild(modal);

        modal.querySelectorAll('[data-media-close]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });
        modal.querySelector('[data-media-modal-upload]')?.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            await uploadFile(file);
            e.target.value = '';
        });
        return modal;
    }

    function closeModal() {
        if (modal) modal.hidden = true;
        onSelectCallback = null;
    }

    async function fetchItems() {
        const res = await fetch('/api/media.php?action=list');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Could not load images');
        cachedItems = data.items || [];
        return cachedItems;
    }

    async function uploadFile(file, meta = {}) {
        const status = modal?.querySelector('[data-media-status]');
        if (status) status.textContent = 'Uploading…';
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('photo', file);
        fd.append('_csrf', window.CSRF_TOKEN || '');
        Object.entries(meta).forEach(([k, v]) => { if (v) fd.append(k, v); });
        const res = await fetch('/api/media.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
            if (status) status.textContent = data.message || 'Upload failed';
            throw new Error(data.message || 'Upload failed');
        }
        cachedItems = null;
        if (status) status.textContent = 'Uploaded';
        return data.item;
    }

    function renderGrid(items) {
        const grid = modal.querySelector('[data-media-grid]');
        if (!items.length) {
            grid.innerHTML = '<p class="media-picker-empty">No images yet. Upload one above.</p>';
            return;
        }
        grid.innerHTML = items.map((item) => `
            <button type="button" class="media-picker-item" data-path="${escAttr(item.file_path)}" title="${escAttr(item.display_name || item.file_path)}">
                <img src="${escAttr(item.url)}" alt="${escAttr(item.alt_text || '')}">
                <span>${esc(item.display_name || item.file_path.split('/').pop())}</span>
            </button>`).join('');
        grid.querySelectorAll('.media-picker-item').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (onSelectCallback) onSelectCallback(btn.dataset.path);
                closeModal();
            });
        });
    }

    async function openModal(callback) {
        onSelectCallback = callback;
        ensureModal();
        modal.hidden = false;
        const status = modal.querySelector('[data-media-status]');
        if (status) status.textContent = 'Loading…';
        try {
            const items = await fetchItems();
            renderGrid(items);
            if (status) status.textContent = '';
        } catch (e) {
            if (status) status.textContent = e.message;
        }
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

        field.querySelector('[data-media-select]')?.addEventListener('click', () => {
            openModal((path) => setFieldValue(field, path));
        });

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

    /** Image field HTML for page builder inspector (hidden input + picker buttons). */
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
                <button type="button" class="btn btn-sm" data-media-select>Select Image</button>
                <button type="button" class="btn btn-sm btn-outline" data-media-upload>Upload Image</button>
                <button type="button" class="btn btn-sm btn-muted" data-media-clear"${path ? '' : ' style="display:none"'}>Clear</button>
            </div>
            <input type="file" accept="image/*" data-media-file hidden>
        </div>`;
    }

    window.MediaPicker = {
        init,
        open: openModal,
        upload: uploadFile,
        imageFieldHtml,
        IMAGE_KEYS,
        bindField,
        setFieldValue,
    };

    document.addEventListener('DOMContentLoaded', () => init());
})();
