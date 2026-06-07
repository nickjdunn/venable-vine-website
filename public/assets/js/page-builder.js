document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('builder-canvas');
    const editor = document.getElementById('builder-editor');
    const saveBtn = document.getElementById('save-page-btn');
    const statusEl = document.getElementById('builder-status');
    let sections = window.BUILDER_SECTIONS || [];
    let selectedId = null;

    let sortableInstance = null;

    function initSortable() {
        if (typeof Sortable === 'undefined' || !canvas) return;
        if (sortableInstance) {
            sortableInstance.destroy();
            sortableInstance = null;
        }
        sortableInstance = Sortable.create(canvas, {
            handle: '.sortable-handle',
            animation: 150,
            onEnd: (evt) => {
                const moved = sections.splice(evt.oldIndex, 1)[0];
                sections.splice(evt.newIndex, 0, moved);
            }
        });
    }

    function renderCanvas() {
        if (!canvas) return;
        canvas.innerHTML = '';
        if (!sections.length) {
            canvas.innerHTML = '<p class="builder-preview-note">Add sections from the left panel to build your homepage.</p>';
            return;
        }
        sections.forEach((sec) => {
            const el = document.createElement('div');
            el.className = 'builder-section' + (sec.id === selectedId ? ' selected' : '') + (!sec.is_active ? ' inactive' : '');
            el.dataset.id = sec.id;
            el.innerHTML = `<span class="sortable-handle">☰</span>
                <div><strong>${sec.label || sec.section_type}</strong>
                <div class="builder-section-type">${sec.section_type}</div></div>
                <label style="margin-left:auto;font-size:0.85rem;"><input type="checkbox" class="toggle-active" ${sec.is_active ? 'checked' : ''}> Active</label>`;
            el.addEventListener('click', (e) => {
                if (e.target.classList.contains('toggle-active')) return;
                selectSection(sec.id);
            });
            el.querySelector('.toggle-active')?.addEventListener('change', (e) => {
                sec.is_active = e.target.checked;
                el.classList.toggle('inactive', !sec.is_active);
            });
            canvas.appendChild(el);
        });
        initSortable();
    }

    function selectSection(id) {
        selectedId = id;
        renderCanvas();
        const sec = sections.find(s => s.id === id);
        if (!sec || !editor) return;
        editor.innerHTML = buildEditorForm(sec);
        editor.querySelector('form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            sec.config = {};
            fd.forEach((val, key) => {
                if (key.startsWith('cfg_')) {
                    const k = key.slice(4);
                    if (e.target.querySelector(`[name="${key}"][type=checkbox]`)) {
                        sec.config[k] = e.target.querySelector(`[name="${key}"]`).checked;
                    } else {
                        sec.config[k] = val;
                    }
                }
            });
            e.target.querySelectorAll('input[type=checkbox][name^=cfg_]').forEach(cb => {
                sec.config[cb.name.slice(4)] = cb.checked;
            });
            setStatus('Section updated (click Save Page to publish)', 'success');
        });
    }

    function buildEditorForm(sec) {
        const c = sec.config || {};
        const fields = {
            hero: [
                ['title', 'Title', 'text'],
                ['subtitle', 'Subtitle', 'textarea'],
                ['background_image', 'Background Image Path', 'text'],
                ['logo_image', 'Logo Image Path', 'text'],
                ['cta_text', 'Button Text', 'text'],
                ['cta_link', 'Button Link', 'text'],
            ],
            story: [
                ['title', 'Title', 'text'],
                ['paragraph1', 'Paragraph 1', 'textarea'],
                ['paragraph2', 'Paragraph 2', 'textarea'],
                ['image', 'Image Path', 'text'],
            ],
            menu_preview: [
                ['title', 'Title', 'text'],
                ['coming_soon_title', 'Coming Soon Title', 'text'],
                ['coming_soon_text', 'Coming Soon Text', 'textarea'],
                ['show_coming_soon', 'Show Coming Soon Box', 'checkbox'],
                ['link_to_full_menu', 'Link to Full Menu', 'checkbox'],
            ],
            gallery: [['title', 'Title', 'text']],
            reviews: [['title', 'Title', 'text']],
            find_us: [
                ['title', 'Title', 'text'],
                ['text', 'Footer Text', 'text'],
                ['max_events', 'Max Events to Show', 'number'],
                ['show_facebook_button', 'Show Facebook Button', 'checkbox'],
            ],
            contact: [
                ['title', 'Title', 'text'],
                ['subtitle', 'Subtitle', 'text'],
                ['show_contact', 'Show Contact Form', 'checkbox'],
                ['show_review', 'Show Review Form', 'checkbox'],
            ],
            newsletter: [
                ['title', 'Title', 'text'],
                ['subtitle', 'Subtitle', 'textarea'],
            ],
            social: [['title', 'Title', 'text']],
            custom_html: [['html', 'HTML Content', 'textarea']],
        };
        const defs = fields[sec.section_type] || [];
        let html = `<h3>Edit: ${sec.label}</h3><form>`;
        defs.forEach(([key, label, type]) => {
            const val = c[key] ?? '';
            if (type === 'textarea') {
                html += `<label>${label}</label><textarea name="cfg_${key}">${escapeHtml(String(val))}</textarea>`;
            } else if (type === 'checkbox') {
                html += `<div class="checkbox-row"><label><input type="checkbox" name="cfg_${key}"${val ? ' checked' : ''}> ${label}</label></div>`;
            } else {
                html += `<label>${label}</label><input type="${type}" name="cfg_${key}" value="${escapeAttr(String(val))}">`;
            }
        });
        html += '<p style="font-size:0.85rem;color:#666;">Upload images in Settings, then paste the path (e.g. uploads/site/abc.jpg) or use Page Builder after uploading via Settings.</p>';
        html += '<button type="submit" class="btn btn-sm">Apply to Section</button></form>';
        return html;
    }

    function escapeHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escapeAttr(s) { return escapeHtml(s).replace(/"/g,'&quot;'); }

    document.querySelectorAll('[data-add-section]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const type = btn.dataset.addSection;
            const fd = new FormData();
            fd.append('action', 'add_section');
            fd.append('section_type', type);
            fd.append('_csrf', window.CSRF_TOKEN);
            const res = await fetch('/api/page-builder.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success && data.section) {
                sections.push(data.section);
                selectSection(data.section.id);
                renderCanvas();
                setStatus('Section added', 'success');
            } else {
                setStatus(data.message || 'Error adding section', 'error');
            }
        });
    });

    saveBtn?.addEventListener('click', async () => {
        saveBtn.disabled = true;
        setStatus('Saving...', '');
        const payload = sections.map((s, i) => ({
            id: s.id,
            section_type: s.section_type,
            is_active: s.is_active,
            config: s.config,
        }));
        const res = await fetch('/api/page-builder.php?action=save_sections', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
            body: JSON.stringify({ sections: payload, _csrf: window.CSRF_TOKEN }),
        });
        const data = await res.json();
        setStatus(data.message || (data.success ? 'Saved!' : 'Error'), data.success ? 'success' : 'error');
        saveBtn.disabled = false;
    });

    function setStatus(msg, type) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'alert ' + (type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : '');
    }

    renderCanvas();
    if (sections.length) selectSection(sections[0].id);
});
