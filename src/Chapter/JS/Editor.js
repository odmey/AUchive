const STORY_ID = parseInt(document.getElementById('meta-story-id').value) || 0;
let CHAPTER_ID = parseInt(document.getElementById('meta-chapter-id').value) || 0;
let blockCount = 0;

document.getElementById('draftBtn').addEventListener('click', () => saveChapter('draft', true));
document.getElementById('publishBtn').addEventListener('click', () => saveChapter('published', true));
document.getElementById('previewBtn').addEventListener('click', () => {
    const title = document.querySelector('.editor-title').value.trim() || 'Untitled Chapter';
    const previewBody = document.querySelector('.preview-blocks-container');
    const previewTitle = document.querySelector('.preview-chapter-title');

    previewTitle.textContent = title;
    previewBody.innerHTML = generatePreviewHtml();

    const actualLink = document.getElementById('actualPreviewLink');
    if (actualLink) {
        if (CHAPTER_ID > 0) {
            actualLink.style.display = 'inline-block';
            actualLink.href = `Readingpage.php?story_id=${STORY_ID}&chapter_id=${CHAPTER_ID}&from=editor`;
        } else {
            actualLink.style.display = 'none';
        }
    }

    document.getElementById('previewModal').style.display = 'flex';
});

// Load blok yang sudah ada
if (CHAPTER_ID > 0) loadBlocks(CHAPTER_ID);

async function loadBlocks(chapterId) {
    try {
        const res = await fetch(`src/Chapter/PHP/get_blocks.php?chapter_id=${chapterId}&t=${Date.now()}`);
        const blocks = await res.json();
        blocks.forEach(b => {
            if (b.type === 'narration') {
                renderNarrationBlock(b.block_id, b.content, b.sort_order);
            } else if (b.type === 'roomchat') {
                renderRoomchatBlock(b.block_id, b.roomchat_id, b.contact_name, b.theme, b.bubbles || [], b.my_avatar, b.contact_avatar, b.bg_image, b.sort_order);
            }
        });
    } catch (err) {
        console.error('Gagal load blocks:', err);
    }
}

// ── NARASI ──────────────────────────────────
function addNarrationBlock() {
    // Kita hilangkan pengecekan CHAPTER_ID <= 0 di sini agar penulis bisa langsung menambah blok konten
    renderNarrationBlock(null, '', blockCount + 1);
}

function renderNarrationBlock(blockId, content, sortOrder) {
    blockCount++;
    const localId = 'block-' + blockCount;
    const container = document.getElementById('blocksContainer');
    const div = document.createElement('div');
    div.className = 'content-block narration-block';
    div.id = localId;
    div.dataset.blockId = blockId || '';
    div.dataset.sortOrder = sortOrder !== undefined ? sortOrder : blockCount;
    div.innerHTML = `
        <div class="block-label">
            <span>Naration</span>
            <div class="block-label-actions">
                <button class="block-action" onclick="saveNarration('${localId}')">save</button>
                <button class="block-action danger" onclick="deleteBlock('${localId}')">delete</button>
            </div>
        </div>
        <textarea class="narration-textarea" placeholder="Write the narration here...">${content || ''}</textarea>
    `;
    container.appendChild(div);
}

async function saveNarration(localId, showFeedback = true) {
    recalculateSortOrders();
    const div = document.getElementById(localId);
    const content = div.querySelector('.narration-textarea').value.trim();
    const blockId = div.dataset.blockId ? parseInt(div.dataset.blockId) : 0;

    // Auto-save chapter sebagai draft terlebih dahulu jika belum disimpan
    if (CHAPTER_ID <= 0) {
        const saved = await saveChapter('draft', false);
        if (!saved) return;
    }

    const res = await fetch('src/Chapter/PHP/save_block.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            chapter_id: CHAPTER_ID,
            block_id: blockId,
            type: 'narration',
            content: content,
            sort_order: parseInt(div.dataset.sortOrder)
        })
    });
    const result = await res.json();
    if (result.success) {
        div.dataset.blockId = result.block_id;
        if (showFeedback) showToast('Naration saved!');
    } else {
        if (showFeedback) alert('Failed to save: ' + result.message);
    }
}

// ── ROOMCHAT ─────────────────────────────────
function addRoomchatBlock() {
    // Kita hilangkan pengecekan CHAPTER_ID <= 0 di sini agar penulis bisa langsung menambah blok konten
    renderRoomchatBlock(null, null, 'Contact', 'wa', [], '', '', '', blockCount + 1);
}

