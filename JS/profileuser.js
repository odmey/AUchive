// ==========================
// GLOBAL
// ==========================
let selectedStory = null;
const overlay = document.getElementById("overlay");

// ==========================
// PROFILE ELEMENTS
// ==========================
const profileName = document.querySelector(".name");
const profileUsername = document.querySelector(".username");
const profileBio = document.querySelector(".bio");
const headerUsernameEl = document.getElementById("headerUsername");
const coverImg = document.querySelector(".cover-img");
const profilePicImg = document.querySelector(".profile-pic");

const editNameInput = document.getElementById("editName");
const editUsernameInput = document.getElementById("editUsername");
const editBioInput = document.getElementById("editBio");
const saveProfileBtn = document.getElementById("saveProfileBtn");

const editProfileImageInput = document.getElementById("editProfileImage");
const editBannerImageInput = document.getElementById("editBannerImage");
const profilePreview = document.getElementById("profilePreview");
const bannerPreview = document.getElementById("bannerPreview");

const defaultProfileImage = profilePicImg ? profilePicImg.src : "";
const defaultBannerImage = coverImg ? coverImg.src : "";

let currentProfileData = {
    name: profileName ? profileName.textContent : "",
    username: profileUsername ? profileUsername.textContent.replace("@", "") : "",
    bio: profileBio ? profileBio.textContent : ""
};

let tempProfileImage = defaultProfileImage;
let tempBannerImage = defaultBannerImage;

// ── File objects yang akan dikirim ke server ──────────────────
// Diisi saat user pilih file (raw) atau setelah crop (blob).
// Null = tidak ada perubahan, PHP tidak akan update kolom tsb.
let pendingProfileFile = null;
let pendingBannerFile = null;

// ==========================
// IMAGE EDITOR (CROPPER)
// ==========================
let cropper = null;
let currentTarget = null;

const imageEditorModal = document.getElementById("imageEditorModal");
const cropperImage = document.getElementById("cropperImage");
const cancelCropBtn = document.getElementById("cancelCrop");
const applyCropBtn = document.getElementById("applyCrop");
const cropZoom = document.getElementById("cropZoom");

function destroyCropper() {
    if (cropper) { cropper.destroy(); cropper = null; }
}

function closeImageEditor() {
    imageEditorModal.classList.remove("active");
    destroyCropper();
    currentTarget = null;
    cropperImage.src = "";
}

function openImageEditor(file, targetType) {
    if (!file) return;
    currentTarget = targetType;
    const reader = new FileReader();
    reader.onload = function (e) {
        imageEditorModal.classList.add("active");
        destroyCropper();
        cropperImage.onload = function () {
            setTimeout(() => {
                destroyCropper();
                cropper = new Cropper(cropperImage, {
                    aspectRatio: targetType === "profile" ? 1 : 16 / 9,
                    viewMode: 1,
                    dragMode: "move",
                    autoCropArea: 0.85,
                    responsive: true,
                    background: false,
                    modal: true,
                    guides: false,
                    center: true,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    rotatable: false,
                    zoomOnWheel: true,
                    zoomOnTouch: true,
                    toggleDragModeOnDblclick: false,
                    ready() {
                        if (cropZoom) cropZoom.value = "1";
                        this.cropper.setDragMode("move");
                    }
                });
            }, 150);
        };
        cropperImage.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// ── Pilih file profil → simpan raw + buka editor ─────────────
if (editProfileImageInput) {
    editProfileImageInput.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (file) {
            pendingProfileFile = file;   // fallback jika user skip crop
            openImageEditor(file, "profile");
        }
        this.value = "";
    });
}

// ── Pilih file banner → simpan raw + buka editor ─────────────
if (editBannerImageInput) {
    editBannerImageInput.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (file) {
            pendingBannerFile = file;    // fallback jika user skip crop
            openImageEditor(file, "banner");
        }
        this.value = "";
    });
}

if (cancelCropBtn) {
    cancelCropBtn.addEventListener("click", closeImageEditor);
}

