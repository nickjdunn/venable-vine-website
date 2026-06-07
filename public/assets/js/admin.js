document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.querySelector('.admin-nav-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    sidebarToggle?.addEventListener('click', () => sidebar?.classList.toggle('open'));

    // Generic tabs
    document.querySelectorAll('[data-tabs]').forEach(tabGroup => {
        const buttons = tabGroup.querySelectorAll('.tab-btn');
        const panels = tabGroup.querySelectorAll('.tab-panel');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.tab;
                buttons.forEach(b => b.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(target)?.classList.add('active');
            });
        });
    });

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

    // Geolocation for events
    const geoBtn = document.getElementById('get-location-btn');
    const addressInput = document.getElementById('event-address');
    const latInput = document.getElementById('event-lat');
    const lngInput = document.getElementById('event-lng');
    if (geoBtn && addressInput) {
        geoBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            geoBtn.disabled = true;
            geoBtn.textContent = 'Locating...';
            navigator.geolocation.getCurrentPosition(async (pos) => {
                const { latitude, longitude } = pos.coords;
                if (latInput) latInput.value = latitude;
                if (lngInput) lngInput.value = longitude;
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
                    const data = await res.json();
                    addressInput.value = data.display_name || `${latitude}, ${longitude}`;
                } catch {
                    addressInput.value = `${latitude}, ${longitude}`;
                }
                geoBtn.disabled = false;
                geoBtn.textContent = 'Use Current Location';
            }, () => {
                alert('Could not get your location.');
                geoBtn.disabled = false;
                geoBtn.textContent = 'Use Current Location';
            });
        });
    }
});
