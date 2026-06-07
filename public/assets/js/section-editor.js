document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('editor-canvas');
    const saveBtn = document.getElementById('save-layout-btn');
    const resetBtn = document.getElementById('reset-layout-btn');
    const statusEl = document.getElementById('se-status');

    if (!canvas || !window.EDITOR_INITIAL?.success) {
        return;
    }

    const bootstrap = window.EDITOR_INITIAL;
    const sectionSettings = bootstrap.sectionSettings || {};
    let sections = layoutToSections(bootstrap.layout);
    let sortable = null;
    let openPopover = null;

    function layoutToSections(layout) {
        const out = [];
        (layout?.rows || []).forEach((row, i) => {
            const col = row.columns?.[0];
            const block = col?.blocks?.[0];
            if (!block) return;
            out.push({
                rowId: row.id || `row_${i + 1}`,
                colId: col.id || `col_${i + 1}`,
                id: block.id,
                type: block.type,
                config: { ...(block.config || {}) },
                active: block.active !== false,
            });
        });
        return out;
    }

    function sectionsToLayout() {
        return {
            rows: sections.map((sec, i) => ({
                id: sec.rowId || `row_${i + 1}`,
                layout: 'full',
                columns: [{
                    id: sec.colId || `col_${i + 1}`,
                    blocks: [{
                        id: sec.id,
                        type: sec.type,
                        config: sec.config,
                        active: sec.active,
                    }],
                }],
            })),
        };
    }

    function getSection(blockId) {
        return sections.find((s) => s.id === blockId);
    }

    function clientLog(message, data = {}) {
        if (!window.AGENT_DEBUG_ENABLED || !window.CSRF_TOKEN) return;
        fetch('/api/admin-log.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                message: 'SE: ' + message,
                page: window.location.pathname,
                hypothesisId: 'C',
                data,
                _csrf: window.CSRF_TOKEN,
            }),
        }).catch(() => {});
    }

    function init() {
        bindEditableFields();
        bindGalleryEditors();
        bindImageZones();
        bindHeroBackground();
        bindCtaLinks();
        bindChrome();
        initSortable();
        preventFormSubmit();
        clientLog('init', { sections: sections.length });
    }

    function stripTags(html) {
        const d = document.createElement('div');
        d.innerHTML = html;
        return (d.textContent || '').trim();
    }

    function bindEditableFields() {
        canvas.querySelectorAll('.se-editable-trigger:not(.se-editable-cta)').forEach((el) => {
            const open = () => openTextEditor(el);
            el.addEventListener('click', open);
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open();
                }
            });
        });
    }

    function openTextEditor(el) {
        const wrap = el.closest('.se-section-wrap');
        const sec = wrap ? getSection(wrap.dataset.blockId) : null;
        if (!sec) return;
        const field = el.dataset.field;
        if (!field || !window.RichTextEditor) return;
        const mode = el.dataset.editMode || 'rich';
        const current = sec.config[field] || '';
        window.RichTextEditor.open({
            mode,
            content: current,
            onSave: (html) => {
                if (mode === 'plain') {
                    sec.config[field] = stripTags(html);
                    el.textContent = sec.config[field] || 'Click to edit';
                } else {
                    sec.config[field] = html;
                    if (html) {
                        el.innerHTML = html;
                    } else {
                        el.innerHTML = '<span class="se-edit-hint">Click to edit text…</span>';
                    }
                }
            },
        });
    }

    function bindGalleryEditors() {
        canvas.querySelectorAll('[data-gallery-editor]').forEach((editorEl) => {
            const wrap = editorEl.closest('.se-section-wrap');
            const sec = wrap ? getSection(wrap.dataset.blockId) : null;
            if (!sec) return;
            if (!Array.isArray(sec.config.photos)) {
                sec.config.photos = [];
            }

            editorEl.querySelector('.se-gallery-add')?.addEventListener('click', () => {
                window.MediaPicker?.openLibrary((paths) => {
                    const list = Array.isArray(paths) ? paths : [paths];
                    list.forEach((path) => {
                        sec.config.photos.push({ src: path, alt: '', caption: '', title: '' });
                    });
                    renderGalleryGrid(editorEl, sec);
                }, { multiple: true });
            });

            const grid = editorEl.querySelector('[data-gallery-grid]');
            if (grid && typeof Sortable !== 'undefined') {
                Sortable.create(grid, {
                    animation: 150,
                    draggable: '.se-gallery-item',
                    onEnd: () => syncGalleryFromDom(editorEl, sec),
                });
            }

            editorEl.addEventListener('click', (e) => {
                const btn = e.target.closest('.se-gallery-remove');
                if (!btn) return;
                const item = btn.closest('.se-gallery-item');
                const index = parseInt(item?.dataset.index || '-1', 10);
                if (index >= 0) {
                    sec.config.photos.splice(index, 1);
                    renderGalleryGrid(editorEl, sec);
                }
            });
        });
    }

    function renderGalleryGrid(editorEl, sec) {
        const grid = editorEl.querySelector('[data-gallery-grid]');
        if (!grid) return;
        grid.innerHTML = '';
        (sec.config.photos || []).forEach((photo, i) => {
            const url = '/' + String(photo.src || '').replace(/^\//, '');
            const item = document.createElement('div');
            item.className = 'se-gallery-item';
            item.dataset.index = String(i);
            item.innerHTML = `<img src="${escAttr(url)}" alt=""><button type="button" class="se-gallery-remove" title="Remove">×</button>`;
            grid.appendChild(item);
        });
    }

    function syncGalleryFromDom(editorEl, sec) {
        const grid = editorEl.querySelector('[data-gallery-grid]');
        if (!grid) return;
        const reordered = [];
        grid.querySelectorAll('.se-gallery-item').forEach((item) => {
            const i = parseInt(item.dataset.index || '-1', 10);
            if (i >= 0 && sec.config.photos[i]) {
                reordered.push(sec.config.photos[i]);
            }
        });
        sec.config.photos = reordered;
        renderGalleryGrid(editorEl, sec);
    }

    function bindImageZones() {
        canvas.querySelectorAll('.se-image-zone, .se-image-btn:not(.se-hero-bg-btn)').forEach((el) => {
            const zone = el.classList.contains('se-image-zone') ? el : el.closest('.se-image-zone');
            if (!zone) return;
            const btn = zone.querySelector('.se-image-btn');
            (btn || zone).addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                pickImageForZone(zone);
            });
        });
    }

    function bindHeroBackground() {
        canvas.querySelectorAll('.se-hero-bg-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const hero = btn.closest('.hero');
                const wrap = btn.closest('.se-section-wrap');
                if (!hero || !wrap) return;
                window.MediaPicker?.openLibrary((path) => {
                    const sec = getSection(wrap.dataset.blockId);
                    if (sec) sec.config.background_image = path;
                    const url = '/' + path.replace(/^\//, '');
                    hero.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('${url}')`;
                    hero.dataset.bgPath = path;
                });
            });
        });
    }

    function pickImageForZone(zone) {
        const wrap = zone.closest('.se-section-wrap');
        if (!wrap) return;
        const field = zone.dataset.field;
        window.MediaPicker?.openLibrary((path) => {
            const sec = getSection(wrap.dataset.blockId);
            if (sec && field) sec.config[field] = path;
            zone.dataset.path = path;
            const url = '/' + path.replace(/^\//, '');
            let img = zone.querySelector('img');
            const placeholder = zone.querySelector('.se-image-placeholder');
            if (!img) {
                img = document.createElement('img');
                img.className = zone.querySelector('img')?.className || '';
                if (placeholder) placeholder.replaceWith(img);
                else zone.insertBefore(img, zone.querySelector('.se-image-btn'));
            }
            img.src = url;
            if (field === 'logo_image') img.classList.add('logo-img-display');
        });
    }

    function bindCtaLinks() {
        canvas.querySelectorAll('.se-editable-cta').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                openTextEditor(el);
            });
            el.addEventListener('dblclick', (e) => {
                e.preventDefault();
                const wrap = el.closest('.se-section-wrap');
                const sec = wrap ? getSection(wrap.dataset.blockId) : null;
                const linkField = el.dataset.linkField;
                const current = el.dataset.href || sec?.config[linkField] || '/';
                const url = prompt('Button link URL:', current);
                if (url === null) return;
                el.dataset.href = url;
                el.setAttribute('href', url);
                if (sec && linkField) sec.config[linkField] = url;
            });
            el.title = 'Click to edit text · Double-click to edit link';
        });
    }

    function bindChrome() {
        canvas.querySelectorAll('.se-section-wrap').forEach((wrap) => {
            wrap.querySelector('.se-toggle-vis')?.addEventListener('click', () => {
                const sec = getSection(wrap.dataset.blockId);
                if (!sec) return;
                sec.active = !sec.active;
                wrap.dataset.active = sec.active ? '1' : '0';
                wrap.classList.toggle('se-section-hidden', !sec.active);
                const btn = wrap.querySelector('.se-toggle-vis');
                if (btn) btn.textContent = sec.active ? '👁' : '👁‍🗨';
            });

            wrap.querySelector('.se-settings-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleSettingsPopover(wrap);
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.se-popover') && !e.target.closest('.se-settings-btn')) {
                closePopover();
            }
        });
    }

    function toggleSettingsPopover(wrap) {
        if (openPopover && openPopover.parentElement === wrap) {
            closePopover();
            return;
        }
        closePopover();
        const type = wrap.dataset.blockType;
        const defs = sectionSettings[type];
        if (!defs?.length) return;

        const sec = getSection(wrap.dataset.blockId);
        if (!sec) return;

        wrap.classList.add('se-popover-open');
        const pop = document.createElement('div');
        pop.className = 'se-popover';
        pop.innerHTML = '<h4>Section Settings</h4>';
        const form = document.createElement('form');
        defs.forEach((def) => {
            const val = sec.config[def.key];
            if (def.type === 'checkbox') {
                form.innerHTML += `<div class="checkbox-row"><label><input type="checkbox" name="${def.key}" ${val ? 'checked' : ''}> ${esc(def.label)}</label></div>`;
            } else if (def.type === 'number') {
                form.innerHTML += `<label>${esc(def.label)}</label><input type="number" name="${def.key}" value="${escAttr(String(val ?? ''))}">`;
            }
        });
        form.innerHTML += '<div class="se-popover-actions"><button type="submit" class="btn btn-sm">Apply</button><button type="button" class="btn btn-sm btn-muted se-popover-close">Close</button></div>';
        pop.appendChild(form);
        wrap.appendChild(pop);
        openPopover = pop;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            defs.forEach((def) => {
                const input = form.querySelector(`[name="${def.key}"]`);
                if (!input) return;
                if (def.type === 'checkbox') sec.config[def.key] = input.checked;
                else if (def.type === 'number') sec.config[def.key] = Number(input.value);
                else sec.config[def.key] = input.value;
            });
            closePopover();
            reloadPage();
        });
        pop.querySelector('.se-popover-close')?.addEventListener('click', closePopover);
    }

    function closePopover() {
        if (openPopover) {
            openPopover.closest('.se-section-wrap')?.classList.remove('se-popover-open');
            openPopover.remove();
            openPopover = null;
        }
    }

    function reloadPage() {
        window.location.reload();
    }

    function initSortable() {
        if (typeof Sortable === 'undefined') return;
        sortable = Sortable.create(canvas, {
            handle: '.se-drag-handle',
            animation: 150,
            draggable: '.se-section-wrap',
            onEnd: (evt) => {
                const moved = sections.splice(evt.oldIndex, 1)[0];
                sections.splice(evt.newIndex, 0, moved);
            },
        });
    }

    function preventFormSubmit() {
        canvas.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (e) => e.preventDefault());
        });
    }

    function collectFromDom() {
        canvas.querySelectorAll('.se-editable-cta').forEach((el) => {
            const wrap = el.closest('.se-section-wrap');
            const sec = wrap ? getSection(wrap.dataset.blockId) : null;
            if (!sec) return;
            const linkField = el.dataset.linkField;
            if (linkField) sec.config[linkField] = el.dataset.href || sec.config[linkField] || '';
        });
    }

    async function apiFetch(url, options = {}) {
        if (window.adminApiFetch) return window.adminApiFetch(url, options);
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) },
            ...options,
        });
        const text = await res.text();
        return { res, data: text ? JSON.parse(text) : {} };
    }

    saveBtn?.addEventListener('click', async () => {
        collectFromDom();
        saveBtn.disabled = true;
        setStatus('Saving homepage…', '');
        try {
            const layout = sectionsToLayout();
            const { data } = await apiFetch('/api/page-builder.php?action=save_layout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ viewport: 'desktop', layout, _csrf: window.CSRF_TOKEN }),
            });
            if (!data.success) throw new Error(data.message);
            clientLog('save success', { rows: layout.rows.length });
            setStatus('Homepage saved!', 'success');
        } catch (err) {
            setStatus(err.message || 'Save failed', 'error');
        }
        saveBtn.disabled = false;
    });

    resetBtn?.addEventListener('click', async () => {
        if (!confirm('Reset the homepage to the original default layout? This replaces all sections and saves immediately.')) return;
        setStatus('Resetting…', '');
        try {
            const { data } = await apiFetch('/api/page-builder.php?action=reset_layout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ _csrf: window.CSRF_TOKEN }),
            });
            if (!data.success) throw new Error(data.message);
            window.location.reload();
        } catch (err) {
            setStatus(err.message || 'Reset failed', 'error');
        }
    });

    function setStatus(msg, type) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'alert ' + (type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : '');
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function escAttr(s) {
        return esc(s).replace(/"/g, '&quot;');
    }

    init();
});
