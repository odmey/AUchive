const STORY_ID   = parseInt(document.getElementById('meta-story-id').value)   || 0;
let   CHAPTER_ID = parseInt(document.getElementById('meta-chapter-id').value) || 0;
let   blockCount = 0;

document.getElementById('draftBtn').addEventListener('click', () => saveChapter('draft', true));
document.getElementById('publishBtn').addEventListener('click', () => saveChapter('published', true));

// Load blok yang sudah ada
if (CHAPTER_ID > 0) loadBlocks(CHAPTER_ID);

async function loadBlocks(chapterId) {
    try {
        const res    = await fetch(`PHP/get_blocks.php?chapter_id=${chapterId}`);
        const blocks = await res.json();
        blocks.forEach(b => {
            if (b.type === 'narration') {
                renderNarrationBlock(b.block_id, b.content, b.sort_order);
            } else if (b.type === 'roomchat') {
                renderRoomchatBlock(b.block_id, b.roomchat_id, b.contact_name, b.theme, b.bubbles || []);
            }
        });
    } catch (err) {
        console.error('Gagal load blocks:', err);
    }
}

// ── NARASI ──────────────────────────────────
function addNarrationBlock() {
    if (CHAPTER_ID <= 0) {
        alert('Simpan judul bab dulu (klik Draft) sebelum tambah konten.');
        return;
    }
    renderNarrationBlock(null, '', blockCount);
}

function renderNarrationBlock(blockId, content, sortOrder) {
    blockCount++;
    const localId = 'block-' + blockCount;
    const container = document.getElementById('blocksContainer');
    const div = document.createElement('div');
    div.className = 'content-block narration-block';
    div.id = localId;
    div.dataset.blockId   = blockId || '';
    div.dataset.sortOrder = sortOrder;
    div.innerHTML = `
        <div class="block-label">
            <span>Narasi</span>
            <div class="block-label-actions">
                <button class="block-action" onclick="saveNarration('${localId}')">simpan</button>
                <button class="block-action danger" onclick="deleteBlock('${localId}')">hapus</button>
            </div>
        </div>
        <textarea class="narration-textarea" placeholder="Tulis narasi di sini...">${content || ''}</textarea>
    `;
    container.appendChild(div);
}

async function saveNarration(localId) {
    const div     = document.getElementById(localId);
    const content = div.querySelector('.narration-textarea').value.trim();
    const blockId = div.dataset.blockId ? parseInt(div.dataset.blockId) : 0;

    const res    = await fetch('PHP/save_block.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            chapter_id: CHAPTER_ID,
            block_id:   blockId,
            type:       'narration',
            content:    content,
            sort_order: parseInt(div.dataset.sortOrder)
        })
    });
    const result = await res.json();
    if (result.success) {
        div.dataset.blockId = result.block_id;
        showToast('Narasi tersimpan!');
    } else {
        alert('Gagal simpan: ' + result.message);
    }
}

// ── ROOMCHAT ─────────────────────────────────
function addRoomchatBlock() {
    if (CHAPTER_ID <= 0) {
        alert('Simpan judul bab dulu (klik Draft) sebelum tambah konten.');
        return;
    }
    renderRoomchatBlock(null, null, 'Contact', 'wa', []);
}

function renderRoomchatBlock(blockId, roomchatId, contactName, theme, bubbles) {
    blockCount++;
    const localId = 'block-' + blockCount;
    const container = document.getElementById('blocksContainer');
    const div = document.createElement('div');
    div.className = 'content-block roomchat-block';
    div.id = localId;
    div.dataset.blockId    = blockId    || '';
    div.dataset.roomchatId = roomchatId || '';
    div.dataset.sortOrder  = blockCount;

    const isWa = theme === 'wa';

    div.innerHTML = `
        <div class="block-label roomchat-label">
            <span>Roomchat</span>
            <div class="block-label-actions">
                <button class="block-action" onclick="saveRoomchat('${localId}')">simpan</button>
                <button class="block-action danger" onclick="deleteBlock('${localId}')">hapus</button>
            </div>
        </div>
        <div class="roomchat-settings">
            <div class="roomchat-setting-row">
                <label>Tema</label>
                <select class="rc-theme" onchange="updateRoomchatPreview('${localId}')">
                    <option value="wa" ${isWa ? 'selected' : ''}>WhatsApp (Dark)</option>
                    <option value="im" ${!isWa ? 'selected' : ''}>iMessage (Light)</option>
                </select>
            </div>
            <div class="roomchat-setting-row">
                <label>Nama Kontak</label>
                <input type="text" class="rc-contact" value="${contactName || 'Contact'}"
                       oninput="document.getElementById('rc-name-${localId}').textContent=this.value">
            </div>
        </div>
        <div class="roomchat-preview theme-${theme}" id="rc-preview-${localId}">
            <div class="rc-header">
                <div class="rc-avatar">👤</div>
                <div>
                    <div class="rc-contact-name" id="rc-name-${localId}">${contactName || 'Contact'}</div>
                    <div class="rc-status">${isWa ? 'online' : 'iMessage'}</div>
                </div>
            </div>
            <div class="rc-bubbles" id="rc-bubbles-${localId}">
                ${renderBubblesPreview(bubbles)}
            </div>
        </div>
        <div class="rc-edit-btn-row">
            <button class="bubble-btn" onclick="goToBubbleChat('${localId}')">
                + Edit Bubble Chat
            </button>
        </div>
    `;
    container.appendChild(div);
}

