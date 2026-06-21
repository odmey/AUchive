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
            const res = await fetch("src/User/PHP/session_check.php");
            const data = await res.json();
            if (data.loggedIn) {
                currentUser = {
                    username: data.username,
                    name: data.name,
                    profilePic: data.profilePic ?? "Pic/PP kosongan.jpg",
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
        const joinNowBtn = document.getElementById("joinNowBtn");
        if (currentUser) {
            guestNav.style.display = "none";
            userNav.style.display = "flex";

            // Hide "Join Now" hero button after login
            if (joinNowBtn) joinNowBtn.style.display = "none";

            // Set foto profil dari database (bukan hardcoded)
            if (profileBtn) {
                profileBtn.src = currentUser.profilePic;
                profileBtn.onerror = function () {
                    this.src = "Pic/PP kosongan.jpg";
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
                        display: flex;
                        align-items: center;
                        cursor: pointer;
                        transition: 0.2s;
                    `;

                    adminBtn.innerHTML = `<span class="material-symbols-outlined" style="font-size: 24px; color: rgba(255, 255, 255, 0.7); transition: color 0.2s;">shield</span>`;
                    
                    adminBtn.addEventListener("mouseenter", () => {
                        const icon = adminBtn.querySelector(".material-symbols-outlined");
                        if (icon) icon.style.color = "rgb(255, 244, 79)";
                    });
                    adminBtn.addEventListener("mouseleave", () => {
                        const icon = adminBtn.querySelector(".material-symbols-outlined");
                        if (icon) icon.style.color = "rgba(255, 255, 255, 0.7)";
                    });

                    adminBtn.addEventListener("click", () => {
                        window.location.href = "Etmin.php";
                    });
                    
                    // Insert before notification button to place it on the left
                    const notifBtn = document.getElementById("notifBtn");
                    if (notifBtn) {
                        userNav.insertBefore(adminBtn, notifBtn);
                    } else {
                        userNav.appendChild(adminBtn);
                    }
                } else {
                    adminBtn.style.display = "flex";
                }
            } else if (adminBtn) {
                adminBtn.style.display = "none";
            }

        } else {
            guestNav.style.display = "flex";
            userNav.style.display = "none";

            // Show "Join Now" hero button when logged out
            if (joinNowBtn) joinNowBtn.style.display = "";

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
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            z-index: 9999;
            background: ${type === "success" ? "rgba(45, 138, 78, 0.95)" : "rgba(192, 57, 43, 0.95)"};
            color: #fff;
            padding: 16px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            opacity: 0;
            transition: opacity 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.15);
            text-align: center;
            font-family: 'Poppins', sans-serif;
            pointer-events: none;
        `;
        document.body.appendChild(toast);

        // Fade in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.opacity = "1";
                toast.style.transform = "translate(-50%, -50%) scale(1)";
            });
        });

        // Fade out setelah 3 detik
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translate(-50%, -50%) scale(0.95)";
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
        setTimeout(() => showToast("✓ Logged out successfully. See you again!"), 200);
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

        // Clear query parameters from URL when modal is closed
        const params = new URLSearchParams(window.location.search);
        if (params.has("auth")) {
            params.delete("auth");
            params.delete("redirect");
            const newUrl = window.location.pathname + (params.toString() ? "?" + params.toString() : "");
            history.replaceState(null, "", newUrl);
        }
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
        btn.textContent = loading ? "Please wait...." : btn.dataset.label;
    }

    // ── Global fungsi dipanggil dari HTML onclick ─────────────────
    window.openLogin = () => openModal(loginModal);
    window.openSignup = () => openModal(signupModal);
    window.closeLogin = () => closeModal(loginModal);
    window.closeSignup = () => closeModal(signupModal);

    window.goToLibrary = function () {
        if (currentUser) {
            window.location.href = "Library.php";
        } else {
            customConfirm("You must log in to view your Library. Would you like to log in now?").then((confirmed) => {
                if (confirmed) {
                    window.openLogin();
                }
            });
        }
    };

    window.scrollSlider = function (button, direction = "right") {
        const slider = button.parentElement.querySelector(".slider");
        const amount = direction === "left" ? -300 : 300;
        slider.scrollBy({ left: amount, behavior: "smooth" });
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
        currentUser ? window.location.href = "Notification.php" : window.openLogin();
    });
    libraryBtn.addEventListener("click", () => {
        if (currentUser) {
            window.location.href = "Library.php";
        } else {
            customConfirm("You must log in to view your Library. Would you like to log in now?").then((confirmed) => {
                if (confirmed) {
                    window.openLogin();
                }
            });
        }
    });

    // ── LOGIN form submit → fetch src/User/PHP/login_action.php ────────────
    loginForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        clearMessages();

        const btn = loginForm.querySelector("button[type=submit]");
        setLoading(btn, true);

        const body = new FormData(loginForm);

        try {
            const res = await fetch("src/User/PHP/login_action.php", { method: "POST", body });
            const data = await res.json();

            if (data.success) {
                window.closeLogin();
                loginForm.reset();
                // Admins go directly to the admin portal
                if (data.role === "admin") {
                    window.location.href = "Etmin.php";
                    return;
                }
                // Redirect ke halaman sebelumnya jika ada param redirect
                const redirectUrl = params.get("redirect");
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                    return;
                }
                await checkSession();
            } else {
                setMessage(loginMessage, data.message, "error");
            }
        } catch {
            setMessage(loginMessage, "An error occurred. Please try again.", "error");
        } finally {
            setLoading(btn, false);
        }
    });

    // ── SIGN UP form submit → fetch src/User/PHP/register_action.php ───────
    signupForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        clearMessages();

        const btn = signupForm.querySelector("button[type=submit]");
        setLoading(btn, true);

        const body = new FormData(signupForm);

        try {
            const res = await fetch("src/User/PHP/register_action.php", { method: "POST", body });
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
            setMessage(signupMessage, "An error occurred. Please try again.", "error");
        } finally {
            setLoading(btn, false);
        }
    });

    // ── Search (live, debounced, dari database) ──────────────────
    let searchTimer = null;

    /**
     * Kirim request ke src/Story/PHP/search_stories.php lalu render hasilnya.
     * @param {string} keyword
     */
    async function fetchSearch(keyword) {
        try {
            const res = await fetch(`src/Story/PHP/search_stories.php?q=${encodeURIComponent(keyword)}`);
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

        const historyDiv = document.getElementById("searchHistory");
        if (historyDiv) historyDiv.style.display = "none";

        if (!Array.isArray(items) || items.length === 0) {
            searchResult.innerHTML =
                `<div class="search-item search-empty">No Results</div>`;
        } else {
            items.forEach(result => {
                const item = document.createElement("div");
                item.classList.add("search-item");

                if (result.type === "user") {
                    // ── Tampilan hasil USER ──
                    const avatar = result.profile_pic
                        ? result.profile_pic
                        : "Pic/PP kosongan.jpg";

                    item.innerHTML = `
                        <img class="search-cover" src="${avatar}"
                             onerror="this.src='Pic/PP kosongan.jpg'"
                             style="border-radius:50%;">
                        <div class="search-info">
                            <span class="search-title">${result.name}</span>
                            <span class="search-badge badge-user">@${result.username}</span>
                        </div>
                    `;
                    item.addEventListener("click", () => {
                        const keyword = searchInput.value.trim();
                        if (keyword) saveHistoryItem(keyword);
                        searchResult.style.display = "none";
                        searchInput.value = "";
                        window.location.href = `profile_person.php?id=${result.user_id}`;
                    });

                } else {
                    // ── Tampilan hasil STORY ──
                    const prog = result.progress_status || "ongoing";
                    const statusLabel = prog === "complete" ? "Complete"
                        : prog === "hiatus" ? "Hiatus" : "Ongoing";
                    const statusClass = prog === "complete" ? "badge-published"
                        : prog === "hiatus" ? "badge-hiatus" : "badge-ongoing";
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
                        const keyword = searchInput.value.trim();
                        if (keyword) saveHistoryItem(keyword);
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

    // ─── SEARCH HISTORY LOGIC ──────────────────────────────────
    const HISTORY_KEY = "auchive_search_history";

    function getHistory() {
        try {
            const data = localStorage.getItem(HISTORY_KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    }

    function saveHistoryItem(item) {
        if (!item || item.trim().length < 2) return;
        const history = getHistory();
        const cleanItem = item.trim();
        const index = history.indexOf(cleanItem);
        if (index > -1) {
            history.splice(index, 1);
        }
        history.unshift(cleanItem);
        if (history.length > 5) {
            history.pop();
        }
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    }

    function deleteHistoryItem(item) {
        const history = getHistory();
        const index = history.indexOf(item);
        if (index > -1) {
            history.splice(index, 1);
            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
        }
    }

    function clearAllHistory() {
        localStorage.removeItem(HISTORY_KEY);
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.deleteSingleHistoryItem = function (query, e) {
        e.stopPropagation();
        deleteHistoryItem(query);
        const keyword = searchInput ? searchInput.value.trim() : "";
        renderSearchHistory(keyword);
        if (searchInput) searchInput.focus();
    };

    window.clearAllSearchHistory = function (e) {
        e.stopPropagation();
        clearAllHistory();
        renderSearchHistory();
        if (searchInput) searchInput.focus();
    };

    function renderSearchHistory(filterKeyword = "") {
        if (!searchInput) return;

        let historyDiv = document.getElementById("searchHistory");
        if (!historyDiv) {
            historyDiv = document.createElement("div");
            historyDiv.id = "searchHistory";
            historyDiv.className = "search-history-dropdown";
            const searchBar = document.querySelector(".search-bar");
            if (searchBar) {
                searchBar.appendChild(historyDiv);
            } else {
                return;
            }
        }

        let history = getHistory();
        if (filterKeyword) {
            const cleanKeyword = filterKeyword.toLowerCase().trim();
            history = history.filter(item => item.toLowerCase().includes(cleanKeyword));
        }

        // Always show dropdown — even when empty, show empty state
        if (history.length === 0) {
            historyDiv.innerHTML = `
                <div class="history-header">
                    <span class="history-title">${filterKeyword ? "No Matching History" : "Recent Searches"}</span>
                </div>
                <div class="history-empty">
                    <span class="material-symbols-outlined" style="color:rgba(255,244,79,0.4);font-size:28px;margin-bottom:6px;">manage_search</span>
                    <span style="color:rgba(255,255,255,0.4);font-size:13px;">No recent searches yet</span>
                </div>
            `;
            historyDiv.style.display = "flex";
            return;
        }

        let html = `
            <div class="history-header">
                <span class="history-title">${filterKeyword ? "Matching History" : "Recent Searches"}</span>
                <button type="button" class="history-clear-btn" onclick="clearAllSearchHistory(event)">Clear All</button>
            </div>
            <div class="history-items">
        `;

        history.forEach(item => {
            html += `
                <div class="history-item" data-query="${encodeURIComponent(item)}">
                    <span class="material-symbols-outlined history-clock-icon">history</span>
                    <span class="history-item-text">${escapeHtml(item)}</span>
                    <span class="material-symbols-outlined history-delete-btn" onclick="deleteSingleHistoryItem('${item.replace(/'/g, "\\'")}', event)">close</span>
                </div>
            `;
        });

        html += `</div>`;
        historyDiv.innerHTML = html;

        // Attach event listeners for clicking the query
        historyDiv.querySelectorAll(".history-item").forEach(el => {
            el.querySelector(".history-item-text").addEventListener("click", () => {
                const query = decodeURIComponent(el.dataset.query);
                searchInput.value = query;
                saveHistoryItem(query);
                window.location.href = `search_result.php?q=${encodeURIComponent(query)}`;
            });
        });

        historyDiv.style.display = "flex";
    }

    // Bind focus and click events to show history dropdown immediately
    const showHistoryDropdown = () => {
        if (!searchInput) return;
        const keyword = searchInput.value.trim();
        if (keyword.length === 0) {
            renderSearchHistory();
            if (searchResult) searchResult.style.display = "none";
        } else {
            renderSearchHistory(keyword);
        }
    };

    if (searchInput) {
        searchInput.addEventListener("focus", showHistoryDropdown);
        searchInput.addEventListener("click", showHistoryDropdown);
    }

    // Handle Enter key
    if (searchInput) {
        searchInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                const keyword = this.value.trim();
                if (keyword.length >= 2) {
                    saveHistoryItem(keyword);
                    window.location.href = `search_result.php?q=${encodeURIComponent(keyword)}`;
                }
            }
        });

        // Debounce: tunggu 300 ms setelah huruf terakhir baru kirim request
        searchInput.addEventListener("input", function () {
            const keyword = this.value.trim();
            if (searchResult) searchResult.style.display = "none";

            const historyDiv = document.getElementById("searchHistory");
            clearTimeout(searchTimer);

            if (keyword.length === 0) {
                renderSearchHistory();
                return;
            }

            // Show matching search history items (filter by typed text)
            renderSearchHistory(keyword);
            if (historyDiv) historyDiv.style.display = "flex";

            if (keyword.length < 2) return;
            searchTimer = setTimeout(() => fetchSearch(keyword), 300);
        });
    }

    // Tutup dropdown kalau klik di luar search bar
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".search-bar")) {
            if (searchResult) searchResult.style.display = "none";
            const historyDiv = document.getElementById("searchHistory");
            if (historyDiv) historyDiv.style.display = "none";
        }
    });

    // ── Init: simpan label tombol untuk loading state ─────────────
    if (loginForm) loginForm.querySelectorAll("button[type=submit]").forEach(b => b.dataset.label = b.textContent);
    if (signupForm) signupForm.querySelectorAll("button[type=submit]").forEach(b => b.dataset.label = b.textContent);

    // ── Cek session saat halaman load ─────────────────────────────
    checkSession();
});


