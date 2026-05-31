document.addEventListener("DOMContentLoaded", function () {

    // ── Element refs ─────────────────────────────────────────────
    const loginModal = document.getElementById("loginModal");
    const signupModal = document.getElementById("signupModal");
    const closeLoginBtn = document.getElementById("closeLoginBtn");
    const closeSignupBtn = document.getElementById("closeSignupBtn");

    const guestNav = document.getElementById("guestNav");
    const userNav = document.getElementById("userNav");

    const libraryBtn = document.getElementById("libBtn");
    const profileBtn = document.getElementById("profileBtn");
    const settingBtn = document.getElementById("settingBtn");
    const notifBtn = document.getElementById("notifBtn");

    const loginForm = document.getElementById("loginForm");
    const signupForm = document.getElementById("signupForm");

    const loginMessage = document.getElementById("loginMessage");
    const signupMessage = document.getElementById("signupMessage");

    const searchInput = document.getElementById("searchInput");
    const searchResult = document.getElementById("searchResult");

    // ── Session state ─────────────────────────────────────────────
    let currentUser = null;

    // ── Cek session aktif ke server ────────────────────────────
    async function checkSession() {
        try {
            const res = await fetch("PHP/session_check.php");
            const data = await res.json();
            if (data.loggedIn) {
                currentUser = {
                    username: data.username,
                    name: data.name,
                    profilePic: data.profilePic ?? "Pic/profileicon.jpg",
                    role: data.role ?? "user"
                };
            } else {
                currentUser = null;
            }
        } catch {
            currentUser = null;
        }
        updateNavbar();
    }

    // ── Navbar ──────────────────────────────────────────────
    function updateNavbar() {
        if (currentUser) {
            guestNav.style.display = "none";
            userNav.style.display = "flex";

            // Set foto profil dari database (bukan hardcoded)
            if (profileBtn) {
                profileBtn.src = currentUser.profilePic;
                profileBtn.onerror = function () {
                    this.src = "Pic/profileicon.jpg";
                };
            }

            // Dynamic Admin Shield button in nav for admin users
            let adminBtn = document.getElementById("adminPanelBtn");
            if (currentUser.role === "admin") {
                if (!adminBtn) {
                    adminBtn = document.createElement("div");
                    adminBtn.id = "adminPanelBtn";
                    adminBtn.title = "Admin Panel";
                    adminBtn.style.cssText = `
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background: rgba(255, 244, 79, 0.12);
                        border: 1.5px solid rgba(255, 244, 79, 0.3);
                        color: #fff44f;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        margin-left: 20px;
                        margin-top: 8px;
                        flex-shrink: 0;
                    `;

                    // Hover effects using JS event listeners
                    adminBtn.addEventListener("mouseenter", () => {
                        adminBtn.style.background = "rgba(255, 244, 79, 0.25)";
                        adminBtn.style.transform = "scale(1.08)";
                        adminBtn.style.boxShadow = "0 0 12px rgba(255, 244, 79, 0.4)";
                        adminBtn.style.borderColor = "#fff44f";
                    });
                    adminBtn.addEventListener("mouseleave", () => {
                        adminBtn.style.background = "rgba(255, 244, 79, 0.12)";
                        adminBtn.style.transform = "scale(1)";
                        adminBtn.style.boxShadow = "none";
                        adminBtn.style.borderColor = "rgba(255, 244, 79, 0.3)";
                    });

                    adminBtn.innerHTML = `<span class="material-symbols-outlined" style="color: #fff44f; font-size: 20px;">shield</span>`;
                    adminBtn.addEventListener("click", () => {
                        window.location.href = "Etmin.php";
                    });
                    userNav.appendChild(adminBtn);
                } else {
                    adminBtn.style.display = "flex";
                }
            } else if (adminBtn) {
                adminBtn.style.display = "none";
            }

        } else {
            guestNav.style.display = "flex";
            userNav.style.display = "none";
            const adminBtn = document.getElementById("adminPanelBtn");
            if (adminBtn) adminBtn.style.display = "none";
        }
    }

    // ── Toast notification ───────────────────────────────────────
    function showToast(message, type = "success") {
        // Hapus toast lama kalau ada
        const existing = document.getElementById("authToast");
        if (existing) existing.remove();

        const toast = document.createElement("div");
        toast.id = "authToast";
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            background: ${type === "success" ? "#2d8a4e" : "#c0392b"};
            color: #fff;
            padding: 14px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        `;
        document.body.appendChild(toast);

        // Fade in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.opacity = "1";
                toast.style.transform = "translateY(0)";
            });
        });

        // Fade out setelah 3 detik
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(-10px)";
            setTimeout(() => toast.remove(), 350);
        }, 3000);
    }

    // ── Deteksi query param ?loggedout=1 ─────────────────────────
    const params = new URLSearchParams(window.location.search);
    if (params.get("loggedout") === "1") {
        // Bersihkan param dari URL tanpa reload
        const cleanUrl = window.location.pathname;
        history.replaceState(null, "", cleanUrl);
        // Tampilkan toast setelah DOM siap
        setTimeout(() => showToast("✓ Berhasil logout. Sampai jumpa!"), 200);
    }

    // ── Modal helpers ─────────────────────────────────────────────
    function openModal(modal) {
        clearMessages();
        modal.style.display = "flex";
        document.body.classList.add("modal-open");
    }

    function closeModal(modal) {
        modal.style.display = "none";
        const anyOpen = loginModal.style.display === "flex" ||
            signupModal.style.display === "flex";
        if (!anyOpen) document.body.classList.remove("modal-open");
    }

    function clearMessages() {
        [loginMessage, signupMessage].forEach(el => {
            if (el) { el.textContent = ""; el.className = "form-message"; }
        });
    }

    function setMessage(el, text, type = "error") {
        el.textContent = text;
        el.className = "form-message " + type;
    }

    function setLoading(btn, loading) {
        btn.disabled = loading;
        btn.textContent = loading ? "Mohon tunggu..." : btn.dataset.label;
    }

    // ── Global fungsi dipanggil dari HTML onclick ─────────────────
    window.openLogin = () => openModal(loginModal);
    window.openSignup = () => openModal(signupModal);
    window.closeLogin = () => closeModal(loginModal);
    window.closeSignup = () => closeModal(signupModal);

    window.goToLibrary = function () {
        if (currentUser) window.location.href = "Library.html";
        else window.openLogin();
    };

    window.scrollSlider = function (button) {
        const slider = button.parentElement.querySelector(".slider");
        slider.scrollBy({ left: 300, behavior: "smooth" });
    };

    // ── Tutup modal ───────────────────────────────────────────────
    closeLoginBtn.addEventListener("click", window.closeLogin);
    closeSignupBtn.addEventListener("click", window.closeSignup);

    window.addEventListener("click", function (e) {
        if (e.target === loginModal) window.closeLogin();
        if (e.target === signupModal) window.closeSignup();
    });

    // ── Query param: ?auth=login / ?auth=signup ───────────────────
    const authParam = params.get("auth");
    if (authParam === "login") window.openLogin();
    if (authParam === "signup") window.openSignup();

    // ── Navigasi user-nav ─────────────────────────────────────────
    profileBtn.addEventListener("click", () => {
        currentUser ? window.location.href = "Profile.php" : window.openLogin();
    });
    settingBtn.addEventListener("click", () => {
        currentUser ? window.location.href = "Setting.php" : window.openLogin();
    });
    notifBtn.addEventListener("click", () => {
        currentUser ? window.location.href = "Notification.html" : window.openLogin();
    });
    libraryBtn.addEventListener("click", () => {
        currentUser ? window.location.href = "Library.html" : window.openLogin();
    });

    // ── LOGIN form submit → fetch PHP/login_action.php ────────────
    loginForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        clearMessages();

        const btn = loginForm.querySelector("button[type=submit]");
        setLoading(btn, true);

        const body = new FormData(loginForm);

        try {
            const res = await fetch("PHP/login_action.php", { method: "POST", body });
            const data = await res.json();

            if (data.success) {
                window.closeLogin();
                loginForm.reset();
                // Admins go directly to the admin portal
                if (data.role === "admin") {
                    window.location.href = "Etmin.php";
                    return;
                }
                await checkSession();
            } else {
                setMessage(loginMessage, data.message, "error");
            }
        } catch {
            setMessage(loginMessage, "Terjadi kesalahan. Coba lagi.", "error");
        } finally {
            setLoading(btn, false);
        }
    });

    // ── SIGN UP form submit → fetch PHP/register_action.php ───────
    signupForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        clearMessages();

        const btn = signupForm.querySelector("button[type=submit]");
        setLoading(btn, true);

        const body = new FormData(signupForm);

        try {
            const res = await fetch("PHP/register_action.php", { method: "POST", body });
            const data = await res.json();

            if (data.success) {
                // Public signup always creates a regular user
                await checkSession();
                window.closeSignup();
                signupForm.reset();
            } else {
                setMessage(signupMessage, data.message, "error");
            }
        } catch {
            setMessage(signupMessage, "Terjadi kesalahan. Coba lagi.", "error");
        } finally {
            setLoading(btn, false);
        }
    });

    // ── LOGOUT (dipanggil dari navbar jika ada tombol logout di homepage) ──
    window.doLogout = async function () {
        try {
            await fetch("PHP/logout.php", {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
        } finally {
            window.location.href = "homepage.php?loggedout=1";
        }
    };

    // ── Search (live, debounced, dari database) ──────────────────
    let searchTimer = null;

    /**
     * Kirim request ke PHP/search_stories.php lalu render hasilnya.
     * @param {string} keyword
     */
    async function fetchSearch(keyword) {
        try {
            const res = await fetch(`PHP/search_stories.php?q=${encodeURIComponent(keyword)}`);
            const data = await res.json();
            renderSearchResults(data);
        } catch {
            renderSearchResults([]);
        }
    }

    /**
     * Render array hasil ke dalam #searchResult.
     * @param {Array} items  – array of { story_id, title, cover, status }
     */
    function renderSearchResults(items) {
        searchResult.innerHTML = "";

        if (!Array.isArray(items) || items.length === 0) {
            searchResult.innerHTML =
                `<div class="search-item search-empty">Tidak ditemukan</div>`;
        } else {
            items.forEach(result => {
                const item = document.createElement("div");
                item.classList.add("search-item");

                if (result.type === "user") {
                    // ── Tampilan hasil USER ──
                    const avatar = result.profile_pic
                        ? result.profile_pic
                        : "Pic/profileicon.jpg";

                    item.innerHTML = `
                        <img class="search-cover" src="${avatar}"
                             onerror="this.src='Pic/profileicon.jpg'"
                             style="border-radius:50%;">
                        <div class="search-info">
                            <span class="search-title">${result.name}</span>
                            <span class="search-badge badge-user">@${result.username}</span>
                        </div>
                    `;
                    item.addEventListener("click", () => {
                        searchResult.style.display = "none";
                        searchInput.value = "";
                        window.location.href = `profile_person.php?id=${result.user_id}`;
                    });

                } else {
                    // ── Tampilan hasil STORY ──
                    const statusLabel = result.status === "published" ? "Terbit"
                        : result.status === "ongoing" ? "Ongoing" : "Draft";
                    const statusClass = result.status === "published" ? "badge-published"
                        : result.status === "ongoing" ? "badge-ongoing" : "badge-draft";
                    const coverSrc = result.cover
                        ? result.cover : "Pic/cover-placeholder.png";

                    item.innerHTML = `
                        <img class="search-cover" src="${coverSrc}"
                             onerror="this.src='Pic/cover-placeholder.png'">
                        <div class="search-info">
                            <span class="search-title">${result.title}</span>
                            <span class="search-badge ${statusClass}">${statusLabel}</span>
                        </div>
                    `;
                    item.addEventListener("click", () => {
                        searchResult.style.display = "none";
                        searchInput.value = "";
                        window.location.href = `Detstory.php?id=${result.story_id}`;
                    });
                }

                searchResult.appendChild(item);
            });
        }

        searchResult.style.display = "flex";
    }

    // Handle Enter key
    searchInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            const keyword = this.value.trim();
            if (keyword.length >= 2) {
                window.location.href = `search_result.php?q=${encodeURIComponent(keyword)}`;
            }
        }
    });

    // Debounce: tunggu 300 ms setelah huruf terakhir baru kirim request
    searchInput.addEventListener("input", function () {
        const keyword = this.value.trim();
        searchResult.style.display = "none";
        clearTimeout(searchTimer);

        if (keyword.length < 2) return;

        searchTimer = setTimeout(() => fetchSearch(keyword), 300);
    });

    // Tutup dropdown kalau klik di luar search bar
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".search-bar")) {
            searchResult.style.display = "none";
        }
    });

    // ── Init: simpan label tombol untuk loading state ─────────────
    loginForm.querySelectorAll("button[type=submit]").forEach(b => b.dataset.label = b.textContent);
    signupForm.querySelectorAll("button[type=submit]").forEach(b => b.dataset.label = b.textContent);

    // ── Cek session saat halaman load ─────────────────────────────
    checkSession();
});
