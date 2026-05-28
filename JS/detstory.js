document.addEventListener("DOMContentLoaded", function () {
    const likeBtn = document.getElementById("likeBtn");
    const saveBtn = document.getElementById("saveBtn");
    const followBtn = document.getElementById("followBtn");

    const likeCountEl = document.getElementById("likeCount");

    // ── 1. LIKE / UNLIKE ───────────────────────────────────────
    if (likeBtn) {
        likeBtn.addEventListener("click", async function () {
            likeBtn.disabled = true;
            try {
                const response = await fetch("PHP/like_action.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        story_id: STORY_ID
                    })
                });
                const data = await response.json();

                if (data.success) {
                    if (data.action === "liked") {
                        likeBtn.classList.add("active");
                        likeBtn.textContent = "Liked";
                    } else {
                        likeBtn.classList.remove("active");
                        likeBtn.textContent = "Like";
                    }
                    if (likeCountEl) {
                        likeCountEl.textContent = data.total_likes;
                    }
                } else {
                    alert(data.message || "Gagal menyukai cerita.");
                }
            } catch (error) {
                console.error("Error liking:", error);
            } finally {
                likeBtn.disabled = false;
            }
        });
    }

    // ── 2. SAVE / REMOVE LIBRARY ───────────────────────────────
    if (saveBtn) {
        saveBtn.addEventListener("click", async function () {
            saveBtn.disabled = true;
            const isSaved = saveBtn.classList.contains("active");
            const action = isSaved ? "remove" : "add";

            try {
                const response = await fetch("PHP/add_to_library.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        story_id: STORY_ID,
                        action: action
                    })
                });
                const data = await response.json();

                if (data.success) {
                    if (action === "add") {
                        saveBtn.classList.add("active");
                        saveBtn.textContent = "Saved";
                    } else {
                        saveBtn.classList.remove("active");
                        saveBtn.textContent = "Save";
                    }
                } else {
                    alert(data.message || "Gagal memproses library.");
                }
            } catch (error) {
                console.error("Error with library:", error);
            } finally {
                saveBtn.disabled = false;
            }
        });
    }

    // ── 3. FOLLOW / UNFOLLOW AUTHOR ────────────────────────────
    if (followBtn) {
        followBtn.addEventListener("click", async function () {
            followBtn.disabled = true;
            try {
                const response = await fetch("PHP/follow_action.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        following_id: AUTHOR_ID
                    })
                });
                const data = await response.json();

                if (data.success) {
                    if (data.action === "followed") {
                        followBtn.classList.add("active");
                        followBtn.textContent = "Following";
                    } else {
                        followBtn.classList.remove("active");
                        followBtn.textContent = "Follow";
                    }
                } else {
                    alert(data.message || "Gagal mengikuti penulis.");
                }
            } catch (error) {
                console.error("Error following:", error);
            } finally {
                followBtn.disabled = false;
            }
        });
    }
});
