(function () {
    function reportClientError(message, extra) {
        if (!window.CSRF_TOKEN) return;
        fetch('/api/admin-log.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                message,
                page: window.location.pathname,
                _csrf: window.CSRF_TOKEN,
                ...extra,
            }),
        }).catch(() => {});
    }

    window.addEventListener('error', (event) => {
        reportClientError(event.message || 'Unknown error', {
            url: event.filename || '',
            line: event.lineno || '',
            column: event.colno || '',
            stack: event.error?.stack || '',
        });
    });

    window.addEventListener('unhandledrejection', (event) => {
        const reason = event.reason;
        const msg = reason instanceof Error ? reason.message : String(reason);
        reportClientError('Unhandled promise: ' + msg, {
            stack: reason instanceof Error ? reason.stack : '',
        });
    });

    window.adminApiFetch = async function adminApiFetch(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        };
        const res = await fetch(url, { credentials: 'same-origin', ...options, headers });
        const text = await res.text();
        let data;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (_) {
            const err = new Error(
                'Server returned invalid JSON (HTTP ' + res.status + '). Open Admin → Debug for details.'
            );
            reportClientError(err.message, { url, response: text.slice(0, 500) });
            throw err;
        }
        if (!res.ok && !data.message) {
            data.message = 'Request failed (HTTP ' + res.status + ')';
        }
        return { res, data };
    };
})();
