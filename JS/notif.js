let notifications = [];


// LOAD NOTIFICATION
async function loadNotifications() {

    try {
        const response = await fetch('get_notifications.php');
        const data = await response.json();

        notifications = data;
        render(notifications);

    } catch (error) {
        console.error('Gagal mengambil notifikasi:', error);
    }
}


function render(data) {

    const container = document.getElementById('notifContainer');
    const empty = document.getElementById('emptyState');

    if (!container || !empty) return;

    container.innerHTML = '';

    if (data.length === 0) {
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';

    data.forEach(n => {

        const el = document.createElement('div');

        el.className = 'notification ' + (n.unread ? 'unread' : '');

        el.innerHTML = `
            <div class="avatar"></div>

            <div class="content">
                <div class="title">${n.title}</div>
                <div class="preview">${n.preview}</div>
                <div class="time">${n.time}</div>
            </div>
        `;

        el.onclick = () => {
            n.unread = false;
            render(notifications);
        };

        container.appendChild(el);
    });
}


function filterNotif(type, el) {

    document.querySelectorAll('.tab').forEach(t =>
        t.classList.remove('active')
    );

    el.classList.add('active');

    if (type === 'all') {
        render(notifications);
    } else {
        render(notifications.filter(n => n.type === type));
    }
}


function goBack() {
    window.history.back();
}


function markAllRead() {

    notifications.forEach(n => n.unread = false);
    render(notifications);
}


// INIT
loadNotifications();