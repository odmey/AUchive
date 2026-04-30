// ==========================
// GLOBAL
// ==========================
let selectedStory = null;
const overlay = document.getElementById('overlay');


// ==========================
// DROPDOWN ACTION (3 MENU)
// ==========================
function handleAction(value, storyId, selectEl) {

    if (value === "hapus") {
        selectedStory = storyId;
        openPopup();
    }

    // reset dropdown biar bisa dipilih lagi
    if (selectEl) {
        selectEl.selectedIndex = 0;
    }
}


// ==========================
// POPUP (CONFIRM DELETE)
// ==========================
function openPopup() {
    const popup = document.getElementById("confirmBox");
    popup.classList.add("active");
    overlay.classList.add("active");
}

function closePopup() {
    const popup = document.getElementById("confirmBox");
    popup.classList.remove("active");
    overlay.classList.remove("active");
}

function yesAction() {
    if (selectedStory) {
        const el = document.getElementById(selectedStory);

        // animasi kecil sebelum hilang
        el.style.transition = "0.3s";
        el.style.opacity = "0";
        el.style.transform = "scale(0.9)";

        setTimeout(() => {
            el.remove();
        }, 300);
    }

    closePopup();
}


// klik luar popup = close
const popup = document.getElementById("confirmBox");
if (popup) {
    popup.addEventListener("click", function (e) {
        if (e.target === this) {
            closePopup();
        }
    });
}


// ==========================
// MODAL EDIT PROFILE
// ==========================
const openModalButtons = document.querySelectorAll('[data-modal-target]');
const closeModalButtons = document.querySelectorAll('[data-close-button]');

openModalButtons.forEach(button => {
    button.addEventListener('click', () => {
        const modal = document.querySelector(button.dataset.modalTarget);
        openModal(modal);
    });
});

closeModalButtons.forEach(button => {
    button.addEventListener('click', () => {
        const modal = button.closest('.edit-menu');
        closeModal(modal);
    });
});

function openModal(modal) {
    if (!modal) return;
    modal.classList.add('active');
    overlay.classList.add('active');
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('active');
    overlay.classList.remove('active');
}


// ==========================
// OVERLAY CLICK (CLOSE ALL)
// ==========================
if (overlay) {
    overlay.addEventListener('click', () => {

        // tutup semua modal
        document.querySelectorAll('.edit-menu.active').forEach(modal => {
            modal.classList.remove('active');
        });

        // tutup popup juga
        document.getElementById("confirmBox").classList.remove("active");

        overlay.classList.remove('active');
    });
}