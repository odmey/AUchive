let notifications = [];

// TIME AGO HELPER
function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString.replace(/-/g, '/')); // browser compatibility for safari/older engines
    const ms = now - past;
    const sec = Math.floor(ms / 1000);
    if (isNaN(sec)) return dateString;
    if (sec < 5) return 'Just now';
    if (sec < 60) return sec + 's ago';
    const min = Math.floor(sec / 60);
    if (min < 60) return min + 'm ago';
    const hr = Math.floor(min / 60);
    if (hr < 24) return hr + 'h ago';
    const days = Math.floor(hr / 24);
    if (days < 30) return days + 'd ago';
    return past.toLocaleDateString();
}

// LOAD NOTIFICATION
async function loadNotifications() {
    try {
        const response = await fetch('PHP/get_notifications.php');
        const data = await response.json();

        if (data.success) {
            notifications = (data.notifications || []).map(n => ({
                id: n.notif_id,
                type: n.type,
                title: n.title,
                preview: n.body,
                time: timeAgo(n.created_at),
                unread: !parseInt(n.is_read),
                link: n.link_url,
                storyCover: n.story_cover,
                actorUsername: n.actor_username
            }));
            render(notifications);
        } else {
            console.error('Failed to load notifications:', data.message);
        }
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

        const avatarHtml = n.storyCover 
            ? `<img src="${n.storyCover}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" onerror="this.style.display='none'">`
            : `<div style="width:100%; height:100%; border-radius:50%; display:flex; align-items:center; justify-content:center; color:black; font-weight:bold; font-size:16px;">${(n.actorUsername || 'A').charAt(0).toUpperCase()}</div>`;

        el.innerHTML = `
            <div class="avatar" style="overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #fff44f;">${avatarHtml}</div>

            <div class="content">
                <div class="title">${n.title}</div>
                <div class="preview">${n.preview}</div>
                <div class="time">${n.time}</div>
            </div>
        `;

        el.onclick = () => {
            if (n.link) {
                window.location.href = n.link;
            }
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