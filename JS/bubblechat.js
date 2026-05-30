const avatars = { me: null, contact: null };
let bubbleSortOrder = 0;
let chatBgImage = null;

const THEMES = {
    wa: {
        status: 'online',
        placeholder: 'Type a message',
        sendSvg: '<svg viewBox="0 0 24 24"><path d="M12 1a4 4 0 014 4v6a4 4 0 01-8 0V5a4 4 0 014-4zm-1 17.93V21h2v-2.07A8 8 0 0020 11h-2a6 6 0 01-12 0H4a8 8 0 007 7.93z"/></svg>',
        senderBg: '#005c4b',
        receiverBg: '#202c33'
    },
    im: {
        status: '',
        placeholder: 'iMessage',
        sendSvg: '<svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
        senderBg: '#007aff',
        receiverBg: '#e5e5ea'
    },
};

function setTheme(t, card) {
    document.body.classList.remove('theme-wa', 'theme-ig', 'theme-im');
    document.body.classList.add('theme-' + t);
    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    const cfg = THEMES[t];
    document.getElementById('previewStatus').textContent = cfg.status;
    document.getElementById('ibarField').placeholder = cfg.placeholder || 'Type a message';
    document.getElementById('ibarSend').innerHTML = cfg.sendSvg;
    
    const side = document.querySelector('input[name="side"]:checked') ? document.querySelector('input[name="side"]:checked').value : 'left';
    document.getElementById('bubbleColor').value = side === 'left' ? cfg.receiverBg : cfg.senderBg;
}

function updateRenderedAvatars() {
    document.querySelectorAll('.bubble-row.left .row-avatar').forEach(av => {
        av.innerHTML = avatars.contact ? `<img src="${avatars.contact}" alt="">` : '👤';
    });
    document.querySelectorAll('.bubble-row.right .row-avatar').forEach(av => {
        av.innerHTML = avatars.me ? `<img src="${avatars.me}" alt="">` : '🙂';
    });
}

function loadAvatar(input, who) {
    const f = input.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = e => {
        avatars[who] = e.target.result;
        if (who === 'contact') {
            document.getElementById('previewAvatar').innerHTML = `<img src="${e.target.result}" alt="">`;
        }
        updateRenderedAvatars();
    };
    r.readAsDataURL(f);
}

