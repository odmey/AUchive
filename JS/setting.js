document.addEventListener("DOMContentLoaded", function () {
    const overlay = document.getElementById("overlay");
    const popup = document.getElementById("menuPopup");
    const popupTitle = document.getElementById("menuPopupTitle");
    const popupBody = document.getElementById("menuPopupBody");
    const popupActions = document.getElementById("menuPopupActions");
    const popupClose = document.getElementById("menuPopupClose");

    // ── NOTE: Tidak perlu fetch session_check lagi.
    // Setting.php sudah handle show/hide guest-view & user-view langsung dari PHP session.

    function closeMenuPopup() {
        popup.classList.remove("active");
        overlay.classList.remove("active");
        popup.setAttribute("aria-hidden", "true");
    }

    function addActionButtons(buttons) {
        popupActions.innerHTML = buttons;
        popupActions.querySelectorAll("[data-close='true']").forEach(btn => {
            btn.addEventListener("click", closeMenuPopup);
        });
    }

    async function openMenuPopup(type) {
        popup.classList.add("active");
        overlay.classList.add("active");
        popup.setAttribute("aria-hidden", "false");

        popupBody.innerHTML = "Loading...";
        popupActions.innerHTML = "";

        let profile = {};
        if (["account", "email"].includes(type)) {
            try {
                const res = await fetch("PHP/settings_get_profile.php");
                const data = await res.json();
                if (data.success) {
                    profile = data.data;
                }
            } catch (err) {
                console.error("Failed to load profile");
            }
        }

        popupBody.innerHTML = "";

        if (type === "account") {
            popupTitle.textContent = "Account Settings";
            popupBody.innerHTML = `
                <h4>Profile Information</h4>
                <input type="text" id="accountName" placeholder="Full Name" value="${profile.name || ''}">
                <textarea id="accountBio" placeholder="Bio">${profile.bio || ''}</textarea>
                <h4>Personal Details</h4>
                <input type="date" id="accountBirthDate" value="${profile.birth_date || ''}">
                <h4 style="color:#ff6b6b;">Danger Zone</h4>
                <p class="menu-popup-note">Delete your account permanently.</p>
                <input type="password" id="accountDeletePwd" placeholder="Password to confirm deletion" style="margin-bottom: 10px;">
                <button class="menu-popup-btn secondary" id="btnDeleteAccount" type="button">Delete Account</button>
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" id="btnSaveAccount" type="button">Save Changes</button>
            `);

            document.getElementById('btnSaveAccount').addEventListener('click', async () => {
                const fd = new FormData();
                fd.append('name', document.getElementById('accountName').value);
                fd.append('bio', document.getElementById('accountBio').value);
                fd.append('birth_date', document.getElementById('accountBirthDate').value);
                try {
                    const res = await fetch("PHP/settings_update_account.php", { method: 'POST', body: fd });
                    const result = await res.json();
                    alert(result.message);
                    if (result.success) closeMenuPopup();
                } catch (e) {
                    alert("Error updating account");
                }
            });

            document.getElementById('btnDeleteAccount').addEventListener('click', async () => {
                const pwd = document.getElementById('accountDeletePwd').value;
                if (!pwd) { alert("Please enter your password to confirm deletion."); return; }
                if (confirm("Are you sure you want to permanently delete your account? This action cannot be undone.")) {
                    const fd = new FormData();
                    fd.append('password', pwd);
                    try {
                        const res = await fetch("PHP/settings_delete_account.php", { method: 'POST', body: fd });
                        const result = await res.json();
                        alert(result.message);
                        if (result.success) window.location.href = "homepage.php";
                    } catch (e) {
                        alert("Error deleting account");
                    }
                }
            });
        }

        else if (type === "email") {
            popupTitle.textContent = "Email Settings";
            popupBody.innerHTML = `
                <h4>Change Email</h4>
                <input type="email" id="emailCurrent" placeholder="Current Email" value="${profile.email || ''}" readonly style="background-color:rgba(0,0,0,0.05); color:#666; cursor:not-allowed;">
                <input type="email" id="emailNew" placeholder="New Email">
                <input type="password" id="emailPwd" placeholder="Current Password">
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" id="btnSaveEmail" type="button">Save</button>
            `);

            document.getElementById('btnSaveEmail').addEventListener('click', async () => {
                const fd = new FormData();
                fd.append('new_email', document.getElementById('emailNew').value);
                fd.append('password', document.getElementById('emailPwd').value);
                try {
                    const res = await fetch("PHP/settings_update_email.php", { method: 'POST', body: fd });
                    const result = await res.json();
                    alert(result.message);
                    if (result.success) closeMenuPopup();
                } catch (e) {
                    alert("Error updating email");
                }
            });
        }

        else if (type === "password") {
            popupTitle.textContent = "Security Settings";
            popupBody.innerHTML = `
                <h4>Change Password</h4>
                <input type="password" id="pwdCurrent" placeholder="Current Password">
                <input type="password" id="pwdNew" placeholder="New Password">
                <input type="password" id="pwdConfirm" placeholder="Confirm Password">
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" id="btnSavePassword" type="button">Update</button>
            `);

            document.getElementById('btnSavePassword').addEventListener('click', async () => {
                const fd = new FormData();
                fd.append('current_password', document.getElementById('pwdCurrent').value);
                fd.append('new_password', document.getElementById('pwdNew').value);
                fd.append('confirm_password', document.getElementById('pwdConfirm').value);
                try {
                    const res = await fetch("PHP/settings_update_password.php", { method: 'POST', body: fd });
                    const result = await res.json();
                    alert(result.message);
                    if (result.success) closeMenuPopup();
                } catch (e) {
                    alert("Error updating password");
                }
            });
        }

        else if (type === "faq") {
            popupTitle.textContent = "Help Center";
            popupBody.innerHTML = `
                <p class="menu-popup-note">Check out our guides and tutorials to learn how to use AUchive.</p>
                <button class="menu-popup-btn secondary" onclick="window.location.href='Guide.php'" type="button" style="margin-top:15px; width:100%;">Go to Guides & Tutorials</button>
            `;
            addActionButtons(`
                <button class="menu-popup-btn primary" data-close="true" type="button">Close</button>
            `);
        }

        else if (type === "contact") {
            popupTitle.textContent = "Contact Us";
            popupBody.innerHTML = `
                <p class="menu-popup-note" style="margin-bottom:8px;">Email: support@auchive.com</p>
                <p class="menu-popup-note" style="margin-bottom:14px;">WhatsApp: +62 812-XXXX</p>
                <textarea placeholder="Your message..."></textarea>
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" data-close="true" type="button">Send</button>
            `);
        }

        else if (type === "logout") {
            popupTitle.textContent = "Log Out";
            popupBody.innerHTML = `
                <p class="menu-popup-note">Are you sure you want to log out?</p>
            `;

            popupActions.innerHTML = `
                <button class="menu-popup-btn secondary" id="cancelLogout" type="button">Cancel</button>
                <button class="menu-popup-btn primary" data-close="true" id="confirmLogout" type="button">Yes, Log Out</button>
            `;

            const cancelBtn = document.getElementById("cancelLogout");
            const confirmBtn = document.getElementById("confirmLogout");
            if (cancelBtn) {
                cancelBtn.addEventListener("click", closeMenuPopup);
            }

            if (confirmBtn) {
                confirmBtn.addEventListener("click", async function () {
                    this.disabled = true;
                    this.textContent = "Logging out...";

                    try {
                        const res = await fetch("PHP/logout.php", {
                            method: "POST",
                            credentials: "same-origin",
                            cache: "no-store",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        });

                        const data = await res.json();
                        if (data.success) {
                            window.location.href = "homepage.php";
                        }
                    } catch (err) {
                        alert("Logout gagal.");
                        this.disabled = false;
                        this.textContent = "Yes, Log Out";
                    }
                });
            }
        }
    }

    // ── Bind semua .item-set ──────────────────────────────────────
    document.querySelectorAll(".item-set").forEach(item => {
        item.addEventListener("click", function () {
            const type = this.dataset.popup;
            if (type) openMenuPopup(type);
        });
    });

    if (popupClose) popupClose.addEventListener("click", closeMenuPopup);
    if (overlay) overlay.addEventListener("click", closeMenuPopup);
    if (popup) popup.addEventListener("click", e => { if (e.target === popup) closeMenuPopup(); });

    document.addEventListener("keydown", e => { if (e.key === "Escape") closeMenuPopup(); });

    // ── Fungsi global untuk Guest view ────────────────────────────
    window.goLogin = () => window.location.href = "homepage.php?auth=login";
    window.goSignup = () => window.location.href = "homepage.php?auth=signup";
});