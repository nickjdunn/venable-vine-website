document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('editor-canvas');
    const saveBtn = document.getElementById('save-layout-btn');
    const resetBtn = document.getElementById('reset-layout-btn');
    const statusEl = document.getElementById('se-status');
    const saveToast = document.getElementById('se-save-toast');
    let saveToastTimer = null;

    if (!canvas || !window.EDITOR_INITIAL?.success) {
        return;
    }

    const bootstrap = window.EDITOR_INITIAL;
    const sectionSettings = bootstrap.sectionSettings || {};
    const availableForms = bootstrap.forms || [];
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
        bindFormsEditors();
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
        const hint = editorEl.querySelector('[data-gallery-hint]');
        if (hint) {
            hint.hidden = (sec.config.photos || []).length > 0;
        }
    }

    function bindFormsEditors() {
        canvas.querySelectorAll('[data-forms-editor]').forEach((editorEl) => {
            const wrap = editorEl.closest('.se-section-wrap');
            const sec = wrap ? getSection(wrap.dataset.blockId) : null;
            if (!sec) return;
            if (!Array.isArray(sec.config.forms)) {
                sec.config.forms = [];
            }

            editorEl.querySelector('.se-forms-add')?.addEventListener('click', () => {
                openFormPicker((formId) => {
                    const form = availableForms.find((f) => f.id === formId);
                    sec.config.forms.push({
                        form_id: formId,
                        tab_label: form?.name || 'Form',
                    });
                    renderFormsList(editorEl, sec);
                });
            });

            editorEl.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.se-form-remove');
                if (removeBtn) {
                    const slot = removeBtn.closest('.se-form-slot');
                    const index = parseInt(slot?.dataset.formIndex || '-1', 10);
                    if (index >= 0) {
                        sec.config.forms.splice(index, 1);
                        renderFormsList(editorEl, sec);
                    }
                    return;
                }
                const tabLabel = e.target.closest('.se-form-tab-label');
                if (tabLabel) {
                    const slot = tabLabel.closest('.se-form-slot');
                    const index = parseInt(slot?.dataset.formIndex || '-1', 10);
                    if (index < 0) return;
                    window.RichTextEditor?.open({
                        mode: 'plain',
                        content: sec.config.forms[index]?.tab_label || '',
                        onSave: (html) => {
                            sec.config.forms[index].tab_label = stripTags(html);
                            tabLabel.textContent = sec.config.forms[index].tab_label || 'Tab label';
                        },
                    });
                }
            });

            const list = editorEl.querySelector('[data-forms-list]');
            if (list && typeof Sortable !== 'undefined') {
                Sortable.create(list, {
                    animation: 150,
                    draggable: '.se-form-slot',
                    onEnd: () => syncFormsFromDom(editorEl, sec),
                });
            }
        });
    }

    function syncFormsFromDom(editorEl, sec) {
        const reordered = [];
        editorEl.querySelectorAll('.se-form-slot').forEach((slot) => {
            const index = parseInt(slot.dataset.formIndex || '-1', 10);
            if (index >= 0 && sec.config.forms[index]) {
                reordered.push(sec.config.forms[index]);
            }
        });
        sec.config.forms = reordered;
        renderFormsList(editorEl, sec);
    }

    function renderFormsList(editorEl, sec) {
        const list = editorEl.querySelector('[data-forms-list]');
        const emptyHint = editorEl.querySelector('.se-forms-empty-hint');
        if (!list) return;
        list.innerHTML = '';
        (sec.config.forms || []).forEach((slot, i) => {
            const form = availableForms.find((f) => f.id === slot.form_id);
            const fields = (form?.fields || []).map((f) => `<span>${esc(f.label)}</span>`).join('');
            const item = document.createElement('div');
            item.className = 'se-form-slot';
            item.dataset.formIndex = String(i);
            item.dataset.formId = String(slot.form_id);
            item.innerHTML = `<div class="se-form-slot-header">
                <span class="se-editable se-editable-trigger se-form-tab-label" role="button" tabindex="0">${esc(slot.tab_label || form?.name || 'Form')}</span>
                <button type="button" class="se-form-remove" title="Remove form">×</button>
            </div>
            <div class="se-form-preview-panel">
                <strong>${esc(form?.name || 'Unknown form')}</strong>
                <div class="se-fake-form">${fields}</div>
                <span class="se-form-preview-btn">${esc(form?.button_text || 'Submit')}</span>
            </div>`;
            list.appendChild(item);
        });
        if (emptyHint) {
            emptyHint.hidden = (sec.config.forms || []).length > 0;
        }
    }

    function openFormPicker(onSelect) {
        let modal = document.getElementById('se-form-picker-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'se-form-picker-modal';
            modal.className = 'se-form-picker-modal';
            modal.innerHTML = `<div class="se-form-picker-backdrop" data-close></div>
                <div class="se-form-picker-dialog">
                    <h3>Choose a Form</h3>
                    <div class="se-form-picker-list" data-list></div>
                    <p class="se-form-picker-note">Manage forms in <a href="/admin/contacts.php?view=forms" target="_blank">Contacts → Form Builder</a></p>
                    <button type="button" class="btn btn-muted btn-sm" data-close>Cancel</button>
                </div>`;
            document.body.appendChild(modal);
            modal.querySelectorAll('[data-close]').forEach((el) => {
                el.addEventListener('click', () => { modal.hidden = true; });
            });
        }
        const listEl = modal.querySelector('[data-list]');
        listEl.innerHTML = '';
        if (!availableForms.length) {
            listEl.innerHTML = '<p>No forms yet. Create one in Contacts → Form Builder.</p>';
        } else {
            availableForms.forEach((form) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'se-form-picker-item';
                btn.textContent = form.name;
                btn.addEventListener('click', () => {
                    onSelect(form.id);
                    modal.hidden = true;
                });
                listEl.appendChild(btn);
            });
        }
        modal.hidden = false;
    }

    function applySectionStyles(wrap, sec) {
        const section = wrap?.querySelector('.se-section-body > section');
        if (!section || !sec) return;
        const c = sec.config;
        const styles = [];
        const existing = section.getAttribute('style') || '';
        const bgImage = existing.match(/background-image:\s[^;]+/);
        if (bgImage) styles.push(bgImage[0]);
        if (c.background_color) styles.push(`background-color:${c.background_color}`);
        if (c.padding_top !== undefined && c.padding_top !== '' && c.padding_top !== null) {
            styles.push(`padding-top:${c.padding_top}rem`);
        }
        if (c.padding_bottom !== undefined && c.padding_bottom !== '' && c.padding_bottom !== null) {
            styles.push(`padding-bottom:${c.padding_bottom}rem`);
        }
        section.setAttribute('style', styles.join(';'));
    }

    const STYLE_SETTING_KEYS = ['background_color', 'padding_top', 'padding_bottom'];

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
        form.className = 'se-popover-form';
        defs.forEach((def) => {
            const val = sec.config[def.key];
            if (def.type === 'checkbox') {
                form.innerHTML += `<div class="checkbox-row"><label><input type="checkbox" name="${def.key}" ${val ? 'checked' : ''}> ${esc(def.label)}</label></div>`;
            } else if (def.type === 'number') {
                const min = def.min !== undefined ? ` min="${def.min}"` : '';
                const max = def.max !== undefined ? ` max="${def.max}"` : '';
                const step = def.step !== undefined ? ` step="${def.step}"` : '';
                form.innerHTML += `<label>${esc(def.label)}</label><input type="number" name="${def.key}" value="${escAttr(String(val ?? ''))}"${min}${max}${step}>`;
            } else if (def.type === 'color') {
                form.innerHTML += `<label>${esc(def.label)}</label><input type="color" name="${def.key}" value="${escAttr(String(val || '#fdf6e8'))}">`;
            }
        });
        form.innerHTML += '<div class="se-popover-actions"><button type="submit" class="btn btn-sm">Apply</button><button type="button" class="btn btn-sm btn-muted se-popover-close">Close</button></div>';
        pop.appendChild(form);
        wrap.appendChild(pop);
        openPopover = pop;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            let needsReload = false;
            defs.forEach((def) => {
                const input = form.querySelector(`[name="${def.key}"]`);
                if (!input) return;
                if (def.type === 'checkbox') sec.config[def.key] = input.checked;
                else if (def.type === 'number') sec.config[def.key] = input.value === '' ? '' : Number(input.value);
                else sec.config[def.key] = input.value;
                if (!STYLE_SETTING_KEYS.includes(def.key)) {
                    needsReload = true;
                }
            });
            applySectionStyles(wrap, sec);
            closePopover();
            if (needsReload) reloadPage();
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
        saveBtn.classList.add('is-saving');
        saveBtn.textContent = 'Saving…';
        hideSaveToast();
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
            saveBtn.classList.remove('is-saving');
            saveBtn.classList.add('is-saved');
            saveBtn.textContent = '✓ Saved!';
            showSaveToast('Homepage saved successfully!', 'success');
            setStatus('Homepage saved!', 'success');
            setTimeout(() => {
                saveBtn.classList.remove('is-saved');
                saveBtn.textContent = 'Save Page';
            }, 3000);
        } catch (err) {
            saveBtn.classList.remove('is-saving');
            saveBtn.textContent = 'Save Page';
            showSaveToast(err.message || 'Save failed', 'error');
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

    function showSaveToast(message, type) {
        if (!saveToast) return;
        clearTimeout(saveToastTimer);
        saveToast.hidden = false;
        saveToast.textContent = message;
        saveToast.className = 'se-save-toast ' + (type === 'success' ? 'is-success' : 'is-error');
        saveToastTimer = setTimeout(() => hideSaveToast(), type === 'success' ? 4000 : 6000);
    }

    function hideSaveToast() {
        if (!saveToast) return;
        saveToast.hidden = true;
        saveToast.textContent = '';
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function escAttr(s) {
        return esc(s).replace(/"/g, '&quot;');
    }

    init();
});
