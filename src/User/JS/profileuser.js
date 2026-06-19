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
let pendingEditCoverFile = null;  // Cropped cover blob for edit story
let pendingCreateCoverFile = null; // Cropped cover blob for create story
let tempEditCoverImage = '';       // Preview URL for current edit cover

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
                    aspectRatio: targetType === "profile" ? 1 : ((targetType === "cover" || targetType === "create_cover") ? 2 / 3 : 16 / 9),
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

        // Dimensions based on target type
        const dims = {
            profile:      { width: 800,  height: 800  },
            banner:       { width: 1600, height: 900  },
            cover:        { width: 520,  height: 780  },  // 2:3 portrait book cover
            create_cover: { width: 520,  height: 780  }
        };
        const { width, height } = dims[currentTarget] || dims.banner;

        const canvas = cropper.getCroppedCanvas({
            width, height,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: "high"
        });

        const previewUrl = canvas.toDataURL("image/jpeg", 0.92);

        canvas.toBlob(function (blob) {
            const fileNames = { profile: "profile.jpg", banner: "banner.jpg", cover: "cover.jpg", create_cover: "cover.jpg" };
            const croppedFile = new File([blob], fileNames[currentTarget] || "image.jpg", { type: "image/jpeg" });

            if (currentTarget === "profile") {
                tempProfileImage = previewUrl;
                pendingProfileFile = croppedFile;
                if (profilePreview) profilePreview.src = previewUrl;
            } else if (currentTarget === "cover") {
                // Update story cover preview in the edit modal
                tempEditCoverImage = previewUrl;
                pendingEditCoverFile = croppedFile;
                const prev = document.getElementById("editPreviewCover");
                const box  = prev ? prev.closest(".cover-box") : null;
                const lbl  = box  ? box.querySelector(".upload-btn-label") : null;
                if (prev) { prev.src = previewUrl; prev.style.display = "block"; }
                if (box)  box.classList.add("has-image");
                if (lbl)  lbl.textContent = "Change Cover";
            } else if (currentTarget === "create_cover") {
                // Update story cover preview in the create modal
                pendingCreateCoverFile = croppedFile;
                const prev = document.getElementById("previewCover");
                const box  = prev ? prev.closest(".cover-box") : null;
                const lbl  = box  ? box.querySelector(".upload-btn-label") : null;
                if (prev) { prev.src = previewUrl; prev.style.display = "block"; }
                if (box)  box.classList.add("has-image");
                if (lbl)  lbl.textContent = "Change Cover";
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
// DROPDOWN ACTION
// ==========================

// ── Progress Status (Ongoing / Complete / Hiatus) ─────────────
function handleProgress(value, storyId, selectEl) {
    if (!value) return;
    const id = storyId.replace('story-', '');
    updateProgressStatus(id, value, storyId, selectEl);
}

async function updateProgressStatus(storyId, progressStatus, elemId, selectEl) {
    try {
        const res = await fetch('src/Chapter/PHP/update_progress_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ story_id: storyId, progress_status: progressStatus })
        });
        const data = await res.json();
        if (data.success) {
            const badge = document.querySelector(`#${elemId} .progress-badge`);
            if (badge) {
                const labels = { ongoing: 'Ongoing', complete: 'Complete', hiatus: 'Hiatus' };
                badge.textContent = labels[progressStatus] || progressStatus;
                badge.className = `status-badge progress-badge ${progressStatus}`;
            }
            const card = document.getElementById(elemId);
            if (card) {
                card.setAttribute('data-progress-status', progressStatus);
            }
            const toasts = { ongoing: '\u2713 Ongoing', complete: '\u2713 Complete', hiatus: '\u2713 Hiatus' };
            showToastProfile(toasts[progressStatus] || 'Progress diperbarui.');
        } else {
            alert('Failed to update progress: ' + (data.message || ''));
            // Revert
            const card = document.getElementById(elemId);
            if (card && selectEl) {
                selectEl.value = card.getAttribute('data-progress-status') || 'ongoing';
            }
        }
    } catch (err) {
        alert('Connection failed.');
        // Revert
        const card = document.getElementById(elemId);
        if (card && selectEl) {
            selectEl.value = card.getAttribute('data-progress-status') || 'ongoing';
        }
    }
}

// ── Publish / Draft / Delete ──────────────────────────────────
function handleAction(value, storyId, selectEl) {
    const id = storyId.replace('story-', '');

    if (value === 'hapus') {
        selectedStory = { elemId: storyId, id: id, selectEl: selectEl };
        openPopup();
    } else if (value === 'publish') {
        updateStoryStatus(id, 'published', storyId, selectEl);
    } else if (value === 'draft') {
        updateStoryStatus(id, 'draft', storyId, selectEl);
    }
}

async function updateStoryStatus(storyId, status, elemId, selectEl) {
    try {
        const res = await fetch('src/Story/PHP/update_story_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ story_id: storyId, status: status })
        });
        const data = await res.json();
        if (data.success) {
            const badge = document.querySelector(`#${elemId} .publish-badge`);
            if (badge) {
                badge.textContent = status === 'published' ? 'Published' : 'Draft';
                badge.className = `status-badge publish-badge ${status}`;
            }
            showToastProfile(status === 'published' ? '\u2713 Cerita dipublikasikan!' : 'Cerita dijadikan draft.');
        } else {
            alert('Failed to update status: ' + data.message);
            // Revert
            const badge = document.querySelector(`#${elemId} .publish-badge`);
            if (badge && selectEl) {
                const isPublished = badge.classList.contains('published');
                selectEl.value = isPublished ? 'publish' : 'draft';
            }
        }
    } catch (err) {
        alert('Connection failed.');
        // Revert
        const badge = document.querySelector(`#${elemId} .publish-badge`);
        if (badge && selectEl) {
            const isPublished = badge.classList.contains('published');
            selectEl.value = isPublished ? 'publish' : 'draft';
        }
    }
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

    // Revert the delete selection back to the current status in the card!
    if (selectedStory && selectedStory.selectEl && selectedStory.elemId) {
        const badge = document.querySelector(`#${selectedStory.elemId} .publish-badge`);
        if (badge) {
            const isPublished = badge.classList.contains('published');
            selectedStory.selectEl.value = isPublished ? 'publish' : 'draft';
        }
    }
}

async function yesAction() {
    if (selectedStory) {
        try {
            const res = await fetch('src/Story/PHP/delete_story.php', {
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
                alert('Failed to delete: ' + data.message);
            }
        } catch (err) {
            alert('Connection failed.');
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
            alert("Name and username are required.");
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
            const res = await fetch("src/User/PHP/update_profile.php", { method: "POST", body: fd });
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
            alert("Failed to save. Please try again.");
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
    closeBtn.addEventListener("click", () => {
        storyPrepModal.style.display = "none";
        const form = storyPrepModal.querySelector("form");
        if (form) {
            form.reset();
            const prev = document.getElementById("previewCover");
            if (prev) { prev.src = ""; prev.style.display = "none"; }
            const box = storyPrepModal.querySelector(".cover-box");
            if (box) box.classList.remove("has-image");
            const label = storyPrepModal.querySelector(".upload-btn-label");
            if (label) label.textContent = "Upload Cover";
        }
    });
    storyPrepModal.addEventListener("click", function (e) {
        if (e.target === storyPrepModal) {
            storyPrepModal.style.display = "none";
            const form = storyPrepModal.querySelector("form");
            if (form) {
                form.reset();
                const prev = document.getElementById("previewCover");
                if (prev) { prev.src = ""; prev.style.display = "none"; }
                const box = storyPrepModal.querySelector(".cover-box");
                if (box) box.classList.remove("has-image");
                const label = storyPrepModal.querySelector(".upload-btn-label");
                if (label) label.textContent = "Upload Cover";
            }
        }
    });
}

// ==========================
// COVER PREVIEW (Story Prep)
// ==========================
const coverInput = document.getElementById("cover");
if (coverInput) {
    coverInput.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (!file) return;

        pendingCreateCoverFile = file;
        openImageEditor(file, "create_cover");

        setTimeout(() => { this.value = ""; }, 300);
    });
}

// ==========================
// EDIT STORY PREP MODAL
// ==========================
const editStoryPrepModal = document.getElementById("editstoryprep");
const closeEditBtn = document.getElementById("closeEditStoryPrep");

if (editStoryPrepModal && closeEditBtn) {
    closeEditBtn.addEventListener("click", () => {
        editStoryPrepModal.style.display = "none";
        const form = editStoryPrepModal.querySelector("form");
        if (form) form.reset();
    });
    editStoryPrepModal.addEventListener("click", function (e) {
        if (e.target === editStoryPrepModal) {
            editStoryPrepModal.style.display = "none";
            const form = editStoryPrepModal.querySelector("form");
            if (form) form.reset();
        }
    });
}

function openEditStoryPrep(storyId) {
    const card = document.getElementById(`story-${storyId}`);
    if (!card) return;

    const title       = card.getAttribute('data-title')       || '';
    const description = card.getAttribute('data-description') || '';
    const genre       = card.getAttribute('data-genre')       || '';
    const tags        = card.getAttribute('data-tags')        || '';
    const cover       = card.getAttribute('data-cover')       || '';

    // Populate fields
    document.getElementById('editStoryId').value      = storyId;
    document.getElementById('editStoryTitle').value   = title;
    document.getElementById('editStoryDesc').value    = description;
    document.getElementById('editStoryGenre').value   = genre;
    document.getElementById('editStoryTags').value    = tags;

    // Populate progress status dropdown
    const progressStatus = card.getAttribute('data-progress-status') || 'ongoing';
    const editProgressEl = document.getElementById('editStoryProgress');
    if (editProgressEl) editProgressEl.value = progressStatus;

    // Reset pending cover state each time the modal opens
    pendingEditCoverFile = null;
    tempEditCoverImage   = cover;

    const prev  = document.getElementById('editPreviewCover');
    const box   = prev ? prev.closest(".cover-box") : null;
    const label = box  ? box.querySelector(".upload-btn-label") : null;

    if (prev) {
        if (cover) {
            prev.src = cover;
            prev.style.display = 'block';
            if (box)   box.classList.add("has-image");
            if (label) label.textContent = "Change Cover";
        } else {
            prev.src = '';
            prev.style.display = 'none';
            if (box)   box.classList.remove("has-image");
            if (label) label.textContent = "Upload Cover";
        }
    }

    if (editStoryPrepModal) editStoryPrepModal.style.display = 'flex';
}

// ==========================
// EDIT COVER PREVIEW
// ==========================
// ── Edit Story Cover → open Cropper editor ───────────────────
const editCoverInput = document.getElementById("editCover");
if (editCoverInput) {
    editCoverInput.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (!file) return;

        // Capture file reference BEFORE resetting input value
        pendingEditCoverFile = file;

        // Open the shared image editor (same one used for profile/banner)
        openImageEditor(file, "cover");

        // Reset after a short delay so FileReader can read the file first
        setTimeout(() => { this.value = ""; }, 300);
    });
}

