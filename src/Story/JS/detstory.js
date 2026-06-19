document.addEventListener("DOMContentLoaded", function () {
    const favBtn = document.getElementById("favBtn");
    const saveBtn = document.getElementById("saveBtn");
    const followBtn = document.getElementById("followBtn");

    // ── 1. FAVORITE / UNFAVORITE ───────────────────────────────
    if (favBtn) {
        favBtn.addEventListener("click", async function () {
            favBtn.disabled = true;
            try {
                const response = await fetch("src/Library/PHP/favorite_action.php", {
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
                    if (data.is_favorite) {
                        favBtn.classList.add("active");
                        favBtn.textContent = "Favorited";
                        if (saveBtn) {
                            saveBtn.classList.add("active");
                            saveBtn.textContent = "Saved";
                        }
                    } else {
                        favBtn.classList.remove("active");
                        favBtn.textContent = "Favorite";
                    }
                } else {
                    alert(data.message || "Failed to favorite story.");
                }
            } catch (error) {
                console.error("Error favoriting:", error);
            } finally {
                favBtn.disabled = false;
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
                const response = await fetch("src/Library/PHP/add_to_library.php", {
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
                    alert(data.message || "Failed to process library.");
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
                const response = await fetch("src/User/PHP/follow_action.php", {
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
                    alert(data.message || "Failed to follow author.");
                }
            } catch (error) {
                console.error("Error following:", error);
            } finally {
                followBtn.disabled = false;
            }
        });
    }

    // ── 4. REPORT STORY ────────────────────────────────────────
    const reportStoryBtn = document.getElementById("reportStoryBtn");
    const reportModalContainer = document.getElementById("reportModalContainer");
    const closeReportModal = document.getElementById("closeReportModal");
    const cancelReportBtn = document.getElementById("cancelReportBtn");
    const reportForm = document.getElementById("reportForm");

    if (reportStoryBtn && reportModalContainer) {
        reportStoryBtn.addEventListener("click", function () {
            reportModalContainer.style.display = "flex";
        });
    }

    function hideReportModal() {
        if (reportModalContainer) {
            reportModalContainer.style.display = "none";
            if (reportForm) reportForm.reset();
        }
    }

    if (closeReportModal) {
        closeReportModal.addEventListener("click", hideReportModal);
    }

    if (cancelReportBtn) {
        cancelReportBtn.addEventListener("click", hideReportModal);
    }

    if (reportModalContainer) {
        reportModalContainer.addEventListener("click", function (e) {
            if (e.target === reportModalContainer) {
                hideReportModal();
            }
        });
    }

    if (reportForm) {
        reportForm.addEventListener("submit", async function (e) {
            e.preventDefault();

            const reason = document.getElementById("reportReason").value;
            const description = document.getElementById("reportDescription").value;
            const submitBtn = document.getElementById("submitReportBtn");

            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch("src/Report/PHP/report_action.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        reported_story_id: STORY_ID,
                        reason: reason,
                        description: description
                    })
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    hideReportModal();
                } else {
                    alert(data.message || "Failed to submit report.");
                }
            } catch (error) {
                console.error("Error submitting report:", error);
                alert("System error occurred while submitting report.");
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});

