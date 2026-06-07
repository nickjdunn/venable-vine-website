document.addEventListener('DOMContentLoaded', () => {
    const steps = [
        { title: 'Welcome to V&V Admin!', body: 'This quick tour shows you how to manage your food truck website. You can reopen it anytime with the Tutorial button.', highlight: null },
        { title: 'Dashboard', body: 'Your home base — see upcoming events, new messages, and pending reviews at a glance.', highlight: '.admin-nav-link[href*="dashboard"]', nav: '/admin/dashboard.php' },
        { title: 'Page Builder', body: 'Edit your homepage visually — it looks just like the live site. Click any text or photo to change it, drag section handles to reorder, then Save Page.', highlight: '.admin-nav-link[href*="page-builder"]', nav: '/admin/page-builder.php' },
        { title: 'Menu', body: 'Add categories and items with prices, photos, and dietary tags. Toggle items on/off without deleting them.', highlight: '.admin-nav-link[href*="menu"]', nav: '/admin/menu.php' },
        { title: 'Events', body: 'Tell customers where the truck will be! Add date, time, and location. Use "Use Current Location" on your phone.', highlight: '.admin-nav-link[href*="events"]', nav: '/admin/events.php' },
        { title: 'Media Library', body: 'Upload and manage all site images here. Use Add Photo in Page Builder, Settings, and Menu to pick images. Edit names, alt text, and captions.', highlight: '.admin-nav-link[href*="gallery"]', nav: '/admin/gallery.php' },
        { title: 'Settings', body: 'Choose your logo and favicon from the Media Library, set social media links, and add your Google Maps API key.', highlight: '.admin-nav-link[href*="settings"]', nav: '/admin/settings.php' },
        { title: 'You\'re all set!', body: 'Visit your live site to see how it looks. Start by adding your next event and updating the homepage in Page Builder.', highlight: null },
    ];

    let stepIndex = 0;
    let overlay, card, titleEl, bodyEl, progressEl, prevBtn, nextBtn, skipBtn;

    function buildOverlay() {
        overlay = document.createElement('div');
        overlay.className = 'tutorial-overlay';
        overlay.innerHTML = `<div class="tutorial-card" role="dialog">
            <div class="tutorial-progress"></div>
            <h2></h2>
            <p></p>
            <div class="tutorial-actions">
                <button type="button" class="btn btn-muted" id="tut-skip">Skip</button>
                <div style="display:flex;gap:0.5rem;">
                    <button type="button" class="btn btn-outline" id="tut-prev">Back</button>
                    <button type="button" class="btn btn-success" id="tut-next">Next</button>
                </div>
            </div>
        </div>`;
        document.body.appendChild(overlay);
        titleEl = overlay.querySelector('h2');
        bodyEl = overlay.querySelector('p');
        progressEl = overlay.querySelector('.tutorial-progress');
        prevBtn = overlay.querySelector('#tut-prev');
        nextBtn = overlay.querySelector('#tut-next');
        skipBtn = overlay.querySelector('#tut-skip');
        prevBtn.addEventListener('click', () => showStep(stepIndex - 1));
        nextBtn.addEventListener('click', () => {
            if (stepIndex >= steps.length - 1) closeTutorial(true);
            else showStep(stepIndex + 1);
        });
        skipBtn.addEventListener('click', () => closeTutorial(true));
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeTutorial(false); });
    }

    function clearHighlight() {
        document.querySelectorAll('.tutorial-highlight').forEach((el) => el.classList.remove('tutorial-highlight'));
    }

    function showStep(i) {
        stepIndex = Math.max(0, Math.min(steps.length - 1, i));
        const step = steps[stepIndex];
        if (step.nav && !window.location.pathname.includes(step.nav.replace('/admin/', ''))) {
            sessionStorage.setItem('vv_tutorial_step', String(stepIndex));
            window.location.href = step.nav + '?tutorial=1';
            return;
        }
        clearHighlight();
        titleEl.textContent = step.title;
        bodyEl.textContent = step.body;
        progressEl.textContent = `Step ${stepIndex + 1} of ${steps.length}`;
        prevBtn.style.visibility = stepIndex === 0 ? 'hidden' : 'visible';
        nextBtn.textContent = stepIndex === steps.length - 1 ? 'Finish' : 'Next';
        if (step.highlight) {
            const el = document.querySelector(step.highlight);
            if (el) {
                el.classList.add('tutorial-highlight');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        overlay.classList.add('open');
    }

    function closeTutorial(done) {
        overlay?.classList.remove('open');
        clearHighlight();
        if (done) localStorage.setItem('vv_tutorial_done', '1');
        sessionStorage.removeItem('vv_tutorial_step');
    }

    function openTutorial() {
        if (!overlay) buildOverlay();
        showStep(0);
    }

    document.getElementById('admin-tutorial-btn')?.addEventListener('click', openTutorial);

    const params = new URLSearchParams(window.location.search);
    if (params.get('tutorial') === '1') {
        if (!overlay) buildOverlay();
        const saved = parseInt(sessionStorage.getItem('vv_tutorial_step') || '0', 10);
        showStep(saved);
    } else if (!localStorage.getItem('vv_tutorial_done') && document.querySelector('.admin-content')) {
        setTimeout(openTutorial, 600);
    }
});
