document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('pb-canvas');
    const inspector = document.getElementById('pb-inspector');
    const statusEl = document.getElementById('pb-status');
    const saveBtn = document.getElementById('save-layout-btn');

    let pageLayout = { rows: [] };
    let builderData = {};
    let selected = null;
    let rowSortable = null;

    uid.counter = 1;
    function uid(prefix) { return `${prefix}_${Date.now()}_${uid.counter++}`; }

    function debugIngest(hypothesisId, message, data = {}) {
        // #region agent log
        fetch('http://127.0.0.1:7709/ingest/55c5d319-00f7-40e2-8cfc-95a4896d60d5', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '684396' },
            body: JSON.stringify({ sessionId: '684396', hypothesisId, location: 'page-builder.js', message, data, timestamp: Date.now() }),
        }).catch(() => {});
        // #endregion
    }

    async function load() {
        try {
            debugIngest('C', 'load start', {});
            const { data } = await apiFetch('/api/page-builder.php?action=get_builder_data');
            builderData = data;
            if (!builderData.success) {
                showLoadError(builderData.message || 'Load failed');
                debugIngest('C', 'load failed', { message: builderData.message });
                return;
            }
            pageLayout = builderData.layout?.rows?.length
                ? builderData.layout
                : (builderData.layout_desktop?.rows?.length ? builderData.layout_desktop : { rows: [] });
            debugIngest('C', 'load success', { rows: pageLayout.rows?.length || 0 });
            renderPalette();
            renderCanvas();
        } catch (err) {
            debugIngest('C', 'load exception', { error: err.message });
            showLoadError(err.message || 'Could not load page builder');
        }
    }

    async function apiFetch(url, options = {}) {
        if (window.adminApiFetch) {
            return window.adminApiFetch(url, options);
        }
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) },
            ...options,
        });
        const text = await res.text();
        let data;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (_) {
            throw new Error('Server returned invalid JSON (HTTP ' + res.status + ')');
        }
        return { res, data };
    }

    function showLoadError(message) {
        setStatus(message, 'error');
        canvas.innerHTML = `<div class="pb-empty">
            <p><strong>Page Builder failed to load</strong></p>
            <p>${esc(message)}</p>
            <p><a href="/admin/debug.php">Open Debug</a> for details.</p>
        </div>`;
    }

    function newFullRow(block = null) {
        const row = { id: uid('row'), layout: 'full', columns: [{ id: uid('col'), blocks: [] }] };
        if (block) row.columns[0].blocks.push(block);
        return row;
    }

    function newColumnRow() {
        return {
            id: uid('row'),
            layout: 'columns',
            columns: [
                { id: uid('col'), blocks: [] },
                { id: uid('col'), blocks: [] },
                { id: uid('col'), blocks: [] },
            ],
        };
    }

    function newBlock(type) {
        return { id: uid('block'), type, config: defaultConfig(type), active: true };
    }

    function defaultConfig(type) {
        const defaults = {
            title: { text: 'Section Title', level: 'h2', align: 'center' },
            text: { content: 'Your text here...', align: 'left' },
            image: { src: '', alt: '', caption: '' },
            button: { text: 'Learn More', link: '/find-us.php', align: 'center' },
            spacer: { height: 40 },
            gallery: { title: 'A Glimpse of Our Goodness' },
            menu_category: { category_id: (builderData.categories || [])[0]?.id || 0, title: '' },
            hero: { title: 'Freshly Squeezed. Family Made.', subtitle: '', background_image: '', logo_image: '', cta_text: 'Find The Truck Today', cta_link: '/find-us.php' },
            story: { title: 'Our Story', paragraph1: '', paragraph2: '', image: '' },
            menu_preview: { title: 'Taste the Sunshine', show_coming_soon: true, coming_soon_title: 'Coming Soon!', coming_soon_text: '', link_to_full_menu: true },
            reviews: { title: 'What Our Customers Say' },
            find_us: { title: 'Where to Find Us', text: '', show_facebook_button: true, max_events: 5 },
            contact: { title: 'Get In Touch', subtitle: '', show_contact: true, show_review: true },
            newsletter: { title: 'Stay in the Loop', subtitle: '' },
            social: { title: 'Follow Us' },
        };
        return { ...(defaults[type] || {}) };
    }

    function blockLabel(type) {
        return builderData.block_types?.basic?.[type] || builderData.block_types?.modules?.[type] || type;
    }

    function blockPreview(block) {
        const c = block.config || {};
        switch (block.type) {
            case 'hero': return c.title || 'Hero Banner';
            case 'story': return c.title || 'Story';
            case 'title': return c.text || 'Title';
            case 'text': return (c.content || '').slice(0, 60) || 'Text';
            case 'image': return (c.src || 'Image').split('/').pop() || 'Image';
            case 'button': return c.text || 'Button';
            case 'menu_preview': return c.title || 'Menu Preview';
            case 'gallery': return c.title || 'Gallery';
            case 'reviews': return c.title || 'Reviews';
            case 'find_us': return c.title || 'Find Us';
            case 'contact': return c.title || 'Contact';
            case 'newsletter': return c.title || 'Newsletter';
            case 'social': return c.title || 'Social';
            case 'spacer': return `Spacer (${c.height || 40}px)`;
            default: return blockLabel(block.type);
        }
    }

    function renderPalette() {
        const basic = document.getElementById('palette-basic');
        const modules = document.getElementById('palette-modules');
        basic.innerHTML = '';
        modules.innerHTML = '';
        Object.entries(builderData.block_types?.modules || {}).forEach(([type, label]) => {
            modules.appendChild(paletteBtn(type, label));
        });
        Object.entries(builderData.block_types?.basic || {}).forEach(([type, label]) => {
            basic.appendChild(paletteBtn(type, label));
        });
    }

    function paletteBtn(type, label) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-outline pb-palette-btn pb-palette-section';
        b.textContent = '+ ' + label;
        b.draggable = true;
        b.dataset.blockType = type;
        b.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('blockType', type);
            e.dataTransfer.effectAllowed = 'copy';
        });
        b.addEventListener('click', () => addSection(type));
        return b;
    }

    function addSection(type) {
        const block = newBlock(type);
        pageLayout.rows.push(newFullRow(block));
        renderCanvas();
        const row = pageLayout.rows[pageLayout.rows.length - 1];
        selectBlock(row.id, row.columns[0].id, block.id);
    }

    function renderCanvas() {
        if (rowSortable) {
            rowSortable.destroy();
            rowSortable = null;
        }
        canvas.innerHTML = '';
        if (!pageLayout.rows.length) {
            canvas.innerHTML = '<div class="pb-empty"><p>Add page sections from the left panel to build your homepage.</p></div>';
            return;
        }
        pageLayout.rows.forEach((row, rowIndex) => canvas.appendChild(renderRow(row, rowIndex)));
        if (typeof Sortable !== 'undefined') {
            rowSortable = Sortable.create(canvas, {
                handle: '.pb-row-handle',
                animation: 150,
                onEnd: (evt) => {
                    const moved = pageLayout.rows.splice(evt.oldIndex, 1)[0];
                    pageLayout.rows.splice(evt.newIndex, 0, moved);
                    renderCanvas();
                },
            });
        }
    }

    function renderRow(row, rowIndex) {
        const isFull = row.layout !== 'columns';
        const rowEl = document.createElement('div');
        rowEl.className = 'pb-row' + (isFull ? ' pb-row--full' : ' pb-row--columns');
        rowEl.innerHTML = `<div class="pb-row-toolbar">
            <span class="pb-row-handle" title="Drag to reorder">☰</span>
            <span class="pb-row-label">${isFull ? 'Section' : '3-Column Row'} ${rowIndex + 1}</span>
            <button type="button" class="btn btn-sm btn-muted pb-row-up">↑</button>
            <button type="button" class="btn btn-sm btn-muted pb-row-down">↓</button>
            <button type="button" class="btn btn-sm btn-danger pb-row-del">Delete</button>
        </div>`;
        const body = document.createElement('div');
        body.className = isFull ? 'pb-section-body' : 'pb-row-cols';
        if (isFull) {
            const col = row.columns[0];
            if (!col.blocks.length) {
                body.innerHTML = '<p class="pb-hint">Empty section</p>';
            } else {
                col.blocks.forEach((block) => body.appendChild(renderSectionCard(block, row.id, col.id)));
            }
        } else {
            row.columns.forEach((col, colIndex) => {
                const colEl = document.createElement('div');
                colEl.className = 'pb-col';
                colEl.innerHTML = `<div class="pb-col-label">Column ${colIndex + 1}</div>`;
                const list = document.createElement('div');
                list.className = 'pb-block-list';
                col.blocks.forEach((block) => list.appendChild(renderBlockChip(block, row.id, col.id)));
                colEl.appendChild(list);
                body.appendChild(colEl);
                initBlockSortable(list, row.id, col.id);
            });
        }
        rowEl.appendChild(body);
        rowEl.querySelector('.pb-row-up').addEventListener('click', () => moveRow(rowIndex, -1));
        rowEl.querySelector('.pb-row-down').addEventListener('click', () => moveRow(rowIndex, 1));
        rowEl.querySelector('.pb-row-del').addEventListener('click', () => {
            if (confirm('Delete this section?')) {
                pageLayout.rows.splice(rowIndex, 1);
                selected = null;
                inspector.innerHTML = '<p class="pb-hint">Click a section to edit it here.</p>';
                renderCanvas();
            }
        });
        return rowEl;
    }

    function renderSectionCard(block, rowId, colId) {
        const el = document.createElement('div');
        el.className = 'pb-section-card' + (selected?.blockId === block.id ? ' selected' : '') + (!block.active ? ' inactive' : '');
        el.innerHTML = `<div class="pb-section-card-head">
            <span class="pb-section-type">${esc(blockLabel(block.type))}</span>
            <label class="pb-block-active"><input type="checkbox" ${block.active ? 'checked' : ''}> Visible</label>
        </div><div class="pb-section-preview">${esc(blockPreview(block))}</div>`;
        el.addEventListener('click', (e) => {
            if (e.target.type === 'checkbox') return;
            selectBlock(rowId, colId, block.id);
        });
        el.querySelector('input').addEventListener('change', (e) => {
            block.active = e.target.checked;
            el.classList.toggle('inactive', !block.active);
        });
        return el;
    }

    function renderBlockChip(block, rowId, colId) {
        const el = document.createElement('div');
        el.className = 'pb-block' + (selected?.blockId === block.id ? ' selected' : '') + (!block.active ? ' inactive' : '');
        el.dataset.blockId = block.id;
        el.innerHTML = `<span class="pb-block-handle">☰</span><span class="pb-block-label">${esc(blockLabel(block.type))}</span>
            <label class="pb-block-active"><input type="checkbox" ${block.active ? 'checked' : ''}> On</label>`;
        el.addEventListener('click', (e) => {
            if (e.target.type === 'checkbox') return;
            selectBlock(rowId, colId, block.id);
        });
        el.querySelector('input').addEventListener('change', (e) => {
            block.active = e.target.checked;
            el.classList.toggle('inactive', !block.active);
        });
        return el;
    }

    function initBlockSortable(listEl, rowId, colId) {
        if (typeof Sortable === 'undefined') return;
        Sortable.create(listEl, {
            group: 'blocks',
            handle: '.pb-block-handle',
            animation: 150,
            onUpdate: (evt) => {
                const col = findColumn(rowId, colId);
                const moved = col.blocks.splice(evt.oldIndex, 1)[0];
                col.blocks.splice(evt.newIndex, 0, moved);
            },
        });
    }

    function moveRow(index, dir) {
        const target = index + dir;
        if (target < 0 || target >= pageLayout.rows.length) return;
        [pageLayout.rows[index], pageLayout.rows[target]] = [pageLayout.rows[target], pageLayout.rows[index]];
        renderCanvas();
    }

    function findColumn(rowId, colId) {
        const row = pageLayout.rows.find((r) => r.id === rowId);
        return row.columns.find((c) => c.id === colId);
    }

    function findBlock(rowId, colId, blockId) {
        return findColumn(rowId, colId)?.blocks.find((b) => b.id === blockId);
    }

    function selectBlock(rowId, colId, blockId) {
        selected = { rowId, colId, blockId };
        renderCanvas();
        const block = findBlock(rowId, colId, blockId);
        if (!block) return;
        inspector.innerHTML = buildInspector(block, rowId, colId);
        bindInspector(block, rowId, colId);
    }

    function buildInspector(block) {
        const c = block.config || {};
        let html = `<h3>Edit ${esc(blockLabel(block.type))}</h3><form id="inspector-form">`;
        const field = (key, lbl, type = 'text', opts = {}) => {
            const val = c[key] ?? '';
            if (type === 'textarea') html += `<label>${lbl}</label><textarea name="${key}">${esc(val)}</textarea>`;
            else if (type === 'checkbox') html += `<div class="checkbox-row"><label><input type="checkbox" name="${key}" ${val ? 'checked' : ''}> ${lbl}</label></div>`;
            else if (type === 'select') {
                html += `<label>${lbl}</label><select name="${key}">`;
                opts.options.forEach(([v, l]) => { html += `<option value="${escAttr(v)}"${String(val) === String(v) ? ' selected' : ''}>${esc(l)}</option>`; });
                html += '</select>';
            } else if (type === 'image') {
                html += window.MediaPicker ? window.MediaPicker.imageFieldHtml(key, lbl, val) : `<label>${lbl}</label><input type="text" name="${key}" value="${escAttr(val)}">`;
            } else html += `<label>${lbl}</label><input type="${type}" name="${key}" value="${escAttr(val)}">`;
        };
        switch (block.type) {
            case 'title': field('text', 'Title Text'); field('level', 'Size', 'select', { options: [['h1', 'Large'], ['h2', 'Medium'], ['h3', 'Small']] }); field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] }); break;
            case 'text': field('content', 'Content', 'textarea'); field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] }); break;
            case 'image': field('src', 'Image', 'image'); field('alt', 'Alt Text'); field('caption', 'Caption'); break;
            case 'button': field('text', 'Button Text'); field('link', 'Link URL'); break;
            case 'spacer': field('height', 'Height (px)', 'number'); break;
            case 'menu_category': field('category_id', 'Category', 'select', { options: (builderData.categories || []).map((cat) => [cat.id, cat.name]) }); field('title', 'Custom Title'); break;
            case 'gallery': field('title', 'Gallery Title'); html += '<p class="pb-hint">Upload photos in <a href="/admin/gallery.php">Media Library</a>.</p>'; break;
            case 'hero': field('title', 'Headline'); field('subtitle', 'Subtitle', 'textarea'); field('background_image', 'Background', 'image'); field('logo_image', 'Logo', 'image'); field('cta_text', 'Button Text'); field('cta_link', 'Button Link'); break;
            case 'story': field('title', 'Title'); field('paragraph1', 'Paragraph 1', 'textarea'); field('paragraph2', 'Paragraph 2', 'textarea'); field('image', 'Photo', 'image'); break;
            case 'menu_preview': field('title', 'Title'); field('coming_soon_title', 'Coming Soon Title'); field('coming_soon_text', 'Coming Soon Text', 'textarea'); field('show_coming_soon', 'Show Coming Soon', 'checkbox'); field('link_to_full_menu', 'Link to Menu Page', 'checkbox'); break;
            case 'find_us': field('title', 'Title'); field('text', 'Footer Text'); field('max_events', 'Max Events', 'number'); field('show_facebook_button', 'Show Facebook Button', 'checkbox'); break;
            case 'contact': field('title', 'Title'); field('subtitle', 'Subtitle'); field('show_contact', 'Contact Form', 'checkbox'); field('show_review', 'Review Form', 'checkbox'); break;
            case 'newsletter': field('title', 'Title'); field('subtitle', 'Subtitle', 'textarea'); break;
            case 'social': field('title', 'Title'); break;
            default: field('title', 'Title');
        }
        html += `<div class="form-actions"><button type="submit" class="btn btn-sm">Apply</button>
            <button type="button" class="btn btn-sm btn-danger" id="delete-block-btn">Delete</button></div></form>`;
        return html;
    }

    function bindInspector(block, rowId, colId) {
        document.getElementById('inspector-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            const form = e.target;
            form.querySelectorAll('[name]').forEach((input) => {
                if (input.type === 'checkbox') return;
                block.config[input.name] = input.type === 'number' ? Number(input.value) : input.value;
            });
            form.querySelectorAll('input[type=checkbox]').forEach((cb) => { block.config[cb.name] = cb.checked; });
            setStatus('Applied — click Save Page to publish', 'success');
            renderCanvas();
        });
        document.querySelectorAll('[data-media-picker]').forEach((field) => {
            window.MediaPicker?.bindField(field);
            field.querySelector('[data-media-input]')?.addEventListener('change', () => {
                block.config[field.querySelector('[data-media-input]').name] = field.querySelector('[data-media-input]').value;
            });
        });
        document.getElementById('delete-block-btn')?.addEventListener('click', () => {
            if (!confirm('Delete this block?')) return;
            const col = findColumn(rowId, colId);
            col.blocks = col.blocks.filter((b) => b.id !== block.id);
            if (!col.blocks.length) pageLayout.rows = pageLayout.rows.filter((r) => r.id !== rowId);
            selected = null;
            inspector.innerHTML = '<p class="pb-hint">Click a section to edit it here.</p>';
            renderCanvas();
        });
    }

    document.getElementById('add-column-row-btn')?.addEventListener('click', () => {
        pageLayout.rows.push(newColumnRow());
        renderCanvas();
    });

    saveBtn?.addEventListener('click', async () => {
        saveBtn.disabled = true;
        setStatus('Saving homepage...', '');
        try {
            const { data } = await apiFetch('/api/page-builder.php?action=save_layout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ viewport: 'desktop', layout: pageLayout, _csrf: window.CSRF_TOKEN }),
            });
            if (!data.success) throw new Error(data.message);
            debugIngest('C', 'save success', { rows: pageLayout.rows.length });
            setStatus('Homepage saved! View your live site.', 'success');
        } catch (err) {
            setStatus(err.message || 'Save failed', 'error');
        }
        saveBtn.disabled = false;
    });

    function setStatus(msg, type) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'alert ' + (type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : '');
    }

    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function escAttr(s) { return esc(s).replace(/"/g, '&quot;'); }

    load();
});
