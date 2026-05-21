const avatars = { me: null, contact: null };
let bubbleSortOrder = 0;

const THEMES = {
    wa: {
        status: 'online',
        placeholder: 'Type a message',
        sendSvg: '<svg viewBox="0 0 24 24"><path d="M12 1a4 4 0 014 4v6a4 4 0 01-8 0V5a4 4 0 014-4zm-1 17.93V21h2v-2.07A8 8 0 0020 11h-2a6 6 0 01-12 0H4a8 8 0 007 7.93z"/></svg>',
        defColor: '#005c4b'
    },
    im: {
        status: '',
        sendSvg: '<svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
        defColor: '#007aff'
    },
};

function setTheme(t, card) {
    document.body.classList.remove('theme-wa', 'theme-ig', 'theme-im');
    document.body.classList.add('theme-' + t);
    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    const cfg = THEMES[t];
    document.getElementById('previewStatus').textContent = cfg.status;
    document.getElementById('ibarField').placeholder = cfg.placeholder;
    document.getElementById('ibarSend').innerHTML = cfg.sendSvg;
    document.getElementById('bubbleColor').value = cfg.defColor;
}

function loadAvatar(input, who) {
    const f = input.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = e => {
        avatars[who] = e.target.result;
        if (who === 'contact') {
            document.getElementById('previewAvatar').innerHTML = `<img src="${e.target.result}" alt="">`;
        }
    };
    r.readAsDataURL(f);
}

function loadBg(input) {
    const f = input.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = e => {
        const a = document.getElementById('chatArea');
        a.style.backgroundImage = `url(${e.target.result})`;
        a.style.backgroundSize = 'cover';
        a.style.backgroundPosition = 'center';
    };
    r.readAsDataURL(f);
}

function formatTime(v) {
    if (!v) return '';
    const [h, m] = v.split(':');
    const hr = parseInt(h), ap = hr >= 12 ? 'PM' : 'AM', h12 = hr % 12 || 12;
    return `${h12}:${m} ${ap}`;
}

function getChapterId() {
    const params = new URLSearchParams(window.location.search);
    return parseInt(params.get('chapter_id')) || 0;
}

function addBubble() {
    const msg     = document.getElementById('message').value.trim();
    const tv      = document.getElementById('time').value;
    const color   = document.getElementById('bubbleColor').value;
    const side    = document.querySelector('input[name="side"]:checked').value;
    const imgFile = document.getElementById('imageUpload').files[0];
    const sender  = document.getElementById('contactName').value.trim() || 'Unknown';
    const ts      = formatTime(tv);

    if (!msg && !imgFile) {
        const el = document.getElementById('message');
        el.classList.add('field-input-error');
        el.focus();
        setTimeout(() => el.classList.remove('field-input-error'), 800);
        return;
    }

    const row = document.createElement('div');
    row.className = `bubble-row ${side}`;
    const av = document.createElement('div');
    av.className = 'row-avatar';
    const avSrc = side === 'left' ? avatars.contact : avatars.me;
    av.innerHTML = avSrc ? `<img src="${avSrc}" alt="">` : (side === 'left' ? '👤' : '🙂');

    if (imgFile) {
        const r = new FileReader();
        r.onload = e => {
            const w = document.createElement('div');
            w.className = 'bubble-img-wrap';
            w.innerHTML = `<img src="${e.target.result}"><span class="bubble-time">${ts}</span>`;
            row.appendChild(av);
            row.appendChild(w);
            document.getElementById('chatArea').appendChild(row);
            scrollBottom();
        };
        r.readAsDataURL(imgFile);
        document.getElementById('imageUpload').value = '';
    } else {
        const b = document.createElement('div');
        b.className = 'bubble';
        b.style.background = color;
        b.innerHTML = `${escHtml(msg)}<div class="bubble-meta"><span class="bubble-time">${ts}</span></div>`;
        row.appendChild(av);
        row.appendChild(b);
        document.getElementById('chatArea').appendChild(row);
        scrollBottom();

        bubbleSortOrder++;
        postBubbleToAPI({
            chapter_id:  getChapterId(),
            roomchat_id: getRoomchatId(), 
            message:     msg,
            sender_name: sender,
            position:    side,
            color:       color,
            sort_order:  bubbleSortOrder,
            time_label:  ts
        });
    }

    document.getElementById('message').value = '';
}

async function postBubbleToAPI(data) {
    try {
        const res = await fetch('PHP/save_bubble.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (!result.success) {
            console.warn('Bubble tidak tersimpan:', result.message);
        }
    } catch (err) {
        console.error('Gagal kirim bubble:', err);
    }
}

function saveStory() {
    const chapterId = getChapterId();
    if (chapterId <= 0) {
        alert('Chapter ID tidak ditemukan.');
        return;
    }
    const btn = document.querySelector('.btn-save');
    btn.textContent = '✓ SAVED!';
    setTimeout(() => {
        window.location.href = `Editor.php?chapter_id=${chapterId}`;
    }, 800);
}

function clearChat() {
    document.getElementById('chatArea').innerHTML = '<div class="date-chip"><span>Today</span></div>';
    bubbleSortOrder = 0;
}

function scrollBottom() {
    const a = document.getElementById('chatArea');
    a.scrollTop = a.scrollHeight;
}

function escHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
}
function getRoomchatId() {
    const params = new URLSearchParams(window.location.search);
    return parseInt(params.get('roomchat_id')) || 0;
}