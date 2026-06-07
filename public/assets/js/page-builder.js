document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('pb-canvas');
    const canvasWrap = document.getElementById('pb-canvas-wrap');
    const inspector = document.getElementById('pb-inspector');
    const statusEl = document.getElementById('pb-status');
    const saveBtn = document.getElementById('save-layout-btn');
    const resetMobileBtn = document.getElementById('reset-mobile-btn');
    const viewportNotice = document.getElementById('pb-viewport-notice');

    let viewport = 'desktop';
    let layouts = { desktop: { rows: [] }, mobile: { rows: [] } };
    let builderData = {};
    let selected = null;
    let rowSortable = null;
    let mobileCustomized = false;

    uid.counter = 1;
    function uid(prefix) { return `${prefix}_${Date.now()}_${uid.counter++}`; }

    async function load() {
        const res = await fetch('/api/page-builder.php?action=get_builder_data');
        builderData = await res.json();
        if (!builderData.success) {
            setStatus(builderData.message || 'Load failed', 'error');
            return;
        }
        layouts.desktop = builderData.layout_desktop?.rows?.length
            ? builderData.layout_desktop
            : { rows: [] };
        layouts.mobile = builderData.layout_mobile?.rows?.length
            ? builderData.layout_mobile
            : mobileFromDesktop(layouts.desktop);
        mobileCustomized = !!builderData.mobile_persisted;
        renderPalette();
        updateViewportUI();
        renderCanvas();
    }

    function mobileFromDesktop(desktop) {
        const src = desktop?.rows?.length ? desktop : { rows: [] };
        const rows = [];
        src.rows.forEach((row) => {
            if (row.layout === 'columns') {
                row.columns.forEach((col) => {
                    (col.blocks || []).forEach((block) => {
                        rows.push(newFullRow(block));
                    });
                });
            } else {
                (row.columns?.[0]?.blocks || []).forEach((block) => {
                    rows.push(newFullRow(block));
                });
            }
        });
        return { rows };
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
        return {
            id: uid('block'),
            type,
            config: defaultConfig(type),
            active: true,
        };
    }

    function defaultConfig(type) {
        const defaults = {
            title: { text: 'Section Title', level: 'h2', align: 'center' },
            text: { content: 'Your text here...', align: 'left' },
            image: { src: (builderData.asset_images || [])[0] || 'assets/images/ImagesOfFoodOffered.jpg', alt: '', caption: '' },
            button: { text: 'Learn More', link: '/find-us.php', align: 'center' },
            spacer: { height: 40 },
            gallery: { title: 'A Glimpse of Our Goodness' },
            menu_category: { category_id: (builderData.categories || [])[0]?.id || 0, title: '' },
            hero: { title: 'Freshly Squeezed. Family Made.', subtitle: '', background_image: 'assets/images/BerriesInhand.png', logo_image: 'assets/images/VenableandVineLogo.png', cta_text: 'Find The Truck Today', cta_link: '/find-us.php' },
            story: { title: 'Our Story', paragraph1: '', paragraph2: '', image: 'assets/images/FoodTruckPicture.jpg' },
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
        return builderData.block_types?.basic?.[type]
            || builderData.block_types?.modules?.[type]
            || type;
    }

    function blockPreview(block) {
        const c = block.config || {};
        switch (block.type) {
            case 'hero': return c.title || 'Hero Banner';
            case 'story': return c.title || 'Story';
            case 'title': return c.text || 'Title';
            case 'text': return (c.content || '').slice(0, 60) || 'Text';
            case 'image': return (c.src || 'Image').split('/').pop();
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
            modules.appendChild(paletteBtn(type, label, true));
        });
        Object.entries(builderData.block_types?.basic || {}).forEach(([type, label]) => {
            basic.appendChild(paletteBtn(type, label, false));
        });
    }

    function paletteBtn(type, label, isSection) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-outline pb-palette-btn' + (isSection ? ' pb-palette-section' : '');
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
        layouts[viewport].rows.push(newFullRow(block));
        renderCanvas();
        const row = layouts[viewport].rows[layouts[viewport].rows.length - 1];
        selectBlock(row.id, row.columns[0].id, block.id);
    }

    function updateViewportUI() {
        canvasWrap?.classList.toggle('pb-canvas--mobile', viewport === 'mobile');
        if (viewportNotice) {
            if (viewport === 'mobile' && !mobileCustomized) {
                viewportNotice.hidden = false;
                viewportNotice.textContent = 'Mobile layout is auto-stacked from desktop. Customize the order here, then Save Page.';
                viewportNotice.className = 'pb-viewport-notice pb-notice-info';
            } else {
                viewportNotice.hidden = true;
            }
        }
    }

    function renderCanvas() {
        if (rowSortable) {
            rowSortable.destroy();
            rowSortable = null;
        }
        canvas.innerHTML = '';
        const layout = layouts[viewport];
        if (!layout.rows.length) {
            canvas.innerHTML = '<div class="pb-empty"><p>Add page sections from the left panel to build your homepage.</p><p class="pb-hint">Each section becomes a block you can drag to reorder.</p></div>';
            return;
        }

        layout.rows.forEach((row, rowIndex) => {
            canvas.appendChild(renderRow(row, rowIndex));
        });

        if (typeof Sortable !== 'undefined') {
            rowSortable = Sortable.create(canvas, {
                handle: '.pb-row-handle',
                animation: 150,
                onEnd: (evt) => {
                    const rows = layouts[viewport].rows;
                    const moved = rows.splice(evt.oldIndex, 1)[0];
                    rows.splice(evt.newIndex, 0, moved);
                    if (viewport === 'mobile') mobileCustomized = true;
                    renderCanvas();
                },
            });
        }
    }

    function renderRow(row, rowIndex) {
        const isFull = row.layout !== 'columns';
        const rowEl = document.createElement('div');
        rowEl.className = 'pb-row' + (isFull ? ' pb-row--full' : ' pb-row--columns');
        rowEl.dataset.rowId = row.id;

        rowEl.innerHTML = `<div class="pb-row-toolbar">
            <span class="pb-row-handle" title="Drag to reorder">☰</span>
            <span class="pb-row-label">${isFull ? 'Section' : '3-Column Row'} ${rowIndex + 1}</span>
            <span class="pb-row-badge">${isFull ? 'Full width' : 'Columns'}</span>
            <button type="button" class="btn btn-sm btn-muted pb-row-up" title="Move up">↑</button>
            <button type="button" class="btn btn-sm btn-muted pb-row-down" title="Move down">↓</button>
            <button type="button" class="btn btn-sm btn-danger pb-row-del">Delete</button>
        </div>`;

        const body = document.createElement('div');
        body.className = isFull ? 'pb-section-body' : 'pb-row-cols';

        if (isFull) {
            const col = row.columns[0];
            if (!col.blocks.length) {
                body.innerHTML = '<p class="pb-hint">Empty section — drag a block here or delete this row.</p>';
            } else {
                col.blocks.forEach((block) => {
                    body.appendChild(renderSectionCard(block, row.id, col.id));
                });
            }
            body.addEventListener('dragover', (e) => e.preventDefault());
            body.addEventListener('drop', (e) => {
                e.preventDefault();
                const type = e.dataTransfer.getData('blockType');
                if (type) addSection(type);
            });
        } else {
            row.columns.forEach((col, colIndex) => {
                const colEl = document.createElement('div');
                colEl.className = 'pb-col';
                colEl.dataset.rowId = row.id;
                colEl.dataset.colId = col.id;
                colEl.innerHTML = `<div class="pb-col-label">Column ${colIndex + 1}</div>`;
                const list = document.createElement('div');
                list.className = 'pb-block-list';
                list.dataset.rowId = row.id;
                list.dataset.colId = col.id;
                col.blocks.forEach((block) => {
                    list.appendChild(renderBlockChip(block, row.id, col.id));
                });
                colEl.appendChild(list);
                body.appendChild(colEl);
                initBlockSortable(list, row.id, col.id);
                colEl.addEventListener('dragover', (e) => e.preventDefault());
                colEl.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const type = e.dataTransfer.getData('blockType');
                    if (type) {
                        const b = newBlock(type);
                        col.blocks.push(b);
                        renderCanvas();
                        selectBlock(row.id, col.id, b.id);
                    }
                });
            });
        }

        rowEl.appendChild(body);
        rowEl.querySelector('.pb-row-up').addEventListener('click', () => moveRow(rowIndex, -1));
        rowEl.querySelector('.pb-row-down').addEventListener('click', () => moveRow(rowIndex, 1));
        rowEl.querySelector('.pb-row-del').addEventListener('click', () => {
            if (confirm('Delete this ' + (isFull ? 'section' : 'row') + '?')) {
                layouts[viewport].rows.splice(rowIndex, 1);
                selected = null;
                inspector.innerHTML = '<p class="pb-hint">Click a section or block to edit it here.</p>';
                renderCanvas();
            }
        });

        return rowEl;
    }

    function renderSectionCard(block, rowId, colId) {
        const el = document.createElement('div');
        el.className = 'pb-section-card' + (selected?.blockId === block.id ? ' selected' : '') + (!block.active ? ' inactive' : '');
        el.dataset.blockId = block.id;
        const label = blockLabel(block.type);
        const preview = blockPreview(block);
        el.innerHTML = `
            <div class="pb-section-card-head">
                <span class="pb-section-type">${esc(label)}</span>
                <label class="pb-block-active"><input type="checkbox" ${block.active ? 'checked' : ''}> Visible</label>
            </div>
            <div class="pb-section-preview">${esc(preview)}</div>
            <div class="pb-section-preview-frame" data-preview-for="${escAttr(block.id)}"></div>`;
        el.addEventListener('click', (e) => {
            if (e.target.type === 'checkbox') return;
            selectBlock(rowId, colId, block.id);
        });
        el.querySelector('input').addEventListener('change', (e) => {
            block.active = e.target.checked;
            el.classList.toggle('inactive', !block.active);
        });
        loadBlockPreview(block, el.querySelector('[data-preview-for]'));
        return el;
    }

    async function loadBlockPreview(block, container) {
        if (!container) return;
        try {
            const res = await fetch('/api/page-builder.php?action=preview_block', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ block, _csrf: window.CSRF_TOKEN }),
            });
            const data = await res.json();
            if (data.success && data.html) {
                container.innerHTML = data.html;
            }
        } catch (_) { /* preview optional */ }
    }

    function renderBlockChip(block, rowId, colId) {
        const el = document.createElement('div');
        el.className = 'pb-block' + (selected?.blockId === block.id ? ' selected' : '') + (!block.active ? ' inactive' : '');
        el.dataset.blockId = block.id;
        el.innerHTML = `<span class="pb-block-handle">☰</span>
            <span class="pb-block-label">${esc(blockLabel(block.type))}</span>
            <span class="pb-block-preview">${esc(blockPreview(block))}</span>
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
            onAdd: (evt) => {
                const blockId = evt.item.dataset.blockId;
                if (!blockId) {
                    const type = evt.item.dataset.blockType;
                    if (type) {
                        const block = newBlock(type);
                        findColumn(rowId, colId).blocks.splice(evt.newIndex, 0, block);
                        evt.item.remove();
                        renderCanvas();
                        selectBlock(rowId, colId, block.id);
                    }
                    return;
                }
                moveBlockBetweenLists(blockId, rowId, colId, evt.newIndex);
            },
            onUpdate: (evt) => {
                const col = findColumn(rowId, colId);
                const moved = col.blocks.splice(evt.oldIndex, 1)[0];
                col.blocks.splice(evt.newIndex, 0, moved);
            },
        });
    }

    function moveBlockBetweenLists(blockId, toRowId, toColId, toIndex) {
        let block = null;
        layouts[viewport].rows.forEach((row) => {
            row.columns.forEach((col) => {
                const i = col.blocks.findIndex((b) => b.id === blockId);
                if (i >= 0) block = col.blocks.splice(i, 1)[0];
            });
        });
        if (block) findColumn(toRowId, toColId).blocks.splice(toIndex, 0, block);
        renderCanvas();
    }

    function moveRow(index, dir) {
        const rows = layouts[viewport].rows;
        const target = index + dir;
        if (target < 0 || target >= rows.length) return;
        [rows[index], rows[target]] = [rows[target], rows[index]];
        if (viewport === 'mobile') mobileCustomized = true;
        renderCanvas();
    }

    function findColumn(rowId, colId) {
        const row = layouts[viewport].rows.find((r) => r.id === rowId);
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

    function buildInspector(block, rowId, colId) {
        const c = block.config || {};
        const label = blockLabel(block.type);
        let html = `<h3>Edit ${esc(label)}</h3><p class="pb-block-type-label">${block.type}</p><form id="inspector-form">`;
        const field = (key, lbl, type = 'text', opts = {}) => {
            const val = c[key] ?? '';
            if (type === 'textarea') {
                html += `<label>${lbl}</label><textarea name="${key}">${esc(val)}</textarea>`;
            } else if (type === 'checkbox') {
                html += `<div class="checkbox-row"><label><input type="checkbox" name="${key}" ${val ? 'checked' : ''}> ${lbl}</label></div>`;
            } else if (type === 'select') {
                html += `<label>${lbl}</label><select name="${key}">`;
                opts.options.forEach(([v, l]) => {
                    html += `<option value="${escAttr(v)}"${String(val) === String(v) ? ' selected' : ''}>${esc(l)}</option>`;
                });
                html += '</select>';
            } else if (type === 'image') {
                html += window.MediaPicker
                    ? window.MediaPicker.imageFieldHtml(key, lbl, val)
                    : `<label>${lbl}</label><input type="text" name="${key}" value="${escAttr(val)}">`;
            } else {
                html += `<label>${lbl}</label><input type="${type}" name="${key}" value="${escAttr(val)}">`;
            }
        };

        switch (block.type) {
            case 'title':
                field('text', 'Title Text'); field('level', 'Size', 'select', { options: [['h1', 'Large'], ['h2', 'Medium'], ['h3', 'Small']] });
                field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] });
                break;
            case 'text':
                field('content', 'Content', 'textarea');
                field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] });
                break;
            case 'image':
                field('src', 'Image', 'image'); field('alt', 'Alt Text'); field('caption', 'Caption (optional)');
                break;
            case 'button':
                field('text', 'Button Text'); field('link', 'Link URL');
                field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] });
                break;
            case 'spacer': field('height', 'Height (px)', 'number'); break;
            case 'menu_category':
                field('category_id', 'Menu Category', 'select', { options: (builderData.categories || []).map((cat) => [cat.id, cat.name]) });
                field('title', 'Custom Title (optional)');
                break;
            case 'gallery': field('title', 'Gallery Title'); html += renderGalleryManager(); break;
            case 'hero':
                field('title', 'Headline'); field('subtitle', 'Subtitle', 'textarea');
                field('background_image', 'Background Image', 'image'); field('logo_image', 'Logo Image', 'image');
                field('cta_text', 'Button Text'); field('cta_link', 'Button Link');
                break;
            case 'story':
                field('title', 'Title'); field('paragraph1', 'Paragraph 1', 'textarea');
                field('paragraph2', 'Paragraph 2', 'textarea'); field('image', 'Photo', 'image');
                break;
            case 'menu_preview':
                field('title', 'Title'); field('coming_soon_title', 'Coming Soon Title');
                field('coming_soon_text', 'Coming Soon Text', 'textarea');
                field('show_coming_soon', 'Show Coming Soon Box', 'checkbox');
                field('link_to_full_menu', 'Link to Full Menu Page', 'checkbox');
                break;
            case 'find_us':
                field('title', 'Title'); field('text', 'Footer Text');
                field('max_events', 'Max Events', 'number'); field('show_facebook_button', 'Show Facebook Button', 'checkbox');
                break;
            case 'contact':
                field('title', 'Title'); field('subtitle', 'Subtitle');
                field('show_contact', 'Show Contact Form', 'checkbox'); field('show_review', 'Show Review Form', 'checkbox');
                break;
            case 'newsletter': field('title', 'Title'); field('subtitle', 'Subtitle', 'textarea'); break;
            case 'social': field('title', 'Title'); break;
            default: field('title', 'Title');
        }

        html += `<div class="form-actions">
            <button type="submit" class="btn btn-sm">Apply Changes</button>
            <button type="button" class="btn btn-sm btn-danger" id="delete-block-btn">Delete Block</button>
        </div></form>`;
        return html;
    }

    function renderGalleryManager() {
        const imgs = (builderData.gallery || []).filter((img) => img.is_active);
        let html = '<div class="pb-gallery-manager"><h4>Gallery Photos</h4>';
        html += '<p class="pb-hint">Manage photos in the <a href="/admin/gallery.php" target="_blank">Media Library</a>.</p>';
        html += '<div class="pb-gallery-grid">';
        imgs.forEach((img) => { html += `<div class="pb-gallery-thumb"><img src="${escAttr(img.url)}" alt=""></div>`; });
        html += '</div></div>';
        return html;
    }

    function bindInspector(block, rowId, colId) {
        const form = document.getElementById('inspector-form');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            form.querySelectorAll('[name]').forEach((input) => {
                if (input.type === 'checkbox') return;
                block.config[input.name] = input.type === 'number' ? Number(input.value) : input.value;
            });
            form.querySelectorAll('input[type=checkbox]').forEach((cb) => { block.config[cb.name] = cb.checked; });
            setStatus('Changes applied — click Save Page when ready', 'success');
            renderCanvas();
        });
        form?.querySelectorAll('[data-media-picker]').forEach((field) => {
            window.MediaPicker?.bindField(field);
            field.querySelector('[data-media-input]')?.addEventListener('change', () => {
                const input = field.querySelector('[data-media-input]');
                if (input) block.config[input.name] = input.value;
            });
        });
        document.getElementById('delete-block-btn')?.addEventListener('click', () => {
            if (!confirm('Delete this block?')) return;
            const col = findColumn(rowId, colId);
            col.blocks = col.blocks.filter((b) => b.id !== block.id);
            const row = layouts[viewport].rows.find((r) => r.id === rowId);
            if (row?.layout === 'full' && !col.blocks.length) {
                layouts[viewport].rows = layouts[viewport].rows.filter((r) => r.id !== rowId);
            }
            selected = null;
            inspector.innerHTML = '<p class="pb-hint">Click a section or block to edit it here.</p>';
            renderCanvas();
        });
    }

    document.getElementById('add-column-row-btn')?.addEventListener('click', () => {
        layouts[viewport].rows.push(newColumnRow());
        renderCanvas();
    });

    document.querySelectorAll('.pb-viewport-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.pb-viewport-tab').forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');
            viewport = tab.dataset.viewport;
            selected = null;
            inspector.innerHTML = '<p class="pb-hint">Click a section or block to edit it here.</p>';
            updateViewportUI();
            renderCanvas();
        });
    });

    resetMobileBtn?.addEventListener('click', async () => {
        if (!confirm('Reset mobile layout by stacking all desktop sections vertically? This replaces your current mobile layout.')) return;
        layouts.mobile = mobileFromDesktop(layouts.desktop);
        mobileCustomized = true;
        updateViewportUI();
        renderCanvas();
        setStatus('Mobile layout reset from desktop — click Save Page to publish', 'success');
    });

    saveBtn?.addEventListener('click', async () => {
        saveBtn.disabled = true;
        setStatus('Saving desktop & mobile layouts...', '');
        try {
            for (const vp of ['desktop', 'mobile']) {
                const res = await fetch('/api/page-builder.php?action=save_layout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                    body: JSON.stringify({ viewport: vp, layout: layouts[vp], _csrf: window.CSRF_TOKEN }),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
            }
            mobileCustomized = true;
            updateViewportUI();
            setStatus('Page saved! View your live site to see changes.', 'success');
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