// ── Apply crop → simpan preview (dataURL) + file (Blob) ──────
if (applyCropBtn) {
    applyCropBtn.addEventListener("click", function () {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: currentTarget === "profile" ? 800 : 1600,
            height: currentTarget === "profile" ? 800 : 900,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: "high"
        });

        // Preview pakai dataURL (cepat, tidak perlu server)
        const previewUrl = canvas.toDataURL("image/jpeg", 0.92);

        // File untuk dikirim ke PHP pakai toBlob (lebih efisien dari base64)
        canvas.toBlob(function (blob) {
            const fileName = currentTarget === "profile" ? "profile.jpg" : "banner.jpg";
            const croppedFile = new File([blob], fileName, { type: "image/jpeg" });

            if (currentTarget === "profile") {
                tempProfileImage = previewUrl;
                pendingProfileFile = croppedFile;
                if (profilePreview) profilePreview.src = previewUrl;
            } else {
                tempBannerImage = previewUrl;
                pendingBannerFile = croppedFile;
                if (bannerPreview) bannerPreview.src = previewUrl;
            }

            closeImageEditor();
        }, "image/jpeg", 0.92);
    });
}

if (cropZoom) {
    cropZoom.addEventListener("input", function () {
        if (!cropper) return;
        cropper.zoomTo(parseFloat(this.value));
    });
}

if (imageEditorModal) {
    imageEditorModal.addEventListener("click", function (e) {
        if (e.target === imageEditorModal) closeImageEditor();
    });
}

// ==========================
// HELPERS
// ==========================
function openModal(modal) {
    if (!modal) return;
    if (editNameInput) editNameInput.value = currentProfileData.name;
    if (editUsernameInput) editUsernameInput.value = currentProfileData.username;
    if (editBioInput) editBioInput.value = currentProfileData.bio;
    if (profilePreview) profilePreview.src = tempProfileImage;
    if (bannerPreview) bannerPreview.src = tempBannerImage;
    modal.classList.add("active");
    if (overlay) overlay.classList.add("active");
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove("active");
    if (overlay) overlay.classList.remove("active");
}