// ── Intercept Edit Story form submit to inject cropped cover ──
const editStoryForm = document.querySelector("#editstoryprep form");
if (editStoryForm) {
    editStoryForm.addEventListener("submit", async function (e) {
        // Only intercept when there is a cropped cover to inject
        if (!pendingEditCoverFile) return; // let normal POST handle it

        e.preventDefault();

        const submitBtn = editStoryForm.querySelector("button[type='submit']");
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = "Saving..."; }

        const fd = new FormData(editStoryForm);
        // Replace whatever file was in the input with our cropped blob
        fd.delete("cover");
        fd.append("cover", pendingEditCoverFile, "cover.jpg");

        try {
            const res  = await fetch("src/Story/PHP/edit_story_prep.php", { method: "POST", body: fd });
            // edit_story_prep.php redirects → follow redirect URL
            const finalUrl = res.url;
            pendingEditCoverFile = null;
            tempEditCoverImage   = '';
            window.location.href = finalUrl;
        } catch (err) {
            alert("Failed to save cover. Please try again.");
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = "Save Changes"; }
        }
    });
}

// ── Intercept Create Story form submit to inject cropped cover ──
const createStoryForm = document.querySelector("#storyprep form");
if (createStoryForm) {
    createStoryForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const submitBtn = createStoryForm.querySelector("button[type='submit']");
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = "Saving..."; }

        const fd = new FormData(createStoryForm);
        if (pendingCreateCoverFile) {
            fd.delete("cover");
            fd.append("cover", pendingCreateCoverFile, "cover.jpg");
        }

        try {
            const res  = await fetch("src/Story/PHP/story_prep.php", { method: "POST", body: fd });
            const data = await res.json();
            pendingCreateCoverFile = null;
            if (data.success) {
                window.location.href = data.redirect_url;
            } else if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                alert('Gagal membuat cerita: ' + (data.message || 'Unknown error'));
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = "Next"; }
            }
        } catch (err) {
            alert("Failed to save story. Please try again.");
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = "Next"; }
        }
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
            showToastProfile("✓ Story Detail Updated Successfully");
        }, 200);
    }
})();

