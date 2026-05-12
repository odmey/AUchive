window.addEventListener("DOMContentLoaded", function () {

    const storyData = JSON.parse(localStorage.getItem("storyData"));
    const chapterData = JSON.parse(localStorage.getItem("chapterData"));
    const bubbleChatData = localStorage.getItem("bubbleChatData");

    if (storyData) {
        document.getElementById("storyTitle").textContent = storyData.title;
    }

    if (chapterData) {
        document.getElementById("storyParagraph").innerHTML =
            "<h2>" + chapterData.chapterTitle + "</h2><p>" +
            chapterData.paragraph + "</p>";

        // AUTO SAVE PROGRESS
        saveProgress(chapterData.chapterId || 1);
    }

    if (bubbleChatData) {
        document.getElementById("chatStory").innerHTML = bubbleChatData;
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
async function saveProgress(chapterId) {

    try {
        const response = await fetch("save_progress.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                chapter_id: chapterId
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
        const response = await fetch("add_to_library.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                story_id: storyId
            })
        });

        const result = await response.json();

        if (result.success) {
            alert("Berhasil ditambahkan ke library");
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

    try {
        const response = await fetch("post_comment.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                comment: comment
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

    try {
        const response = await fetch("get_comments.php");
        const comments = await response.json();

        commentList.innerHTML = comments.map(c => `
            <div class="comment-item">
                <b>${c.username}</b>
                <p>${c.comment}</p>
            </div>
        `).join("");

    } catch (error) {
        console.error("Gagal load comments:", error);
    }
}