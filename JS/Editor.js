// Ambil story_id dan chapter_id dari meta tag yang disisipkan Editor.php
const STORY_ID   = parseInt(document.getElementById('meta-story-id').value)   || 0;
let   CHAPTER_ID = parseInt(document.getElementById('meta-chapter-id').value) || 0;

// Auto-save setiap 30 detik
// setInterval(() => saveChapter('draft', false), 30000);

// Tombol Draft
document.getElementById('draftBtn').addEventListener('click', () => {
    saveChapter('draft', true);
});

// Tombol Publish
document.getElementById('publishBtn').addEventListener('click', () => {
    saveChapter('published', true);
});

// Tombol Bubble Chat — buka setelah chapter tersimpan
document.getElementById('bubbleBtn').addEventListener('click', async () => {
    const saved = await saveChapter('draft', false);
    if (saved && CHAPTER_ID > 0) {
        window.location.href = `bubblechat.php?chapter_id=${CHAPTER_ID}`;
    } else {
        alert('Simpan chapter gagal. Pastikan judul bab sudah diisi.');
    }
});

// Fungsi utama simpan chapter
async function saveChapter(status = 'draft', showFeedback = true) {
    const title = document.querySelector('.editor-title').value.trim();
    const text  = document.querySelector('.editor-paragraph').value.trim();

    if (!title) {
        if (showFeedback) alert('Judul bab tidak boleh kosong.');
        return false;
    }

    const payload = {
        story_id:      STORY_ID,
        chapter_id:    CHAPTER_ID,   // 0 = baru, >0 = update
        chapter_title: title,
        chapter_text:  text,
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
            // Simpan chapter_id yang baru dibuat supaya request berikutnya jadi UPDATE
            CHAPTER_ID = result.chapter_id;
            document.getElementById('meta-chapter-id').value = CHAPTER_ID;

            if (showFeedback) {
                const label = status === 'published' ? 'Published!' : 'Draft tersimpan!';
                showToast(label);
                if (status === 'published') {
                    setTimeout(() => {
                        window.location.href = `Readingpage.php?story_id=${STORY_ID}`;
                    }, 1000);
                }
            }
            return true;

        } else {
            if (showFeedback) alert('Gagal simpan: ' + result.message);
            return false;
        }

    } catch (err) {
        console.error('Save chapter error:', err);
        if (showFeedback) alert('Koneksi ke server gagal.');
        return false;
    }
}

// Toast notifikasi kecil
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
// Load bubble preview kalau chapter_id sudah ada
if (CHAPTER_ID > 0) {
    loadBubblePreview(CHAPTER_ID);
}

async function loadBubblePreview(chapterId) {
    try {
        const res     = await fetch(`PHP/get_bubbles.php?chapter_id=${chapterId}`);
        const bubbles = await res.json();

        if (bubbles.length === 0) return;

        const section = document.getElementById('bubblePreviewSection');
        const area    = document.getElementById('bubblePreviewArea');

        section.style.display = 'block';
        area.innerHTML = '';

        bubbles.forEach(b => {
            const row = document.createElement('div');
            row.className = `bubble-row ${b.position}`;
            row.innerHTML = `
                <div class="bubble" style="background:${b.color}">
                    ${b.bubble_text}
                    <div class="bubble-meta">
                        <span class="bubble-time">${b.time_label}</span>
                    </div>
                </div>
            `;
            area.appendChild(row);
        });

    } catch (err) {
        console.error('Gagal load bubble preview:', err);
    }
}