function renderRoomchatBlock(blockId, roomchatId, contactName, theme, bubbles, myAvatar = '', contactAvatar = '', bgImage = '', sortOrder = null) {
    blockCount++;
    const localId = 'block-' + blockCount;
    const container = document.getElementById('blocksContainer');
    const div = document.createElement('div');
    div.className = 'content-block roomchat-block';
    div.id = localId;
    div.dataset.blockId = blockId || '';
    div.dataset.roomchatId = roomchatId || '';
    div.dataset.sortOrder = sortOrder !== null && sortOrder !== undefined ? sortOrder : blockCount;

    const isWa = theme === 'wa';
    const avatarHtml = contactAvatar ? `<img src="${contactAvatar}" alt="avatar">` : '👤';
    const bgStyle = bgImage ? `style="background-image: url(${bgImage}); background-size: cover; background-position: center;"` : '';

    div.innerHTML = `
        <div class="block-label roomchat-label">
            <span>Roomchat</span>
            <div class="block-label-actions">
                <button class="block-action" onclick="saveRoomchat('${localId}')">save</button>
                <button class="block-action danger" onclick="deleteBlock('${localId}')">delete</button>
            </div>
        </div>
        <input type="hidden" class="rc-theme" value="${theme || 'wa'}">
        <input type="hidden" class="rc-contact" value="${contactName || 'Contact'}">
        <input type="hidden" class="rc-my-avatar" value="${myAvatar || ''}">
        <input type="hidden" class="rc-contact-avatar" value="${contactAvatar || ''}">
        <input type="hidden" class="rc-bg-image" value="${bgImage || ''}">
        <div class="roomchat-preview theme-${theme}" id="rc-preview-${localId}">
            <div class="rc-header">
                <div class="rc-avatar">${avatarHtml}</div>
                <div>
                    <div class="rc-contact-name" id="rc-name-${localId}">${contactName || 'Contact'}</div>
                    <div class="rc-status">${isWa ? 'online' : 'iMessage'}</div>
                </div>
            </div>
            <div class="rc-bubbles" id="rc-bubbles-${localId}" ${bgStyle}>
                ${renderBubblesPreview(bubbles, myAvatar, contactAvatar, contactName, theme)}
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

function renderBubblesPreview(bubbles, myAvatar = '', contactAvatar = '', contactName = '', theme = 'wa') {
    if (!bubbles || bubbles.length === 0) return '<p class="rc-empty">Belum ada bubble. Klik "Edit Bubble Chat".</p>';

    // Check if there is any custom sender name or custom avatar to declare GC mode
    let isGroup = false;
    for (let b of bubbles) {
        if (b.sender_avatar || (b.position === 'left' && b.contact_name && b.contact_name !== contactName)) {
            isGroup = true;
            break;
        }
    }

    return bubbles.map(b => {
        const isLeft = b.position === 'left';

        let avHtml = '';
        if (b.sender_avatar) {
            avHtml = `<img src="${b.sender_avatar}" alt="avatar">`;
        } else {
            avHtml = isLeft
                ? (contactAvatar ? `<img src="${contactAvatar}" alt="avatar">` : '')
                : (myAvatar ? `<img src="${myAvatar}" alt="avatar">` : '');
        }

        let nameHtml = '';
        if (isLeft && isGroup && b.contact_name) {
            nameHtml = `<div class="bubble-sender-name">${b.contact_name}</div>`;
        }

        let imgHtml = '';
        if (b.bubble_image) {
            imgHtml = `<div class="bubble-img-wrap" style="margin-bottom: 4px;"><img src="${b.bubble_image}"></div>`;
        }

        // WhatsApp defaults: left #202c33, right #005c4b
        // iMessage defaults: left #e5e5ea, right #007aff
        const isWa = theme === 'wa';
        const defaultColor = isLeft ? (isWa ? '#202c33' : '#e5e5ea') : (isWa ? '#005c4b' : '#007aff');
        const isDefault = b.color && b.color.toLowerCase() === defaultColor.toLowerCase();
        const bgStyle = (b.color && !isDefault) ? `style="background:${b.color}"` : '';

        return `
            <div class="rc-bubble-row ${b.position}" 
                 data-sender="${b.contact_name || ''}" 
                 data-sender-avatar="${b.sender_avatar || ''}"
                 data-bubble-image="${b.bubble_image || ''}"
                 style="align-items: flex-end; gap: 6px; margin-bottom: 4px;">
                ${isLeft ? `<div class="rc-bubble-av">${avHtml}</div>` : ''}
                <div class="rc-bubble" ${bgStyle}>
                    ${nameHtml}
                    ${imgHtml}
                    ${b.bubble_text || ''}
                    <span class="rc-time">${b.time_label}</span>
                </div>
                ${!isLeft ? `<div class="rc-bubble-av">${avHtml}</div>` : ''}
            </div>
        `;
    }).join('');
}



async function saveRoomchat(localId, showFeedback = true) {
    recalculateSortOrders();
    const div = document.getElementById(localId);
    const blockId = div.dataset.blockId ? parseInt(div.dataset.blockId) : 0;
    const roomchatId = div.dataset.roomchatId ? parseInt(div.dataset.roomchatId) : 0;
    const theme = div.querySelector('.rc-theme').value;
    const contactName = div.querySelector('.rc-contact').value.trim();
    const myAvatar = div.querySelector('.rc-my-avatar') ? div.querySelector('.rc-my-avatar').value : '';
    const contactAvatar = div.querySelector('.rc-contact-avatar') ? div.querySelector('.rc-contact-avatar').value : '';
    const bgImage = div.querySelector('.rc-bg-image') ? div.querySelector('.rc-bg-image').value : '';

    // Auto-save chapter sebagai draft terlebih dahulu jika belum disimpan
    if (CHAPTER_ID <= 0) {
        const saved = await saveChapter('draft', false);
        if (!saved) return;
    }

    // Kalau block belum ada, buat dulu
    let finalBlockId = blockId;
    if (finalBlockId <= 0) {
        const resBlock = await fetch('src/Chapter/PHP/save_block.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chapter_id: CHAPTER_ID,
                block_id: 0,
                type: 'roomchat',
                content: '',
                sort_order: parseInt(div.dataset.sortOrder)
            })
        });
        const blockResult = await resBlock.json();
        if (!blockResult.success) {
            if (showFeedback) alert('Failed to create block.');
            return;
        }
        finalBlockId = blockResult.block_id;
        div.dataset.blockId = finalBlockId;
    }

    const res = await fetch('src/BubbleChat/PHP/save_roomchat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            block_id: finalBlockId,
            chapter_id: CHAPTER_ID,
            roomchat_id: roomchatId,
            theme: theme,
            contact_name: contactName,
            sort_order: parseInt(div.dataset.sortOrder),
            my_avatar: myAvatar || null,
            contact_avatar: contactAvatar || null,
            bg_image: bgImage || null
        })
    });
    const result = await res.json();
    if (result.success) {
        div.dataset.roomchatId = result.roomchat_id;
        if (showFeedback) showToast('Roomchat tersimpan!');
    } else {
        if (showFeedback) alert('Failed to save: ' + result.message);
    }
}

async function goToBubbleChat(localId) {
    const div = document.getElementById(localId);
    let roomchatId = div.dataset.roomchatId ? parseInt(div.dataset.roomchatId) : 0;

    // Jika roomchat belum pernah disimpan, otomatis simpan dulu
    if (roomchatId <= 0) {
        await saveRoomchat(localId, false);
        roomchatId = div.dataset.roomchatId ? parseInt(div.dataset.roomchatId) : 0;
    }

    if (roomchatId <= 0) {
        return; // Jika gagal (misal judul kosong)
    }

    window.location.href = `bubblechat.php?story_id=${STORY_ID}&chapter_id=${CHAPTER_ID}&roomchat_id=${roomchatId}`;
}

// ── HAPUS BLOK ───────────────────────────────
async function deleteBlock(localId) {
    const confirmed = await customConfirm('Delete this block?');
    if (!confirmed) return;
    const div = document.getElementById(localId);
    const blockId = div.dataset.blockId ? parseInt(div.dataset.blockId) : 0;

    if (blockId > 0) {
        await fetch('src/Chapter/PHP/delete_block.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ block_id: blockId })
        });
    }
    div.remove();
    showToast('Blok dihapus.');
}

// ── SAVE CHAPTER ─────────────────────────────
async function saveChapter(status = 'draft', showFeedback = true) {
    const title = document.querySelector('.editor-title').value.trim();
    if (!title) {
        if (showFeedback) alert('Chapter title cannot be empty.');
        return false;
    }

    const payload = {
        story_id: STORY_ID,
        chapter_id: CHAPTER_ID,
        chapter_title: title,
        chapter_text: '',
        status: status
    };

    try {
        const res = await fetch('src/Chapter/PHP/save_chapter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
            const isNew = (CHAPTER_ID === 0);
            CHAPTER_ID = result.chapter_id;
            document.getElementById('meta-chapter-id').value = CHAPTER_ID;

            // Simpan keseluruhan blok yang ada di editor
            await saveAllBlocks(false);

            if (isNew) {
                // Redirect untuk memuat chapter_id di URL dengan benar
                window.location.href = `Editor.php?story_id=${STORY_ID}&chapter_id=${CHAPTER_ID}`;
                return true;
            }

            if (showFeedback) {
                showToast(status === 'published' ? 'Chapter dipublikasikan!' : 'Draft tersimpan!');
                // Update badge di sidebar (tanpa reload)
                updateSidebarStatus(CHAPTER_ID, status);
            }
            return true;
        } else {
            if (showFeedback) alert('Failed to save: ' + result.message);
            return false;
        }
    } catch (err) {
        if (showFeedback) alert('Connection failed.');
        return false;
    }
}

function recalculateSortOrders() {
    const blocks = document.querySelectorAll('#blocksContainer .content-block');
    blocks.forEach((div, index) => {
        div.dataset.sortOrder = index + 1;
    });
}

// Menyimpan semua blok yang ada di DOM secara paralel
async function saveAllBlocks(showFeedback = false) {
    recalculateSortOrders();
    const blocks = document.querySelectorAll('#blocksContainer .content-block');
    const promises = [];
    for (const div of blocks) {
        const localId = div.id;
        if (div.classList.contains('narration-block')) {
            promises.push(saveNarration(localId, showFeedback));
        } else if (div.classList.contains('roomchat-block')) {
            promises.push(saveRoomchat(localId, showFeedback));
        }
    }
    await Promise.all(promises);
}

// Update badge status di sidebar chapter tanpa reload halaman
function updateSidebarStatus(chapterId, status) {
    const badge = document.getElementById('ch-status-' + chapterId);
    if (badge) {
        badge.textContent = status;
        badge.className = 'chapter-status ' + status;
    }
}

// ── CHAPTER NAVIGATION ───────────────────────
async function addNewChapter() {
    const title = document.querySelector('.editor-title').value.trim();
    if (title) {
        await saveChapter('draft', false);
    }
    window.location.href = `Editor.php?story_id=${STORY_ID}&new=1`;
}

async function deleteChapter(chapterId) {
    const confirmed = await customConfirm('Delete this chapter? All content inside will be permanently deleted.');
    if (!confirmed) return;
    try {
        const res = await fetch('src/Chapter/PHP/delete_chapter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ chapter_id: chapterId, story_id: STORY_ID })
        });
        const result = await res.json();
        if (result.success) {
            showToast('Chapter deleted!');
            setTimeout(() => { window.location.href = `Editor.php?story_id=${STORY_ID}`; }, 800);
        } else {
            alert('Failed to delete: ' + result.message);
        }
    } catch (err) {
        alert('Connection failed.');
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

// Live update judul chapter di sidebar secara real-time
const titleInput = document.querySelector('.editor-title');
if (titleInput) {
    titleInput.addEventListener('input', () => {
        const titleVal = titleInput.value.trim() || 'Untitled';
        const targetId = CHAPTER_ID === 0 ? 0 : CHAPTER_ID;
        const sidebarItem = document.getElementById('sidebar-ch-' + targetId);
        if (sidebarItem) {
            const titleSpan = sidebarItem.querySelector('.ch-title-text');
            if (titleSpan) {
                titleSpan.textContent = titleVal;
            }
        }
    });
}

// ── PREVIEW MODAL LOGIC ──────────────────────
function generatePreviewHtml() {
    const blocks = document.querySelectorAll('#blocksContainer .content-block');
    let html = '';

    blocks.forEach(div => {
        if (div.classList.contains('narration-block')) {
            const text = div.querySelector('.narration-textarea').value;
            html += `
                <div class="reader-narration">
                    ${text.replace(/\n/g, '<br>')}
                </div>
            `;
        } else if (div.classList.contains('roomchat-block')) {
            const theme = div.querySelector('.rc-theme').value;
            const contactName = div.querySelector('.rc-contact').value;
            const myAvatar = div.querySelector('.rc-my-avatar') ? div.querySelector('.rc-my-avatar').value : '';
            const contactAvatar = div.querySelector('.rc-contact-avatar') ? div.querySelector('.rc-contact-avatar').value : '';
            const bgImage = div.querySelector('.rc-bg-image') ? div.querySelector('.rc-bg-image').value : '';
            const isWa = theme === 'wa';

            const bubblesContainer = div.querySelector('.rc-bubbles');
            let bubblesHtml = '';
            const rows = bubblesContainer.querySelectorAll('.rc-bubble-row');
            if (rows.length === 0) {
                bubblesHtml = '<p style="color:#888;text-align:center;padding:20px;font-size:13px;">Belum ada bubble.</p>';
            } else {
                // Check if there is any custom sender name or custom avatar to declare GC mode
                let isGroup = false;
                for (let row of rows) {
                    const sender = row.getAttribute('data-sender');
                    const senderAvatar = row.getAttribute('data-sender-avatar');
                    if (senderAvatar || (row.classList.contains('left') && sender && sender !== contactName)) {
                        isGroup = true;
                        break;
                    }
                }

                rows.forEach(row => {
                    const position = row.classList.contains('left') ? 'left' : 'right';
                    const bubble = row.querySelector('.rc-bubble');

                    let textContent = '';
                    if (bubble) {
                        const temp = bubble.cloneNode(true);
                        const nameEl = temp.querySelector('.bubble-sender-name');
                        if (nameEl) nameEl.remove();
                        const imgEl = temp.querySelector('.bubble-img-wrap');
                        if (imgEl) imgEl.remove();
                        const timeEl = temp.querySelector('.rc-time');
                        if (timeEl) timeEl.remove();
                        textContent = temp.textContent.trim();
                    }

                    const bg = bubble ? bubble.style.backgroundColor : '';
                    const time = row.querySelector('.rc-time') ? row.querySelector('.rc-time').textContent.trim() : '';
                    const sender = row.getAttribute('data-sender');
                    const senderAvatar = row.getAttribute('data-sender-avatar');
                    const bubbleImage = row.getAttribute('data-bubble-image');

                    let imgHtml = '';
                    if (bubbleImage) {
                        imgHtml = `<div class="bubble-img-wrap" style="margin-bottom: 4px;"><img src="${bubbleImage}"></div>`;
                    }

                    let avHtml = '';
                    if (senderAvatar) {
                        avHtml = `<img src="${senderAvatar}" alt="avatar">`;
                    } else {
                        avHtml = position === 'left'
                            ? (contactAvatar ? `<img src="${contactAvatar}" alt="avatar">` : '👤')
                            : (myAvatar ? `<img src="${myAvatar}" alt="avatar">` : '🙂');
                    }

                    let nameHtml = '';
                    if (position === 'left' && isGroup && sender) {
                        nameHtml = `<div class="bubble-sender-name">${sender}</div>`;
                    }

                    bubblesHtml += `
                        <div class="reader-bubble-row ${position}">
                            ${position === 'left' ? `<div class="reader-bubble-av">${avHtml}</div>` : ''}
                            <div class="reader-bubble ${position}" style="background:${bg}">
                                ${nameHtml}
                                ${imgHtml}
                                ${textContent}
                                <span class="reader-bubble-time">${time}</span>
                            </div>
                            ${position === 'right' ? `<div class="reader-bubble-av">${avHtml}</div>` : ''}
                        </div>
                    `;
                });
            }

            const headerAvatarHtml = contactAvatar ? `<img src="${contactAvatar}" alt="avatar">` : '👤';
            const chatAreaStyle = bgImage ? `style="background-image: url(${bgImage}); background-size: cover; background-position: center;"` : '';

            html += `
                <div class="reader-roomchat theme-${theme}">
                    <div class="reader-chat-header">
                        <div class="reader-avatar">${headerAvatarHtml}</div>
                        <div>
                            <div class="reader-contact-name">${contactName}</div>
                            <div class="reader-contact-status">${isWa ? 'online' : 'iMessage'}</div>
                        </div>
                    </div>
                    <div class="reader-chat-area" ${chatAreaStyle}>
                        ${bubblesHtml}
                    </div>
                    <div class="reader-chat-inputbar">
                        <div class="reader-input-fake">${isWa ? 'Type a message' : 'iMessage'}</div>
                    </div>
                </div>
            `;
        }
    });

    if (!html) {
        html = '<p style="color:#888; text-align:center; padding:40px;">Belum ada konten untuk dipreview.</p>';
    }
    return html;
}

function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
}
