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

    function openMenuPopup(type) {
        popup.classList.add("active");
        overlay.classList.add("active");
        popup.setAttribute("aria-hidden", "false");

        popupBody.innerHTML = "";
        popupActions.innerHTML = "";

        if (type === "account") {
            popupTitle.textContent = "Account Settings";
            popupBody.innerHTML = `
                <h4>Profile Information</h4>
                <input type="text" placeholder="Full Name">
                <textarea placeholder="Bio"></textarea>
                <h4>Personal Details</h4>
                <input type="date">
                <select>
                    <option>Gender</option>
                    <option>Female</option>
                    <option>Male</option>
                    <option>Other</option>
                </select>
                <h4 style="color:#ff6b6b;">Danger Zone</h4>
                <p class="menu-popup-note">Deactivate or delete your account permanently.</p>
                <button class="menu-popup-btn secondary" data-close="true" type="button">Deactivate Account</button>
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" data-close="true" type="button">Save Changes</button>
            `);
        }

        else if (type === "email") {
            popupTitle.textContent = "Email Settings";
            popupBody.innerHTML = `
                <h4>Change Email</h4>
                <input type="email" placeholder="Current Email">
                <input type="email" placeholder="New Email">
                <h4>Secondary Email</h4>
                <input type="email" placeholder="Backup Email">
                <h4>Email Preferences</h4>
                <div class="menu-popup-toggle"><span>Login Only</span><input type="checkbox"></div>
                <div class="menu-popup-toggle"><span>Receive Updates</span><input type="checkbox" checked></div>
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" data-close="true" type="button">Save</button>
            `);
        }

        else if (type === "password") {
            popupTitle.textContent = "Security Settings";
            popupBody.innerHTML = `
                <h4>Change Password</h4>
                <input type="password" placeholder="Current Password">
                <input type="password" placeholder="New Password">
                <input type="password" placeholder="Confirm Password">
                <h4>Security</h4>
                <div class="menu-popup-toggle"><span>Enable 2FA</span><input type="checkbox"></div>
                <div class="menu-popup-toggle"><span>Biometric Login</span><input type="checkbox"></div>
                <h4>Login Activity</h4>
                <p class="menu-popup-note">Last login: Bali • Chrome</p>
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Cancel</button>
                <button class="menu-popup-btn primary" data-close="true" type="button">Update</button>
            `);
        }

        else if (type === "notifications") {
            popupTitle.textContent = "Notifications";
            popupBody.innerHTML = `
                <div class="menu-popup-toggle"><span>Push Notifications</span><input type="checkbox" checked></div>
                <div class="menu-popup-toggle"><span>In-App Notifications</span><input type="checkbox" checked></div>
                <div class="menu-popup-toggle"><span>Marketing</span><input type="checkbox"></div>
            `;
            addActionButtons(`
                <button class="menu-popup-btn secondary" data-close="true" type="button">Close</button>
                <button class="menu-popup-btn primary" data-close="true" type="button">Save</button>
            `);
        }

        else if (type === "faq") {
            popupTitle.textContent = "Help Center";
            popupBody.innerHTML = `
                <input type="text" placeholder="Search..." style="margin-bottom:15px;">
                <ul class="menu-popup-list">
                    <li>Create story</li>
                    <li>Change password</li>
                    <li>Delete account</li>
                </ul>
                <p class="menu-popup-note">Find guides and tutorials here.</p>
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
                        const res = await fetch("/Project/AUchive/PHP/logout.php", {
                            method: "POST",
                            credentials: "same-origin",
                            cache: "no-store",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        });

                        const data = await res.json();
                        if (data.success) {
                            window.location.href = "/Project/AUchive/homepage.php";
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