// DATA
let stories = [];
let continueReadingStories = [];

// LOAD LIBRARY FROM PHP API
async function loadLibrary() {
    try {
        const response = await fetch("PHP/get_library.php");
        const data = await response.json();

        if (data.success) {
            // Map get_library.php fields to the fields expected by Lib.js
            stories = (data.all_stories || []).map(s => ({
                id: parseInt(s.story_id),
                title: s.title,
                img: s.cover ? s.cover : 'Pic/cover-placeholder.png',
                link: `Detstory.php?id=${s.story_id}`,
                progress: parseFloat(s.progress_percent || 0),
                favorite: false, // Default since favorite isn't a direct DB table but we can toggle visually if liked
                lastRead: s.last_read_at ? new Date(s.last_read_at.replace(/-/g, '/')).getTime() : 0,
                is_complete: parseInt(s.is_complete || 0)
            }));
            
            continueReadingStories = (data.continue_reading || []).map(s => ({
                id: parseInt(s.story_id),
                title: s.title,
                img: s.cover ? s.cover : 'Pic/cover-placeholder.png',
                link: `Detstory.php?id=${s.story_id}`,
                progress: parseFloat(s.progress_percent || 0),
                favorite: false,
                lastRead: s.last_read_at ? new Date(s.last_read_at.replace(/-/g, '/')).getTime() : 0,
                is_complete: parseInt(s.is_complete || 0)
            }));

            render(stories, continueReadingStories);
        } else {
            console.error("Gagal memuat library:", data.message);
        }

    } catch (error) {
        console.error("Gagal mengambil library:", error);
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
            alert("Cerita berhasil ditambahkan ke library!");
            loadLibrary();
        } else {
            alert(result.message || "Gagal menambahkan cerita");
        }

    } catch (error) {
        console.error("Error add library:", error);
    }
}

// REMOVE FROM LIBRARY
async function removeFromLibrary(storyId) {
    try {
        const response = await fetch("PHP/add_to_library.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                story_id: storyId,
                action: 'remove'
            })
        });

        const result = await response.json();

        if (result.success) {
            alert("Cerita berhasil dihapus dari library!");
            loadLibrary();
        } else {
            alert(result.message || "Gagal menghapus cerita");
        }

    } catch (error) {
        console.error("Error remove library:", error);
    }
}

// CARD
function createCard(s, showProgress = false) {
    return `
    <div class="card">
        <div>
            ${s.favorite ? '<div class="love">❤</div>' : ''}
            <img src="${s.img}" onclick="goTo('${s.link}')" alt="${s.title}" onerror="this.src='Pic/cover-placeholder.png'">

            <div class="card-content" onclick="goTo('${s.link}')">
                <h3>${s.title}</h3>

                ${showProgress ? `
                <p>Membaca - ${s.progress}%</p>
                <div class="progress">
                    <div class="progress-bar" style="width:${s.progress}%"></div>
                </div>
                ` : ''}
            </div>
        </div>

        <button class="library-btn" style="background:#e74c3c; color:white; border:none; border-top:1px solid rgba(255,255,255,0.1); width:100%; padding:8px 0; font-weight:600; cursor:pointer;" onclick="removeFromLibrary(${s.id})">
            ✕ Hapus
        </button>
    </div>`;
}

// RENDER
function render(allData, continueData) {
    const allStory = document.getElementById("all-story");
    const continueReading = document.getElementById("continue-reading");

    if (allStory) {
        allStory.innerHTML = allData.length > 0 
            ? allData.map(s => createCard(s, false)).join("") 
            : `<div style="grid-column: 1/-1; text-align: center; color: #888; padding: 20px;">Belum ada cerita yang disimpan.</div>`;
    }

    if (continueReading) {
        continueReading.innerHTML = continueData.length > 0 
            ? continueData.map(s => createCard(s, true)).join("") 
            : `<div style="grid-column: 1/-1; text-align: center; color: #888; padding: 20px;">Belum ada bacaan aktif saat ini.</div>`;
    }
}

