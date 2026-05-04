// DATA (AUTO LOOP + LAST READ)
const stories = [];

for (let i = 1; i <= 7; i++) {
    stories.push({
        title: "Story " + i,
        img: `https://picsum.photos/200/300?random=${i}`,
        progress: Math.floor(Math.random() * 100),
        link: "story" + i + ".html",
        favorite: i % 2 === 0,
        lastRead: i // 🔥 ini kuncinya
    });
}


// CARD
function createCard(s, showProgress = false) {
    return `
    <div class="card" onclick="goTo('${s.link}')">
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
    </div>`;
}


// RENDER
function render(data) {
    const allStory = document.getElementById("all-story");
    const continueReading = document.getElementById("continue-reading");

    // SEMUA CERITA (tanpa progress)
    if (allStory) {
        allStory.innerHTML = data.map(s => createCard(s, false)).join("");
    }

    // LANJUTKAN MEMBACA (max 5 + ada progress)
    if (continueReading) {
        const limited = data.slice(0, 5); // ubah ke 3 kalau mau
        continueReading.innerHTML = limited.map(s => createCard(s, true)).join("");
    }
}


// INIT
render(stories);


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

    toggle.addEventListener("click", function () {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
    });

    overlay.addEventListener("click", function () {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    });

});