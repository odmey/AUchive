const notifications = [
    {type:'story', title:'Cerita baru dari Arga', preview:'"Aku tidak menyangka pesan itu akan mengubah hidupku..."', time:'5 menit lalu', unread:true},
    {type:'social', title:'Dina menyukai ceritamu', preview:'"Langit malam itu terasa berbeda..."', time:'1 jam lalu', unread:true},
    {type:'social', title:'Komentar baru dari Raka', preview:'"Plot twist-nya gila banget!"', time:'3 jam lalu', unread:false},
];

function render(data) {
    const container = document.getElementById('notifContainer');
    const empty = document.getElementById('emptyState');

    container.innerHTML = '';

    if(data.length === 0) {
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
        }

        container.appendChild(el);
    });
}

function filterNotif(type, el) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');

    if(type === 'all') {
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

render(notifications);
