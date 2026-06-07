document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('pb-canvas');
    const inspector = document.getElementById('pb-inspector');
    const statusEl = document.getElementById('pb-status');
    const saveBtn = document.getElementById('save-layout-btn');

    let viewport = 'desktop';
    let layouts = { desktop: { rows: [] }, mobile: { rows: [] } };
    let builderData = {};
    let selected = null; // { rowId, colId, blockId }

    uid.counter = 1;
    function uid(prefix) { return `${prefix}_${Date.now()}_${uid.counter++}`; }

    async function load() {
        const res = await fetch('/api/page-builder.php?action=get_builder_data');
        builderData = await res.json();
        if (!builderData.success) {
            setStatus(builderData.message || 'Load failed', 'error');
            return;
        }
        layouts.desktop = builderData.layout_desktop?.rows?.length ? builderData.layout_desktop : { rows: [] };
        layouts.mobile = builderData.layout_mobile?.rows?.length ? builderData.layout_mobile : JSON.parse(JSON.stringify(layouts.desktop));
        renderPalette();
        renderCanvas();
    }

    function renderPalette() {
        const basic = document.getElementById('palette-basic');
        const modules = document.getElementById('palette-modules');
        basic.innerHTML = '';
        modules.innerHTML = '';
        Object.entries(builderData.block_types?.basic || {}).forEach(([type, label]) => {
            basic.appendChild(paletteBtn(type, label));
        });
        Object.entries(builderData.block_types?.modules || {}).forEach(([type, label]) => {
            modules.appendChild(paletteBtn(type, label));
        });
    }

    function paletteBtn(type, label) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-outline pb-palette-btn';
        b.textContent = '+ ' + label;
        b.draggable = true;
        b.dataset.blockType = type;
        b.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('blockType', type);
            e.dataTransfer.effectAllowed = 'copy';
        });
        b.addEventListener('click', () => addBlockToFirstColumn(type));
        return b;
    }

    function addBlockToFirstColumn(type) {
        ensureRow();
        const row = layouts[viewport].rows[layouts[viewport].rows.length - 1];
        row.columns[0].blocks.push(newBlock(type));
        renderCanvas();
        selectBlock(row.id, row.columns[0].id, row.columns[0].blocks[row.columns[0].blocks.length - 1].id);
    }

    function ensureRow() {
        if (!layouts[viewport].rows.length) {
            layouts[viewport].rows.push(newRow());
        }
    }

    function newRow() {
        return {
            id: uid('row'),
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
            image: { src: (builderData.asset_images || [])[0] || '', alt: '', caption: '' },
            button: { text: 'Learn More', link: '/find-us.php', align: 'center' },
            spacer: { height: 40 },
            gallery: { title: 'A Glimpse of Our Goodness' },
            menu_category: { category_id: (builderData.categories || [])[0]?.id || 0, title: '' },
            hero: { title: 'Freshly Squeezed. Family Made.', subtitle: '', background_image: 'assets/images/BerriesInhand.webp', logo_image: 'assets/images/VenableandVineLogo.webp', cta_text: 'Find The Truck Today', cta_link: '/find-us.php' },
            story: { title: 'Our Story', paragraph1: '', paragraph2: '', image: 'assets/images/FoodTruckPicture.webp' },
            menu_preview: { title: 'Taste the Sunshine', show_coming_soon: true, coming_soon_title: 'Coming Soon!', coming_soon_text: '', link_to_full_menu: true },
            reviews: { title: 'What Our Customers Say' },
            find_us: { title: 'Where to Find Us', text: '', show_facebook_button: true, max_events: 5 },
            contact: { title: 'Get In Touch', subtitle: '', show_contact: true, show_review: true },
            newsletter: { title: 'Stay in the Loop', subtitle: '' },
            social: { title: 'Follow Us' },
        };
        return { ...(defaults[type] || {}) };
    }

    function renderCanvas() {
        canvas.innerHTML = '';
        const layout = layouts[viewport];
        if (!layout.rows.length) {
            canvas.innerHTML = '<p class="pb-hint">Click "+ Add Row" or drag blocks from the left panel to start building.</p>';
            return;
        }
        layout.rows.forEach((row, rowIndex) => {
            const rowEl = document.createElement('div');
            rowEl.className = 'pb-row';
            rowEl.dataset.rowId = row.id;
            rowEl.innerHTML = `<div class="pb-row-toolbar">
                <span class="pb-row-label">Row ${rowIndex + 1}</span>
                <button type="button" class="btn btn-sm btn-muted pb-row-up" title="Move up">↑</button>
                <button type="button" class="btn btn-sm btn-muted pb-row-down" title="Move down">↓</button>
                <button type="button" class="btn btn-sm btn-danger pb-row-del">Delete Row</button>
            </div><div class="pb-row-cols"></div>`;
            const colsWrap = rowEl.querySelector('.pb-row-cols');
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
                colsWrap.appendChild(colEl);
                initSortable(list, row.id, col.id);
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
            rowEl.querySelector('.pb-row-up').addEventListener('click', () => moveRow(rowIndex, -1));
            rowEl.querySelector('.pb-row-down').addEventListener('click', () => moveRow(rowIndex, 1));
            rowEl.querySelector('.pb-row-del').addEventListener('click', () => {
                if (confirm('Delete this row?')) {
                    layout.rows.splice(rowIndex, 1);
                    renderCanvas();
                }
            });
            canvas.appendChild(rowEl);
        });
    }

    function moveRow(index, dir) {
        const rows = layouts[viewport].rows;
        const target = index + dir;
        if (target < 0 || target >= rows.length) return;
        [rows[index], rows[target]] = [rows[target], rows[index]];
        renderCanvas();
    }

    function renderBlockChip(block, rowId, colId) {
        const el = document.createElement('div');
        el.className = 'pb-block' + (selected?.blockId === block.id ? ' selected' : '') + (!block.active ? ' inactive' : '');
        el.dataset.blockId = block.id;
        const label = (builderData.block_types?.basic?.[block.type] || builderData.block_types?.modules?.[block.type] || block.type);
        el.innerHTML = `<span class="pb-block-handle">☰</span>
            <span class="pb-block-label">${label}</span>
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

    function initSortable(listEl, rowId, colId) {
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
                if (i >= 0) {
                    block = col.blocks.splice(i, 1)[0];
                }
            });
        });
        if (block) {
            findColumn(toRowId, toColId).blocks.splice(toIndex, 0, block);
        }
        renderCanvas();
    }

    function findColumn(rowId, colId) {
        const row = layouts[viewport].rows.find((r) => r.id === rowId);
        return row.columns.find((c) => c.id === colId);
    }

    function findBlock(rowId, colId, blockId) {
        const col = findColumn(rowId, colId);
        return col?.blocks.find((b) => b.id === blockId);
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
        let html = `<h3>Edit Block</h3><p class="pb-block-type-label">${block.type}</p><form id="inspector-form">`;
        const field = (key, label, type = 'text', opts = {}) => {
            const val = c[key] ?? '';
            if (type === 'textarea') {
                html += `<label>${label}</label><textarea name="${key}">${esc(val)}</textarea>`;
            } else if (type === 'checkbox') {
                html += `<div class="checkbox-row"><label><input type="checkbox" name="${key}" ${val ? 'checked' : ''}> ${label}</label></div>`;
            } else if (type === 'select') {
                html += `<label>${label}</label><select name="${key}">`;
                opts.options.forEach(([v, l]) => {
                    html += `<option value="${escAttr(v)}"${String(val) === String(v) ? ' selected' : ''}>${esc(l)}</option>`;
                });
                html += '</select>';
            } else if (type === 'image') {
                html += `<label>${label}</label><select name="${key}">`;
                (builderData.asset_images || []).forEach((p) => {
                    html += `<option value="${escAttr(p)}"${val === p ? ' selected' : ''}>${esc(p.split('/').pop())}</option>`;
                });
                html += '</select>';
            } else {
                html += `<label>${label}</label><input type="${type}" name="${key}" value="${escAttr(val)}">`;
            }
        };

        switch (block.type) {
            case 'title':
                field('text', 'Title Text');
                field('level', 'Size', 'select', { options: [['h1', 'Large'], ['h2', 'Medium'], ['h3', 'Small']] });
                field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] });
                break;
            case 'text':
                field('content', 'Content', 'textarea');
                field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] });
                break;
            case 'image':
                field('src', 'Image', 'image');
                field('alt', 'Alt Text');
                field('caption', 'Caption (optional)');
                break;
            case 'button':
                field('text', 'Button Text');
                field('link', 'Link URL');
                field('align', 'Alignment', 'select', { options: [['left', 'Left'], ['center', 'Center'], ['right', 'Right']] });
                break;
            case 'spacer':
                field('height', 'Height (px)', 'number');
                break;
            case 'menu_category':
                field('category_id', 'Menu Category', 'select', {
                    options: (builderData.categories || []).map((cat) => [cat.id, cat.name]),
                });
                field('title', 'Custom Title (optional)');
                break;
            case 'gallery':
                field('title', 'Gallery Title');
                html += renderGalleryManager();
                break;
            case 'hero':
                field('title', 'Headline');
                field('subtitle', 'Subtitle', 'textarea');
                field('background_image', 'Background Image', 'image');
                field('logo_image', 'Logo Image', 'image');
                field('cta_text', 'Button Text');
                field('cta_link', 'Button Link');
                break;
            case 'story':
                field('title', 'Title');
                field('paragraph1', 'Paragraph 1', 'textarea');
                field('paragraph2', 'Paragraph 2', 'textarea');
                field('image', 'Photo', 'image');
                break;
            case 'menu_preview':
                field('title', 'Title');
                field('coming_soon_title', 'Coming Soon Title');
                field('coming_soon_text', 'Coming Soon Text', 'textarea');
                field('show_coming_soon', 'Show Coming Soon Box', 'checkbox');
                field('link_to_full_menu', 'Link to Full Menu Page', 'checkbox');
                break;
            case 'find_us':
                field('title', 'Title');
                field('text', 'Footer Text');
                field('max_events', 'Max Events', 'number');
                field('show_facebook_button', 'Show Facebook Button', 'checkbox');
                break;
            case 'contact':
                field('title', 'Title');
                field('subtitle', 'Subtitle');
                field('show_contact', 'Show Contact Form', 'checkbox');
                field('show_review', 'Show Review Form', 'checkbox');
                break;
            case 'newsletter':
                field('title', 'Title');
                field('subtitle', 'Subtitle', 'textarea');
                break;
            default:
                field('title', 'Title');
        }

        html += `<div class="form-actions">
            <button type="submit" class="btn btn-sm">Apply</button>
            <button type="button" class="btn btn-sm btn-danger" id="delete-block-btn">Delete Block</button>
        </div></form>`;
        return html;
    }

    function renderGalleryManager() {
        const imgs = builderData.gallery || [];
        let html = '<div class="pb-gallery-manager"><h4>Gallery Photos</h4>';
        if (!imgs.length) {
            html += '<p class="pb-hint">No photos yet. Upload below.</p>';
        }
        html += '<div class="pb-gallery-grid">';
        imgs.forEach((img) => {
            html += `<div class="pb-gallery-thumb${img.is_active ? '' : ' inactive'}">
                <img src="${escAttr(img.url)}" alt="">
                <button type="button" class="btn btn-sm btn-danger pb-gdel" data-id="${img.id}">×</button>
            </div>`;
        });
        html += '</div>';
        html += `<label>Upload New Photo</label>
            <input type="file" id="gallery-upload-input" accept="image/*">
            <p class="pb-hint">Photos appear on the site gallery block and homepage.</p></div>`;
        return html;
    }

    function bindInspector(block, rowId, colId) {
        const form = document.getElementById('inspector-form');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            fd.forEach((val, key) => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input?.type === 'checkbox') return;
                block.config[key] = input?.type === 'number' ? Number(val) : val;
            });
            form.querySelectorAll('input[type=checkbox]').forEach((cb) => {
                block.config[cb.name] = cb.checked;
            });
            setStatus('Block updated — click Save Page when ready', 'success');
        });
        document.getElementById('delete-block-btn')?.addEventListener('click', () => {
            if (!confirm('Delete this block?')) return;
            const col = findColumn(rowId, colId);
            col.blocks = col.blocks.filter((b) => b.id !== block.id);
            selected = null;
            inspector.innerHTML = '<p class="pb-hint">Click a block to edit it here.</p>';
            renderCanvas();
        });
        document.querySelectorAll('.pb-gdel').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!confirm('Remove this photo from the gallery?')) return;
                const fd = new FormData();
                fd.append('action', 'gallery_delete');
                fd.append('id', btn.dataset.id);
                fd.append('_csrf', window.CSRF_TOKEN);
                await fetch('/api/page-builder.php', { method: 'POST', body: fd });
                await reloadGallery();
                selectBlock(rowId, colId, block.id);
            });
        });
        document.getElementById('gallery-upload-input')?.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('action', 'gallery_upload');
            fd.append('photo', file);
            fd.append('_csrf', window.CSRF_TOKEN);
            const res = await fetch('/api/page-builder.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                await reloadGallery();
                selectBlock(rowId, colId, block.id);
                setStatus('Photo uploaded', 'success');
            } else {
                setStatus(data.message || 'Upload failed', 'error');
            }
        });
    }

    async function reloadGallery() {
        const res = await fetch('/api/page-builder.php?action=get_builder_data');
        const data = await res.json();
        if (data.success) builderData.gallery = data.gallery;
    }

    document.getElementById('add-row-btn')?.addEventListener('click', () => {
        layouts[viewport].rows.push(newRow());
        renderCanvas();
    });

    document.querySelectorAll('.pb-viewport-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.pb-viewport-tab').forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');
            viewport = tab.dataset.viewport;
            selected = null;
            inspector.innerHTML = '<p class="pb-hint">Click a block to edit it here.</p>';
            renderCanvas();
        });
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
            setStatus('Page saved! View your site to see changes.', 'success');
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