// INIT
loadLibrary();

// NAVIGASI
function goTo(link) {
    window.location.href = link;
}

function goBack() {
    window.history.back();
}

// FILTER
// ====================================================================
// LOGIKA FILTER TAB NAVIGASI (Dinamis Memasang/Menghapus HTML Lanjutkan Membaca)
// ====================================================================
const filterBtns = document.querySelectorAll(".filter-btn");

filterBtns.forEach(btn => {
    btn.addEventListener("click", function (e) {
        e.preventDefault();

        const active = document.querySelector(".filter-btn.active");
        if (active) active.classList.remove("active");

        btn.classList.add("active");

        let type = btn.dataset.filter;
        let filteredStories = [...stories];
        let filteredContinue = [...continueReadingStories];

        // Ambil elemen pembungkus utama di halaman library kamu
        const sectionTitle = document.querySelector(".main h2:nth-of-type(1)"); // Judul utama yang terlihat
        const continueContainer = document.getElementById("continue-reading");  // Tempat kartu lanjutkan membaca

        if (type === "latest") {
            // 1. Ubah judul menjadi informasi terakhir dibaca
            if (sectionTitle) sectionTitle.innerText = "Semua Cerita (Terakhir Dibaca)";

            // 2. ISI DAN MUNCULKAN konten Lanjutkan Membaca hanya di tab Latest
            if (continueContainer) {
                continueContainer.style.display = "grid";
                continueContainer.innerHTML = filteredContinue.length > 0
                    ? filteredContinue.map(s => createCard(s, true)).join("")
                    : `<div style="grid-column: 1/-1; text-align: center; color: #888; padding: 20px;">Belum ada bacaan aktif saat ini.</div>`;
            }

            // 3. Urutkan daftar konten berdasarkan waktu baca terbaru
            filteredStories = filteredStories.sort((a, b) => b.lastRead - a.lastRead);
        }
        else if (type === "favorite") {
            // 1. Ubah judul utama
            if (sectionTitle) sectionTitle.innerText = "Cerita Favorit";

            // 2. HAPUS KONTEN Lanjutkan Membaca dari layar (Kosongkan)
            if (continueContainer) {
                continueContainer.innerHTML = "";
                continueContainer.style.display = "none";
            }

            // 3. Filter cerita favorit
            filteredStories = stories.filter(s => s.favorite === true);
        }
        else {
            // DEFAULT: TAB "SEMUA"
            // 1. Kembalikan judul utama menjadi Semua Cerita
            if (sectionTitle) sectionTitle.innerText = "Semua Cerita";

            // 2. HAPUS KONTEN Lanjutkan Membaca dari layar (Kosongkan)
            if (continueContainer) {
                continueContainer.innerHTML = "";
                continueContainer.style.display = "none";
            }

            // 3. Tampilkan semua cerita tanpa filter sorting
            filteredStories = [...stories];
        }

        // Jalankan render bawaan kelompokmu khusus untuk bagian daftar cerita bawah saja
        const allStory = document.getElementById("all-story");
        if (allStory) {
            allStory.innerHTML = filteredStories.length > 0
                ? filteredStories.map(s => createCard(s, false)).join("")
                : `<div style="grid-column: 1/-1; text-align: center; color: #888; padding: 20px;">Belum ada cerita dalam daftar ini.</div>`;
        }
    });
});

// Pastikan saat pertama kali halaman dimuat (Default tab 'Semua'), wadah atas langsung kosong total
document.addEventListener("DOMContentLoaded", function () {
    const continueContainer = document.getElementById("continue-reading");
    if (continueContainer) {
        continueContainer.innerHTML = "";
        continueContainer.style.display = "none";
    }
});

// SIDEBAR
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const toggle = document.getElementById("toggleSidebar");

    if (toggle) {
        toggle.addEventListener("click", function () {
            sidebar.classList.toggle("active");
            overlay.classList.toggle("active");
        });
    }

    if (overlay) {
        overlay.addEventListener("click", function () {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });
    }
});