// ==========================
// DROPDOWN ACTION (3 MENU)
// ==========================
function handleAction(value, storyId, selectEl) {
    const id = storyId.replace('story-', '');

    if (value === 'hapus') {
        selectedStory = { elemId: storyId, id: id };
        openPopup();
    } else if (value === 'publish') {
        updateStoryStatus(id, 'published', storyId);
    } else if (value === 'draft') {
        updateStoryStatus(id, 'draft', storyId);
    }

    if (selectEl) selectEl.selectedIndex = 0;
}
async function updateStoryStatus(storyId, status, elemId) {
    try {
        const res = await fetch('PHP/update_story_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ story_id: storyId, status: status })
        });
        const data = await res.json();
        if (data.success) {
            // Update badge status di card
            const badge = document.querySelector(`#${elemId} .status-badge`);
            if (badge) {
                badge.textContent = status === 'published' ? 'Published' : 'Draft';
                badge.className = `status-badge ${status}`;
            }
            showToastProfile(status === 'published' ? 'Cerita dipublikasikan!' : 'Cerita dijadikan draft.');
        } else {
            alert('Gagal update status: ' + data.message);
        }
    } catch (err) {
        alert('Koneksi gagal.');
    }
}
async function yesAction() {
    if (selectedStory) {
        try {
            const res = await fetch('PHP/delete_story.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ story_id: selectedStory.id })
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(selectedStory.elemId);
                if (el) {
                    el.style.transition = '0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'scale(0.9)';
                    setTimeout(() => el.remove(), 300);
                }
                showToastProfile('Cerita berhasil dihapus.');
            } else {
                alert('Gagal hapus: ' + data.message);
            }
        } catch (err) {
            alert('Koneksi gagal.');
        }
    }
    closePopup();
}
function showToastProfile(msg) {
    let toast = document.getElementById('profileToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'profileToast';
        toast.style.cssText = `
            position:fixed; bottom:24px; right:24px;
            background:#1a1a1a; color:#fff;
            padding:10px 20px; border-radius:8px;
            font-size:13px; z-index:9999;
            opacity:0; transition:opacity .3s;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    setTimeout(() => { toast.style.opacity = '0'; }, 2500);
}
// ==========================
// POPUP (CONFIRM DELETE)
// ==========================
function openPopup() {
    const popup = document.getElementById("confirmBox");
    if (!popup) return;
    popup.classList.add("active");
    if (overlay) overlay.classList.add("active");
}

function closePopup() {
    const popup = document.getElementById("confirmBox");
    if (!popup) return;
    popup.classList.remove("active");
    if (overlay) overlay.classList.remove("active");
}

async function yesAction() {
    if (selectedStory) {
        try {
            const res = await fetch('PHP/delete_story.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ story_id: selectedStory.id })
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(selectedStory.elemId);
                if (el) {
                    el.style.transition = '0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'scale(0.9)';
                    setTimeout(() => el.remove(), 300);
                }
                showToastProfile('Cerita berhasil dihapus.');
            } else {
                alert('Gagal hapus: ' + data.message);
            }
        } catch (err) {
            alert('Koneksi gagal.');
        }
    }
    closePopup();
}

function showToastProfile(msg) {
    let toast = document.getElementById('profileToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'profileToast';
        toast.style.cssText = `
            position:fixed; bottom:24px; right:24px;
            background:#1a1a1a; color:#fff;
            padding:10px 20px; border-radius:8px;
            font-size:13px; z-index:9999;
            opacity:0; transition:opacity .3s;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    setTimeout(() => { toast.style.opacity = '0'; }, 2500);
}

const popup = document.getElementById("confirmBox");
if (popup) {
    popup.addEventListener("click", function (e) {
        if (e.target === this) closePopup();
    });
}

// ==========================
// MODAL EDIT PROFILE
// ==========================
const openModalButtons = document.querySelectorAll("[data-modal-target]");
const closeModalButtons = document.querySelectorAll("[data-close-button]");
const editMenu = document.querySelector(".edit-menu");

openModalButtons.forEach(button => {
    button.addEventListener("click", () => {
        const modal = document.querySelector(button.dataset.modalTarget);
        openModal(modal);
    });
});

closeModalButtons.forEach(button => {
    button.addEventListener("click", () => {
        const modal = button.closest(".edit-menu");
        closeModal(modal);
    });
});

// ==========================
// SAVE PROFILE — UTAMA
// ==========================
if (saveProfileBtn) {
    saveProfileBtn.addEventListener("click", async () => {
        const newName = editNameInput ? editNameInput.value.trim() : "";
        const newUsername = editUsernameInput ? editUsernameInput.value.trim() : "";
        const newBio = editBioInput ? editBioInput.value.trim() : "";

        if (!newName || !newUsername) {
            alert("Nama dan username wajib diisi.");
            return;
        }

        saveProfileBtn.disabled = true;
        saveProfileBtn.textContent = "Menyimpan...";

        const fd = new FormData();
        fd.append("name", newName);
        fd.append("username", newUsername);
        fd.append("bio", newBio);

        // Lampirkan file hanya jika ada perubahan
        if (pendingProfileFile) fd.append("profile_pic", pendingProfileFile);
        if (pendingBannerFile) fd.append("profile_ban", pendingBannerFile);

        try {
            const res = await fetch("PHP/update_profile.php", { method: "POST", body: fd });
            const data = await res.json();

            if (!data.success) {
                alert(data.message);
                saveProfileBtn.disabled = false;
                saveProfileBtn.textContent = "Save";
                return;
            }

            // ── Update DOM ──────────────────────────────────────
            if (profileName) {
                profileName.textContent = newName;
                currentProfileData.name = newName;
            }
            if (profileUsername) {
                profileUsername.textContent = "@" + newUsername;
                currentProfileData.username = newUsername;
            }
            if (headerUsernameEl) headerUsernameEl.textContent = newUsername;
            if (profileBio) {
                profileBio.textContent = newBio || "Your bio goes here...";
                currentProfileData.bio = newBio;
            }

            // Gunakan path dari server (bukan base64) supaya src-nya persistent
            if (data.profile_pic) {
                if (profilePicImg) profilePicImg.src = data.profile_pic;
                tempProfileImage = data.profile_pic;
            }
            if (data.profile_ban) {
                if (coverImg) coverImg.src = data.profile_ban;
                tempBannerImage = data.profile_ban;
            }

            // Reset pending files setelah berhasil
            pendingProfileFile = null;
            pendingBannerFile = null;

            closeModal(editMenu);

        } catch {
            alert("Gagal menyimpan. Coba lagi.");
        } finally {
            saveProfileBtn.disabled = false;
            saveProfileBtn.textContent = "Save";
        }
    });
}

// ==========================
// OVERLAY CLICK (CLOSE ALL)
// ==========================
if (overlay) {
    overlay.addEventListener("click", () => {
        document.querySelectorAll(".edit-menu.active").forEach(m => m.classList.remove("active"));
        const confirmBox = document.getElementById("confirmBox");
        if (confirmBox) confirmBox.classList.remove("active");
        overlay.classList.remove("active");
    });
}

// ==========================
// STORY PREP MODAL
// ==========================
const openBtn = document.getElementById("openstoryprep");
const storyPrepModal = document.getElementById("storyprep");
const closeBtn = document.getElementById("closeStoryPrep");

if (openBtn && storyPrepModal && closeBtn) {
    openBtn.addEventListener("click", () => { storyPrepModal.style.display = "flex"; });
    closeBtn.addEventListener("click", () => { storyPrepModal.style.display = "none"; });
    storyPrepModal.addEventListener("click", function (e) {
        if (e.target === storyPrepModal) storyPrepModal.style.display = "none";
    });
}

// ==========================
// COVER PREVIEW (Story Prep)
// ==========================
const coverInput = document.getElementById("cover");
if (coverInput) {
    coverInput.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const prev = document.getElementById("previewCover");
            if (prev) { prev.src = e.target.result; prev.style.display = "block"; }
        };
        reader.readAsDataURL(file);
    });
}

