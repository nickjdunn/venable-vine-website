(function () {
    let modalEl = null;
    let quill = null;
    let saveCallback = null;

    // #region agent log
    function dbgLog(location, message, data, hypothesisId) {
        fetch('http://127.0.0.1:7709/ingest/55c5d319-00f7-40e2-8cfc-95a4896d60d5', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '684396' }, body: JSON.stringify({ sessionId: '684396', location, message, data, hypothesisId, timestamp: Date.now(), runId: 'pre-fix' }) }).catch(() => {});
    }
    // #endregion

    function ensureModal() {
        if (modalEl) return modalEl;
        modalEl = document.createElement('div');
        modalEl.id = 'rte-modal';
        modalEl.className = 'rte-modal';
        modalEl.hidden = true;
        modalEl.innerHTML = `<div class="rte-backdrop" data-rte-close></div>
            <div class="rte-dialog" role="dialog" aria-modal="true">
                <div class="rte-header">
                    <h3>Edit Text</h3>
                    <button type="button" class="rte-close" data-rte-close aria-label="Close">&times;</button>
                </div>
                <div id="rte-editor"></div>
                <div class="rte-actions">
                    <button type="button" class="btn btn-muted" data-rte-close>Cancel</button>
                    <button type="button" class="btn btn-success" data-rte-save>Apply</button>
                </div>
            </div>`;
        document.body.appendChild(modalEl);

        modalEl.querySelectorAll('[data-rte-close]').forEach((el) => {
            el.addEventListener('click', close);
        });
        modalEl.querySelector('[data-rte-save]')?.addEventListener('click', () => {
            if (!quill || !saveCallback) return;
            const html = quill.root.innerHTML.trim();
            const empty = quill.getText().trim() === '';
            saveCallback(empty ? '' : html);
            close();
        });

        return modalEl;
    }

    function initQuill(mode) {
        if (typeof Quill === 'undefined') {
            alert('Text editor failed to load. Please refresh the page.');
            return null;
        }
        const container = document.getElementById('rte-editor');
        // #region agent log
        const toolbarsBefore = document.querySelectorAll('#rte-modal .ql-toolbar, .ql-toolbar').length;
        dbgLog('rich-text-editor.js:initQuill', 'before initQuill', { toolbarsBefore, hasExistingQuill: !!quill, mode }, 'H1');
        // #endregion
        container.innerHTML = '';
        const toolbar = mode === 'plain'
            ? [['bold', 'italic', 'underline'], ['link'], ['clean']]
            : [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean'],
            ];
        quill = new Quill(container, {
            theme: 'snow',
            modules: { toolbar },
        });
        // #region agent log
        const toolbarsAfter = document.querySelectorAll('#rte-modal .ql-toolbar, .ql-toolbar').length;
        dbgLog('rich-text-editor.js:initQuill', 'after initQuill', { toolbarsAfter, mode }, 'H1');
        // #endregion
        return quill;
    }

    function open(options) {
        ensureModal();
        const mode = options.mode === 'plain' ? 'plain' : 'rich';
        initQuill(mode);
        if (!quill) return;

        let content = options.content || '';
        if (mode === 'plain') {
            content = content.replace(/<[^>]+>/g, '');
            quill.setText(content);
        } else if (content && content === content.replace(/<[^>]+>/g, '')) {
            quill.setText(content);
        } else {
            quill.root.innerHTML = content;
        }

        saveCallback = options.onSave || null;
        modalEl.hidden = false;
        document.body.style.overflow = 'hidden';
        quill.focus();
    }

    function close() {
        if (!modalEl) return;
        modalEl.hidden = true;
        saveCallback = null;
        document.body.style.overflow = '';
    }

    window.RichTextEditor = { open, close };
})();
