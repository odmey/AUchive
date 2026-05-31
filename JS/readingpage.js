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

    // LIKE BUTTON FOR CHAPTER IN READINGPAGE
    const likeChapterBtn = document.getElementById("likeChapterBtn");
    if (likeChapterBtn && typeof CURRENT_CHAPTER_ID !== 'undefined' && CURRENT_CHAPTER_ID > 0) {
        likeChapterBtn.addEventListener("click", async function () {
            likeChapterBtn.disabled = true;
            try {
                const response = await fetch("PHP/like_chapter_action.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ chapter_id: CURRENT_CHAPTER_ID })
                });
                const result = await response.json();
                if (result.success) {
                    const icon = likeChapterBtn.querySelector('.material-symbols-outlined');
                    if (result.action === 'liked') {
                        likeChapterBtn.classList.add("active");
                        if (icon) icon.textContent = "favorite";
                    } else {
                        likeChapterBtn.classList.remove("active");
                        if (icon) icon.textContent = "favorite_border";
                    }
                } else {
                    alert(result.message || "Gagal menyukai chapter.");
                }
            } catch (error) {
                console.error("Error liking chapter:", error);
            } finally {
                likeChapterBtn.disabled = false;
            }
        });
    }

    // TOGGLE CHAPTERS SIDEBAR (ARROW TAB)
    const chapterToggleBtn = document.getElementById("chapterToggleBtn");
    const chapterSidebar = document.getElementById("chapterSidebar");
    const toggleArrowIcon = document.getElementById("toggleArrowIcon");
    if (chapterToggleBtn && chapterSidebar && toggleArrowIcon) {
        chapterToggleBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            const isOpen = chapterSidebar.classList.toggle("show");
            if (isOpen) {
                toggleArrowIcon.textContent = "chevron_left";
            } else {
                toggleArrowIcon.textContent = "chevron_right";
            }
        });

        document.addEventListener("click", function (e) {
            if (!chapterSidebar.contains(e.target) && e.target !== chapterToggleBtn && !chapterToggleBtn.contains(e.target)) {
                chapterSidebar.classList.remove("show");
                toggleArrowIcon.textContent = "chevron_right";
            }
        });
    }
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
            <div class="comment-item" style="padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); margin-bottom: 8px; background: #181818; border-radius: 12px;">
                <b style="color: #FFF44F; font-size: 14px; display: block; margin-bottom: 4px;">${escapeHTML(c.username)}</b>
                <p style="margin: 0 0 8px 0; color: #e0e0e0; font-size: 13.5px; line-height: 1.4;">${escapeHTML(c.comment_text)}</p>
                <small style="color: #888; font-size: 11px;">${escapeHTML(c.created_at)}</small>
            </div>
        `).join("");

    } catch (error) {
        console.error("Gagal load comments:", error);
    }
}