// ==========================
// EDIT STORY PREP MODAL
// ==========================
const editStoryPrepModal = document.getElementById("editstoryprep");
const closeEditBtn = document.getElementById("closeEditStoryPrep");

if (editStoryPrepModal && closeEditBtn) {
    closeEditBtn.addEventListener("click", () => { editStoryPrepModal.style.display = "none"; });
    editStoryPrepModal.addEventListener("click", function (e) {
        if (e.target === editStoryPrepModal) editStoryPrepModal.style.display = "none";
    });
}

function openEditStoryPrep(storyId) {
    const card = document.getElementById(`story-${storyId}`);
    if (!card) return;

    const title = card.getAttribute('data-title') || '';
    const description = card.getAttribute('data-description') || '';
    const genre = card.getAttribute('data-genre') || '';
    const tags = card.getAttribute('data-tags') || '';
    const cover = card.getAttribute('data-cover') || '';

    // Populate fields
    document.getElementById('editStoryId').value = storyId;
    document.getElementById('editStoryTitle').value = title;
    document.getElementById('editStoryDesc').value = description;
    document.getElementById('editStoryGenre').value = genre;
    document.getElementById('editStoryTags').value = tags;

    const prev = document.getElementById('editPreviewCover');
    if (prev) {
        if (cover) {
            prev.src = cover;
            prev.style.display = 'block';
        } else {
            prev.src = '';
            prev.style.display = 'none';
        }
    }

    if (editStoryPrepModal) {
        editStoryPrepModal.style.display = 'flex';
    }
}

// ==========================
// EDIT COVER PREVIEW
// ==========================
const editCoverInput = document.getElementById("editCover");
if (editCoverInput) {
    editCoverInput.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const prev = document.getElementById("editPreviewCover");
            if (prev) { prev.src = e.target.result; prev.style.display = "block"; }
        };
        reader.readAsDataURL(file);
    });
}

// ==========================
// KLIK STORY CARD → EDITOR
// ==========================
document.querySelectorAll('.story-card').forEach(card => {
    card.addEventListener('click', function (e) {
        // Jangan trigger kalau yang diklik adalah dropdown
        if (e.target.closest('.story-status')) return;

        const storyId = this.id.replace('story-', '');
        window.location.href = `Editor.php?story_id=${storyId}`;
    });
});

// ==========================
// URL PARAMETERS HANDLER
// ==========================
(function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get("success") === "story_updated") {
        const cleanUrl = window.location.pathname;
        history.replaceState(null, "", cleanUrl);
        setTimeout(() => {
            showToastProfile("✓ Detail cerita berhasil diperbarui!");
        }, 200);
    }
})();