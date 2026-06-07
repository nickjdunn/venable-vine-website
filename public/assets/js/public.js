document.addEventListener('DOMContentLoaded', () => {
    // Mobile nav
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.nav-links');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Contact / review form tabs
    document.querySelectorAll('.choice-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));
            const target = document.getElementById(btn.dataset.form);
            if (target) target.classList.add('active');
        });
    });

    // AJAX forms
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = form.querySelector('.form-status');
            const btn = form.querySelector('[type="submit"]');
            const endpoint = form.dataset.endpoint;
            if (!endpoint) return;
            status.textContent = 'Sending...';
            status.className = 'form-status';
            btn.disabled = true;
            try {
                const fd = new FormData(form);
                if (typeof grecaptcha !== 'undefined') {
                    const widget = form.querySelector('.g-recaptcha');
                    if (widget) {
                        const response = grecaptcha.getResponse();
                        fd.append('g-recaptcha-response', response);
                    }
                }
                const res = await fetch(endpoint, { method: 'POST', body: fd });
                const data = await res.json();
                status.textContent = data.message || (data.success ? 'Success!' : 'Error');
                status.className = 'form-status ' + (data.success ? 'success' : 'error');
                if (data.success) form.reset();
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
            } catch (err) {
                status.textContent = 'Could not send. Please try again.';
                status.className = 'form-status error';
            } finally {
                btn.disabled = false;
            }
        });
    });

    // Lightbox
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const closeBtn = document.querySelector('.close-lightbox');
    document.querySelectorAll('.gallery-image').forEach(img => {
        img.addEventListener('click', () => {
            if (!lightbox || !lightboxImg) return;
            lightbox.hidden = false;
            lightboxImg.src = img.src;
            document.body.style.overflow = 'hidden';
        });
    });
    const closeLightbox = () => {
        if (!lightbox) return;
        lightbox.hidden = true;
        if (lightboxImg) lightboxImg.src = '';
        document.body.style.overflow = '';
    };
    closeBtn?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLightbox(); });
});
