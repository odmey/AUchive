// Helper untuk mencegah XSS
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
}

window.addEventListener("DOMContentLoaded", function () {

    const storyData = JSON.parse(localStorage.getItem("storyData"));
    const chapterData = JSON.parse(localStorage.getItem("chapterData"));
    const bubbleChatData = localStorage.getItem("bubbleChatData");

    const storyTitleEl = document.getElementById("storyTitle");
    const storyParagraphEl = document.getElementById("storyParagraph");
    const chatStoryEl = document.getElementById("chatStory");

    if (storyTitleEl && storyData) {
        storyTitleEl.textContent = storyData.title;
    }

    if (storyParagraphEl && chapterData) {
        storyParagraphEl.innerHTML =
            "<h2>" + chapterData.chapterTitle + "</h2><p>" +
            chapterData.paragraph + "</p>";
    }

    if (chatStoryEl && bubbleChatData) {
        chatStoryEl.innerHTML = bubbleChatData;
    }

    // AUTO SAVE PROGRESS DARI DATABASE JIKA VARIABEL GLOBAL TERSEDIA
    if (typeof CURRENT_STORY_ID !== 'undefined' && CURRENT_STORY_ID > 0 &&
        typeof CURRENT_CHAPTER_ID !== 'undefined' && CURRENT_CHAPTER_ID > 0) {
        saveProgress(CURRENT_STORY_ID, CURRENT_CHAPTER_ID, CURRENT_PROGRESS_PCT || 0);
    } else if (chapterData) {
        // Fallback untuk localStorage editor/draft
        saveProgress(storyData?.id || 1, chapterData.chapterId || 1, 0);
    }

    loadComments();
});


window.onload = function () {

    const savedParagraph = localStorage.getItem("storyParagraph");
    const savedBubble = localStorage.getItem("bubbleChatData");

    const paragraphBox = document.getElementById("storyParagraph");
    const bubbleBox = document.getElementById("bubbleContainer");

    if (savedParagraph && paragraphBox) {
        paragraphBox.innerHTML = `
            <div class="story-narration">
                ${savedParagraph}
            </div>
        `;
    }

    if (savedBubble && bubbleBox) {
        bubbleBox.innerHTML = savedBubble;
    }
};


// SAVE PROGRESS
async function saveProgress(storyId, chapterId, progressPct) {

    try {
        const response = await fetch("PHP/save_progress.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                story_id: storyId,
                chapter_id: chapterId,
                progress_pct: progressPct
            })
        });

        const result = await response.json();
        console.log("Progress tersimpan:", result);

    } catch (error) {
        console.error("Gagal save progress:", error);
    }
}


// ADD TO LIBRARY
async function addToLibrary(storyId) {

    try {
        const response = await fetch("PHP/add_to_library.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                story_id: storyId,
                action: 'add'
            })
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message || "Berhasil ditambahkan ke library");
        } else {
            alert(result.message || "Gagal tambah library");
        }

    } catch (error) {
        console.error("Error add library:", error);
    }
}


// POST COMMENT
async function postComment() {

    const commentInput = document.getElementById("commentInput");
    const comment = commentInput.value.trim();

    if (!comment) {
        alert("Komentar tidak boleh kosong");
        return;
    }

    if (typeof CURRENT_CHAPTER_ID === 'undefined' || CURRENT_CHAPTER_ID <= 0) {
        alert("Chapter ID tidak valid.");
        return;
    }

    try {
        const response = await fetch("PHP/post_comment.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                chapter_id: CURRENT_CHAPTER_ID,
                comment_text: comment
            })
        });

        const result = await response.json();

        if (result.success) {
            commentInput.value = "";
            loadComments();
        } else {
            alert(result.message || "Gagal mengirim komentar");
        }

    } catch (error) {
        console.error("Error post comment:", error);
    }
}


// LOAD COMMENTS
async function loadComments() {

    const commentList = document.getElementById("commentList");

    if (!commentList) return;
    if (typeof CURRENT_CHAPTER_ID === 'undefined' || CURRENT_CHAPTER_ID <= 0) return;

    try {
        const response = await fetch("PHP/get_comments.php?chapter_id=" + CURRENT_CHAPTER_ID);
        const comments = await response.json();

        commentList.innerHTML = comments.map(c => `
            <div class="comment-item" style="padding: 10px; border-bottom: 1px solid #eee; margin-bottom: 8px;">
                <b style="color: #333; font-size: 14px;">${escapeHTML(c.username)}</b>
                <p style="margin: 4px 0 0; color: #555; font-size: 13px;">${escapeHTML(c.comment_text)}</p>
                <small style="color: #999; font-size: 11px;">${escapeHTML(c.created_at)}</small>
            </div>
        `).join("");

    } catch (error) {
        console.error("Gagal load comments:", error);
    }
}