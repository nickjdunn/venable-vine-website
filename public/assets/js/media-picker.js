(function () {
    let modalEl = null;
    let activeCallback = null;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function escAttr(s) {
        return esc(s).replace(/"/g, '&quot;');
    }

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
                <button type="button" class="btn btn-sm btn-outline" data-media-select>Add Photo</button>
                <button type="button" class="btn btn-sm btn-muted" data-media-clear"${path ? '' : ' style="display:none"'}>Clear</button>
            </div>
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

    async function fetchLibrary() {
        const res = await fetch('/api/media.php?action=list', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message || 'Could not load media library');
        }
        return data.items || [];
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

    function ensureModal() {
        if (modalEl) return modalEl;
        modalEl = document.createElement('div');
        modalEl.id = 'media-picker-modal';
        modalEl.className = 'media-picker-modal';
        modalEl.hidden = true;
        modalEl.innerHTML = `<div class="media-picker-backdrop" data-media-close></div>
            <div class="media-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="media-picker-title">
                <div class="media-picker-header">
                    <h3 id="media-picker-title">Choose a Photo</h3>
                    <button type="button" class="media-picker-close" data-media-close aria-label="Close">&times;</button>
                </div>
                <div class="media-picker-toolbar">
                    <label class="btn btn-sm btn-outline media-picker-upload-label">
                        Upload New Photo
                        <input type="file" accept="image/*" data-media-modal-upload hidden>
                    </label>
                    <span class="media-picker-status" data-media-status></span>
                </div>
                <div class="media-picker-grid" data-media-grid></div>
            </div>`;
        document.body.appendChild(modalEl);

        modalEl.querySelectorAll('[data-media-close]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });

        modalEl.querySelector('[data-media-modal-upload]')?.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const status = modalEl.querySelector('[data-media-status]');
            status.textContent = 'Uploading…';
            try {
                const item = await uploadFile(file);
                status.textContent = 'Uploaded!';
                if (activeCallback && item?.file_path) {
                    activeCallback(item.file_path);
                    closeModal();
                } else {
                    await renderGrid();
                    status.textContent = '';
                }
            } catch (err) {
                status.textContent = err.message || 'Upload failed';
            }
            e.target.value = '';
        });

        return modalEl;
    }

    async function renderGrid() {
        const grid = modalEl.querySelector('[data-media-grid]');
        const status = modalEl.querySelector('[data-media-status]');
        grid.innerHTML = '<p class="media-picker-empty">Loading…</p>';
        try {
            const items = await fetchLibrary();
            if (!items.length) {
                grid.innerHTML = '<p class="media-picker-empty">No photos yet. Upload one above or visit Media Library.</p>';
                return;
            }
            grid.innerHTML = '';
            items.forEach((item) => {
                const path = item.file_path || '';
                if (!path) return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'media-picker-item';
                btn.innerHTML = `<img src="/${path.replace(/^\//, '')}" alt="${escAttr(item.alt_text || item.display_name || '')}">
                    <span>${esc(item.display_name || path.split('/').pop())}</span>`;
                btn.addEventListener('click', () => {
                    if (activeCallback) activeCallback(path);
                    closeModal();
                });
                grid.appendChild(btn);
            });
            status.textContent = '';
        } catch (err) {
            grid.innerHTML = `<p class="media-picker-empty">${esc(err.message)}</p>`;
        }
    }

    function closeModal() {
        if (!modalEl) return;
        modalEl.hidden = true;
        activeCallback = null;
        document.body.style.overflow = '';
    }

    async function openLibrary(onSelect) {
        ensureModal();
        activeCallback = onSelect;
        modalEl.hidden = false;
        document.body.style.overflow = 'hidden';
        await renderGrid();
    }

    function bindField(field) {
        if (field.dataset.mediaBound) return;
        field.dataset.mediaBound = '1';

        field.querySelector('[data-media-select]')?.addEventListener('click', () => {
            openLibrary((path) => setFieldValue(field, path));
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
        openLibrary,
        imageFieldHtml,
        bindField,
        setFieldValue,
    };

    document.addEventListener('DOMContentLoaded', () => init());
})();
