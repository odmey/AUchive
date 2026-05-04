document.addEventListener("DOMContentLoaded", function () {
    const Auth = window.AUchiveAuth;

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

    const stories = [
        "Romance Campus",
        "Fake Dating AU",
        "Mafia Love Story",
        "Best Friend to Lover",
        "Roommate AU",
        "CEO x Intern",
        "Enemies to Lovers",
        "Fantasy Kingdom",
        "Royal Secret Love",
        "Coffee Shop AU"
    ];

    function openModal(modal) {
        clearMessages();
        modal.style.display = "flex";
        document.body.classList.add("modal-open");
    }

    function closeModal(modal) {
        modal.style.display = "none";

        const loginOpen = loginModal.style.display === "flex";
        const signupOpen = signupModal.style.display === "flex";

        if (!loginOpen && !signupOpen) {
            document.body.classList.remove("modal-open");
        }
    }

    function clearMessages() {
        if (loginMessage) {
            loginMessage.textContent = "";
            loginMessage.classList.remove("error", "success");
        }
        if (signupMessage) {
            signupMessage.textContent = "";
            signupMessage.classList.remove("error", "success");
        }
    }

    function setMessage(el, text, type = "error") {
        el.textContent = text;
        el.classList.remove("error", "success");
        el.classList.add(type);
    }

    window.openLogin = function () {
        openModal(loginModal);
    };

    window.openSignup = function () {
        openModal(signupModal);
    };

    window.closeLogin = function () {
        closeModal(loginModal);
    };

    window.closeSignup = function () {
        closeModal(signupModal);
    };

    window.goToLibrary = function () {
        if (Auth.isLoggedIn()) {
            window.location.href = "Library.html";
        } else {
            window.openLogin();
        }
    };

    window.scrollSlider = function (button) {
        const slider = button.parentElement.querySelector(".slider");
        slider.scrollBy({
            left: 300,
            behavior: "smooth"
        });
    };

    closeLoginBtn.addEventListener("click", window.closeLogin);
    closeSignupBtn.addEventListener("click", window.closeSignup);

    window.addEventListener("click", function (event) {
        if (event.target === loginModal) window.closeLogin();
        if (event.target === signupModal) window.closeSignup();
    });

    // Buka modal dari query setting page
    const authParam = new URLSearchParams(window.location.search).get("auth");
    if (authParam === "login") {
        window.openLogin();
    } else if (authParam === "signup") {
        window.openSignup();
    }

    function updateNavbar() {
        if (Auth.isLoggedIn()) {
            guestNav.style.display = "none";
            userNav.style.display = "flex";
        } else {
            guestNav.style.display = "flex";
            userNav.style.display = "none";
        }
    }

    // Profile icon → profile page
    profileBtn.addEventListener("click", function () {
        if (Auth.isLoggedIn()) {
            window.location.href = "Profile.html";
        } else {
            window.openLogin();
        }
    });

    // Settings icon → settings page
    settingBtn.addEventListener("click", function () {
        if (Auth.isLoggedIn()) {
            window.location.href = "Setting.html";
        } else {
            window.openLogin();
        }
    });

    notifBtn.addEventListener("click", function () {
        if (!Auth.isLoggedIn()) {
            window.openLogin();
            return;
        }
        window.location.href = "Notification.html";
    });
    

    libraryBtn.addEventListener("click", function () {
        if (Auth.isLoggedIn()) {
            window.location.href = "Library.html";
        } else {
            window.openLogin();
        }
    });

    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        clearMessages();

        const formData = new FormData(loginForm);
        const email = Auth.normalizeEmail(formData.get("email"));
        const password = String(formData.get("password") || "").trim();

        if (!email) {
            setMessage(loginMessage, "Email wajib diisi.");
            return;
        }

        if (!Auth.isValidEmail(email)) {
            setMessage(loginMessage, "Format email tidak valid.");
            return;
        }

        if (!password) {
            setMessage(loginMessage, "Password wajib diisi.");
            return;
        }

        const user = Auth.findUserByEmail(email);
        if (!user) {
            setMessage(loginMessage, "Akun belum terdaftar. Silakan sign up dulu.");
            return;
        }

        if (user.password !== password) {
            setMessage(loginMessage, "Password salah.");
            return;
        }

        Auth.saveSession({
            username: user.username,
            email: user.email
        });

        updateNavbar();
        window.closeLogin();
    });

    signupForm.addEventListener("submit", function (e) {
        e.preventDefault();
        clearMessages();

        const formData = new FormData(signupForm);
        const username = String(formData.get("username") || "").trim();
        const email = Auth.normalizeEmail(formData.get("email"));
        const password = String(formData.get("password") || "").trim();

        if (!username) {
            setMessage(signupMessage, "Username wajib diisi.");
            return;
        }

        if (username.length < 3) {
            setMessage(signupMessage, "Username minimal 3 karakter.");
            return;
        }

        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            setMessage(signupMessage, "Username hanya boleh huruf, angka, dan underscore.");
            return;
        }

        if (!email) {
            setMessage(signupMessage, "Email wajib diisi.");
            return;
        }

        if (!Auth.isValidEmail(email)) {
            setMessage(signupMessage, "Format email tidak valid.");
            return;
        }

        if (!password) {
            setMessage(signupMessage, "Password wajib diisi.");
            return;
        }

        if (password.length < 8) {
            setMessage(signupMessage, "Password minimal 8 karakter.");
            return;
        }

        if (Auth.findUserByEmail(email)) {
            setMessage(signupMessage, "Email sudah terdaftar. Silakan login.");
            return;
        }

        if (Auth.findUserByUsername(username)) {
            setMessage(signupMessage, "Username sudah dipakai.");
            return;
        }

        Auth.upsertUser({
            username,
            email,
            password
        });

        Auth.saveSession({
            username,
            email
        });

        updateNavbar();
        window.closeSignup();
    });

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();
        searchResult.innerHTML = "";

        if (keyword === "") {
            searchResult.style.display = "none";
            return;
        }

        const filtered = stories.filter(story =>
            story.toLowerCase().includes(keyword)
        );

        if (filtered.length === 0) {
            searchResult.innerHTML = `<div class="search-item">No result found</div>`;
        } else {
            filtered.forEach(story => {
                const item = document.createElement("div");
                item.classList.add("search-item");
                item.textContent = story;

                item.addEventListener("click", function () {
                    searchInput.value = story;
                    searchResult.style.display = "none";
                });

                searchResult.appendChild(item);
            });
        }

        searchResult.style.display = "flex";
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".search-bar")) {
            searchResult.style.display = "none";
        }
    });

    updateNavbar();
});