// ==========================
// FOLLOWERS / FOLLOWING MODAL
// ==========================
const followersBtn = document.getElementById("followersBtn");
const followingBtn = document.getElementById("followingBtn");
const followModal = document.getElementById("followModal");
const closeFollowModal = document.getElementById("closeFollowModal");
const modalTitle = document.getElementById("modalTitle");
const userList = document.getElementById("userList");

function openFollowModal(title, users) {
    if (!followModal || !modalTitle || !userList) return;
    modalTitle.textContent = title;
    userList.innerHTML = "";

    if (!users || users.length === 0) {
        userList.innerHTML = `<div style="text-align:center; color:#888; padding:30px; font-size:13px;">Belum ada data.</div>`;
    } else {
        users.forEach(user => {
            const redirectUrl = `profile_person.php?id=${user.user_id}`;
            userList.innerHTML += `
                <div class="user-item" onclick="window.location.href='${redirectUrl}'" style="display:flex; align-items:center; gap:14px; padding:10px 20px; cursor:pointer; transition:background 0.2s;">
                    <img src="${user.image}" onerror="this.src='Pic/profileicon.jpg'" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                    <div class="user-info" style="display:flex; flex-direction:column; gap:2px;">
                        <h4 style="margin:0; font-size:14px; color:#fff; font-weight:600;">${user.name}</h4>
                        <p style="margin:0; font-size:12px; color:#aaa;">${user.username}</p>
                    </div>
                </div>
            `;
        });
    }

    followModal.style.display = "flex";
}

if (followersBtn) {
    followersBtn.addEventListener("click", () => {
        openFollowModal("Followers", followersData);
    });
}

if (followingBtn) {
    followingBtn.addEventListener("click", () => {
        openFollowModal("Following", followingData);
    });
}

if (closeFollowModal) {
    closeFollowModal.addEventListener("click", () => {
        followModal.style.display = "none";
    });
}

if (followModal) {
    followModal.addEventListener("click", (e) => {
        if (e.target === followModal) {
            followModal.style.display = "none";
        }
    });
}