function loadBg(input) {
    const f = input.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = e => {
        chatBgImage = e.target.result;
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

function renderBubbleHtml(msg, side, color, ts, senderName) {
    const row = document.createElement('div');
    row.className = `bubble-row ${side}`;
    
    const av = document.createElement('div');
    av.className = 'row-avatar';
    const avSrc = side === 'left' ? avatars.contact : avatars.me;
    av.innerHTML = avSrc ? `<img src="${avSrc}" alt="">` : (side === 'left' ? '👤' : '🙂');
    
    const b = document.createElement('div');
    b.className = 'bubble';
    b.style.background = color;
    b.innerHTML = `${escHtml(msg)}<div class="bubble-meta"><span class="bubble-time">${ts}</span></div>`;
    
    row.appendChild(av);
    row.appendChild(b);
    document.getElementById('chatArea').appendChild(row);
    scrollBottom();
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

    if (imgFile) {
        const row = document.createElement('div');
        row.className = `bubble-row ${side}`;
        const av = document.createElement('div');
        av.className = 'row-avatar';
        const avSrc = side === 'left' ? avatars.contact : avatars.me;
        av.innerHTML = avSrc ? `<img src="${avSrc}" alt="">` : (side === 'left' ? '👤' : '🙂');

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
        renderBubbleHtml(msg, side, color, ts, sender);
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

async function saveStory() {
    const chapterId = getChapterId();
    const roomchatId = getRoomchatId();
    if (chapterId <= 0 || roomchatId <= 0) {
        alert('Chapter ID atau Roomchat ID tidak ditemukan.');
        return;
    }

    const themeCard = document.querySelector('.theme-card.active');
    let theme = 'wa';
    if (themeCard) {
        if (themeCard.classList.contains('tc-im')) {
            theme = 'im';
        }
    }
    const contactName = document.getElementById('contactName').value.trim() || 'Contact';

    const btn = document.querySelector('.btn-save');
    btn.textContent = 'Saving...';
    btn.disabled = true;

    try {
        const res = await fetch('PHP/save_roomchat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                block_id: 1, // dummy value, it will update by roomchat_id anyway
                chapter_id: chapterId,
                roomchat_id: roomchatId,
                theme: theme,
                contact_name: contactName,
                my_avatar: avatars.me,
                contact_avatar: avatars.contact,
                bg_image: chatBgImage
            })
        });
        const result = await res.json();
        if (result.success) {
            btn.textContent = '✓ SAVED!';
            setTimeout(() => {
                window.location.href = `Editor.php?story_id=${STORY_ID}&chapter_id=${chapterId}`;
            }, 800);
        } else {
            alert('Gagal menyimpan roomchat: ' + result.message);
            btn.textContent = 'SAVE STORY';
            btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Koneksi gagal saat menyimpan.');
        btn.textContent = 'SAVE STORY';
        btn.disabled = false;
    }
}

async function clearChat() {
    if (!confirm('Hapus semua bubble di roomchat ini? Data di database juga akan terhapus.')) return;
    
    const roomchatId = getRoomchatId();
    if (roomchatId <= 0) {
        document.getElementById('chatArea').innerHTML = '<div class="date-chip"><span>Today</span></div>';
        bubbleSortOrder = 0;
        return;
    }

    try {
        const res = await fetch('PHP/clear_bubbles.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ roomchat_id: roomchatId })
        });
        const result = await res.json();
        if (result.success) {
            document.getElementById('chatArea').innerHTML = '<div class="date-chip"><span>Today</span></div>';
            bubbleSortOrder = 0;
        } else {
            alert('Gagal hapus bubble: ' + result.message);
        }
    } catch (err) {
        alert('Koneksi gagal.');
    }
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

document.addEventListener('DOMContentLoaded', () => {
    // Listen for side radio changes to automatically swap default colors
    document.querySelectorAll('input[name="side"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const side = e.target.value;
            const themeCard = document.querySelector('.theme-card.active');
            let theme = 'wa';
            if (themeCard && themeCard.classList.contains('tc-im')) {
                theme = 'im';
            }
            const cfg = THEMES[theme];
            document.getElementById('bubbleColor').value = side === 'left' ? cfg.receiverBg : cfg.senderBg;
        });
    });

    if (typeof INITIAL_ROOMCHAT !== 'undefined' && INITIAL_ROOMCHAT) {
        // Load theme
        const theme = INITIAL_ROOMCHAT.theme || 'wa';
        const card = document.querySelector(`.tc-${theme}`);
        if (card) {
            setTheme(theme, card);
        }
        
        // Load contact name
        if (INITIAL_ROOMCHAT.contact_name) {
            document.getElementById('contactName').value = INITIAL_ROOMCHAT.contact_name;
            document.getElementById('previewName').textContent = INITIAL_ROOMCHAT.contact_name;
        }
        
        // Load avatars
        if (INITIAL_ROOMCHAT.my_avatar) {
            avatars.me = INITIAL_ROOMCHAT.my_avatar;
        }
        if (INITIAL_ROOMCHAT.contact_avatar) {
            avatars.contact = INITIAL_ROOMCHAT.contact_avatar;
            document.getElementById('previewAvatar').innerHTML = `<img src="${INITIAL_ROOMCHAT.contact_avatar}" alt="">`;
        }
        
        // Load background
        if (INITIAL_ROOMCHAT.bg_image) {
            chatBgImage = INITIAL_ROOMCHAT.bg_image;
            const a = document.getElementById('chatArea');
            a.style.backgroundImage = `url(${INITIAL_ROOMCHAT.bg_image})`;
            a.style.backgroundSize = 'cover';
            a.style.backgroundPosition = 'center';
        }
        
        updateRenderedAvatars();
    }
    
    if (typeof INITIAL_BUBBLES !== 'undefined' && Array.isArray(INITIAL_BUBBLES)) {
        INITIAL_BUBBLES.forEach(b => {
            renderBubbleHtml(b.bubble_text, b.position, b.color, b.time_label, b.contact_name);
            if (parseInt(b.sort_order) > bubbleSortOrder) {
                bubbleSortOrder = parseInt(b.sort_order);
            }
        });
    }
});