const followBtn = document.getElementById("followBtn");
const followersCountVal = document.getElementById("followersCountVal");

if (followBtn) {
    followBtn.addEventListener("click", async () => {
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
                    followBtn.textContent = "Following";
                    followBtn.style.background = "transparent";
                    followBtn.style.color = "#FFF44F";
                    followBtn.style.border = "1px solid #FFF44F";
                } else {
                    followBtn.textContent = "Follow";
                    followBtn.style.background = "#FFF44F";
                    followBtn.style.color = "black";
                    followBtn.style.border = "none";
                }

                if (followersCountVal) {
                    followersCountVal.textContent = data.follower_count;
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

/* FOLLOWERS / FOLLOWING MODAL */

const followersBtn = document.getElementById("followersBtn");
const followingBtn = document.getElementById("followingBtn");
const followModal = document.getElementById("followModal");
const closeFollowModal = document.getElementById("closeFollowModal");
const modalTitle = document.getElementById("modalTitle");
const userList = document.getElementById("userList");

/* OPEN MODAL */

function openFollowModal(title, users) {
    if (!followModal || !modalTitle || !userList) return;
    modalTitle.textContent = title;
    userList.innerHTML = "";

    if (!users || users.length === 0) {
        userList.innerHTML = `<div style="text-align:center; color:#888; padding:30px; font-size:13px;">No data available.</div>`;
    } else {
        users.forEach(user => {
            const redirectUrl = `profile_person.php?id=${user.user_id}`;
            userList.innerHTML += `
                <div class="user-item" onclick="window.location.href='${redirectUrl}'" style="display:flex; align-items:center; gap:14px; padding:10px 20px; cursor:pointer; transition:background 0.2s;">
                    <img src="${user.image}" onerror="this.src='Pic/PP kosongan.jpg'" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                    <div class="user-info" style="display:flex; flex-direction:column; gap:2px;">
                        <h4 style="margin:0; font-size:14px; color:#fff; font-weight:600;">${user.name}</h4>
                        <p style="margin:0; font-size:12px; color:#aaa;">${user.username}</p>
                    </div>
                </div>
            `;
        });
    }

    followModal.style.display = "flex";
}

/* BUTTON EVENTS */

if (followersBtn) {
    followersBtn.addEventListener("click", () => {
        openFollowModal("Followers", followersData);
    });
}

if (followingBtn) {
    followingBtn.addEventListener("click", () => {
        openFollowModal("Following", followingData);
    });
}

/* CLOSE MODAL */

if (closeFollowModal) {
    closeFollowModal.addEventListener("click", () => {
        followModal.style.display = "none";
    });
}

/* CLOSE OUTSIDE */

if (followModal) {
    followModal.addEventListener("click", (e) => {
        if (e.target === followModal) {
            followModal.style.display = "none";
        }
    });
}

/* REPORT MODAL CONTROLLER */
const reportUserBtn = document.getElementById("reportUserBtn");
const reportModalContainer = document.getElementById("reportModalContainer");
const closeReportModal = document.getElementById("closeReportModal");
const cancelReportBtn = document.getElementById("cancelReportBtn");
const reportForm = document.getElementById("reportForm");

if (reportUserBtn && reportModalContainer) {
    reportUserBtn.addEventListener("click", () => {
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
    reportModalContainer.addEventListener("click", (e) => {
        if (e.target === reportModalContainer) {
            hideReportModal();
        }
    });
}

if (reportForm) {
    reportForm.addEventListener("submit", async (e) => {
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
                    reported_user_id: AUTHOR_ID,
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
