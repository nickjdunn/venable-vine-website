document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.view-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const view = tab.dataset.view;
            document.querySelectorAll('.view-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.view-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('view-' + view)?.classList.add('active');
        });
    });

    const calEl = document.getElementById('events-calendar');
    if (calEl && typeof FullCalendar !== 'undefined' && window.VV_EVENTS) {
        new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            events: window.VV_EVENTS.map(ev => ({
                id: ev.id,
                title: ev.title,
                start: ev.start,
                end: ev.end,
            })),
            height: 'auto',
        }).render();
    }
});
