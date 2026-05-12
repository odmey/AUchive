// DATA
let stories = [];

// LOAD LIBRARY FROM PHP API
async function loadLibrary() {
    try {
        const response = await fetch("get_library.php");
        const data = await response.json();

        stories = data;
        render(stories);

    } catch (error) {
        console.error("Gagal mengambil library:", error);
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
            alert("Cerita berhasil ditambahkan ke library!");
            loadLibrary();
        } else {
            alert(result.message || "Gagal menambahkan cerita");
        }

    } catch (error) {
        console.error("Error add library:", error);
    }
}


// CARD
function createCard(s, showProgress = false) {
    return `
    <div class="card">
        <div onclick="goTo('${s.link}')">
            ${s.favorite ? '<div class="love">❤</div>' : ''}
            <img src="${s.img}">

            <div class="card-content">
                <h3>${s.title}</h3>

                ${showProgress ? `
                <p>Bab - ${s.progress}%</p>
                <div class="progress">
                    <div class="progress-bar" style="width:${s.progress}%"></div>
                </div>
                ` : ''}
            </div>
        </div>

        <button class="library-btn" onclick="addToLibrary(${s.id})">
            + Library
        </button>
    </div>`;
}


// RENDER
function render(data) {
    const allStory = document.getElementById("all-story");
    const continueReading = document.getElementById("continue-reading");

    if (allStory) {
        allStory.innerHTML = data.map(s => createCard(s, false)).join("");
    }

    if (continueReading) {
        const limited = data.slice(0, 5);
        continueReading.innerHTML = limited.map(s => createCard(s, true)).join("");
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
document.querySelectorAll(".filter-btn").forEach(btn => {
    btn.addEventListener("click", e => {
        e.preventDefault();

        const active = document.querySelector(".filter-btn.active");
        if (active) active.classList.remove("active");

        btn.classList.add("active");

        let type = btn.dataset.filter;
        let filtered = [...stories];

        if (type === "favorite") {
            filtered = filtered.filter(s => s.favorite);
        }

        if (type === "latest") {
            filtered = filtered.sort((a, b) => b.lastRead - a.lastRead);
        }

        render(filtered);
    });
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