function renderBubblesPreview(bubbles) {
    if (!bubbles || bubbles.length === 0) return '<p class="rc-empty">Belum ada bubble. Klik "Edit Bubble Chat".</p>';
    return bubbles.map(b => `
        <div class="rc-bubble-row ${b.position}">
            <div class="rc-bubble" style="background:${b.color}">${b.bubble_text}
                <span class="rc-time">${b.time_label}</span>
            </div>
        </div>
    `).join('');
}

function updateRoomchatPreview(localId) {
    const div    = document.getElementById(localId);
    const theme  = div.querySelector('.rc-theme').value;
    const preview = document.getElementById('rc-preview-' + localId);
    preview.className = `roomchat-preview theme-${theme}`;
    const status = div.querySelector('.rc-status');
    if (status) status.textContent = theme === 'wa' ? 'online' : 'iMessage';
}

async function saveRoomchat(localId) {
    const div         = document.getElementById(localId);
    const blockId     = div.dataset.blockId     ? parseInt(div.dataset.blockId)    : 0;
    const roomchatId  = div.dataset.roomchatId  ? parseInt(div.dataset.roomchatId) : 0;
    const theme       = div.querySelector('.rc-theme').value;
    const contactName = div.querySelector('.rc-contact').value.trim();

    // Kalau block belum ada, buat dulu
    let finalBlockId = blockId;
    if (finalBlockId <= 0) {
        const resBlock = await fetch('PHP/save_block.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                chapter_id: CHAPTER_ID,
                block_id:   0,
                type:       'roomchat',
                content:    '',
                sort_order: parseInt(div.dataset.sortOrder)
            })
        });
        const blockResult = await resBlock.json();
        if (!blockResult.success) { alert('Gagal buat block.'); return; }
        finalBlockId = blockResult.block_id;
        div.dataset.blockId = finalBlockId;
    }

    const res    = await fetch('PHP/save_roomchat.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            block_id:     finalBlockId,
            chapter_id:   CHAPTER_ID,
            roomchat_id:  roomchatId,
            theme:        theme,
            contact_name: contactName,
            sort_order:   parseInt(div.dataset.sortOrder)
        })
    });
    const result = await res.json();
    if (result.success) {
        div.dataset.roomchatId = result.roomchat_id;
        showToast('Roomchat tersimpan!');
    } else {
        alert('Gagal simpan: ' + result.message);
    }
}

async function goToBubbleChat(localId) {
    const div        = document.getElementById(localId);
    const roomchatId = div.dataset.roomchatId ? parseInt(div.dataset.roomchatId) : 0;

    if (roomchatId <= 0) {
        alert('Simpan roomchat dulu sebelum edit bubble.');
        return;
    }
    // Simpan chapter dulu
    await saveChapter('draft', false);
    window.location.href = `bubblechat.php?chapter_id=${CHAPTER_ID}&roomchat_id=${roomchatId}`;
}

// ── HAPUS BLOK ───────────────────────────────
async function deleteBlock(localId) {
    if (!confirm('Hapus blok ini?')) return;
    const div     = document.getElementById(localId);
    const blockId = div.dataset.blockId ? parseInt(div.dataset.blockId) : 0;

    if (blockId > 0) {
        await fetch('PHP/delete_block.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ block_id: blockId })
        });
    }
    div.remove();
    showToast('Blok dihapus.');
}

// ── SAVE CHAPTER ─────────────────────────────
async function saveChapter(status = 'draft', showFeedback = true) {
    const title = document.querySelector('.editor-title').value.trim();
    if (!title) {
        if (showFeedback) alert('Judul bab tidak boleh kosong.');
        return false;
    }

    const payload = {
        story_id:      STORY_ID,
        chapter_id:    CHAPTER_ID,
        chapter_title: title,
        chapter_text:  '',
        status:        status
    };

    try {
        const res    = await fetch('PHP/save_chapter.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
            CHAPTER_ID = result.chapter_id;
            document.getElementById('meta-chapter-id').value = CHAPTER_ID;
            if (showFeedback) {
                showToast(status === 'published' ? 'Published!' : 'Draft tersimpan!');
                if (status === 'published') {
                    setTimeout(() => { window.location.href = `Readingpage.php?story_id=${STORY_ID}`; }, 1000);
                }
            }
            return true;
        } else {
            if (showFeedback) alert('Gagal simpan: ' + result.message);
            return false;
        }
    } catch (err) {
        if (showFeedback) alert('Koneksi gagal.');
        return false;
    }
}

// ── CHAPTER NAVIGATION ───────────────────────
async function addNewChapter() {
    const title = document.querySelector('.editor-title').value.trim();
    if (title) await saveChapter('draft', false);
    window.location.href = 'Editor.php';
}

async function deleteChapter(chapterId) {
    if (!confirm('Hapus chapter ini? Semua konten di dalamnya akan terhapus.')) return;
    try {
        const res    = await fetch('PHP/delete_chapter.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ chapter_id: chapterId, story_id: STORY_ID })
        });
        const result = await res.json();
        if (result.success) {
            showToast('Chapter dihapus!');
            setTimeout(() => { window.location.href = 'Editor.php'; }, 800);
        } else {
            alert('Gagal hapus: ' + result.message);
        }
    } catch (err) {
        alert('Koneksi gagal.');
    }
}

function showToast(msg) {
    let toast = document.getElementById('editorToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'editorToast';
        toast.style.cssText = `
            position:fixed; bottom:24px; right:24px;
            background:#1a1a1a; color:#fff;
            padding:10px 20px; border-radius:8px;
            font-size:13px; z-index:9999;
            opacity:0; transition:opacity .3s;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    setTimeout(() => { toast.style.opacity = '0'; }, 2000);
}