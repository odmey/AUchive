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
const headerUsername = document.querySelector(".profile-header .center h3");
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
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
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

if (editProfileImageInput) {
    editProfileImageInput.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (file) openImageEditor(file, "profile");
        this.value = "";
    });
}

if (editBannerImageInput) {
    editBannerImageInput.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (file) openImageEditor(file, "banner");
        this.value = "";
    });
}

if (cancelCropBtn) {
    cancelCropBtn.addEventListener("click", closeImageEditor);
}

if (applyCropBtn) {
    applyCropBtn.addEventListener("click", function () {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: currentTarget === "profile" ? 800 : 1600,
            height: currentTarget === "profile" ? 800 : 900,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: "high"
        });

        const croppedImage = canvas.toDataURL("image/jpeg", 0.95);

        if (currentTarget === "profile") {
            tempProfileImage = croppedImage;
            if (profilePreview) profilePreview.src = croppedImage;
        } else {
            tempBannerImage = croppedImage;
            if (bannerPreview) bannerPreview.src = croppedImage;
        }

        closeImageEditor();
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
    if (value === "hapus") {
        selectedStory = storyId;
        openPopup();
    }

    if (selectEl) {
        selectEl.selectedIndex = 0;
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
}

function yesAction() {
    if (selectedStory) {
        const el = document.getElementById(selectedStory);

        if (el) {
            el.style.transition = "0.3s";
            el.style.opacity = "0";
            el.style.transform = "scale(0.9)";

            setTimeout(() => {
                el.remove();
            }, 300);
        }
    }

    closePopup();
}

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

if (saveProfileBtn) {
    saveProfileBtn.addEventListener("click", () => {
        const newName = editNameInput ? editNameInput.value.trim() : "";
        const newUsername = editUsernameInput ? editUsernameInput.value.trim() : "";
        const newBio = editBioInput ? editBioInput.value.trim() : "";

        if (newName && profileName) {
            profileName.textContent = newName;
            currentProfileData.name = newName;
        }

        if (newUsername && profileUsername) {
            const formattedUsername = newUsername.startsWith("@") ? newUsername : "@" + newUsername;
            profileUsername.textContent = formattedUsername;
            currentProfileData.username = newUsername.replace("@", "");

            if (headerUsername) {
                headerUsername.textContent = newUsername.replace("@", "");
            }
        }

        if (profileBio) {
            profileBio.textContent = newBio || "Your bio goes here...";
            currentProfileData.bio = newBio || "Your bio goes here...";
        }

        if (profilePicImg && tempProfileImage) {
            profilePicImg.src = tempProfileImage;
        }

        if (coverImg && tempBannerImage) {
            coverImg.src = tempBannerImage;
        }

        closeModal(editMenu);
    });
}

// ==========================
// OVERLAY CLICK (CLOSE ALL)
// ==========================
if (overlay) {
    overlay.addEventListener("click", () => {
        document.querySelectorAll(".edit-menu.active").forEach(modal => {
            modal.classList.remove("active");
        });

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
    openBtn.addEventListener("click", function () {
        storyPrepModal.style.display = "flex";
    });

    closeBtn.addEventListener("click", function () {
        storyPrepModal.style.display = "none";
    });

    storyPrepModal.addEventListener("click", function (e) {
        if (e.target === storyPrepModal) {
            storyPrepModal.style.display = "none";
        }
    });
}

function resizeImage(file, callback) {
    const img = new Image();
    const reader = new FileReader();

    reader.onload = function (e) {
        img.src = e.target.result;
    };

    img.onload = function () {
        const canvas = document.createElement("canvas");

        const MAX_WIDTH = 1000; // biar ringan
        const scale = MAX_WIDTH / img.width;

        canvas.width = MAX_WIDTH;
        canvas.height = img.height * scale;

        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        callback(canvas.toDataURL("image/jpeg", 0.9));
    };

    reader.readAsDataURL(file);

}
document.getElementById("nextBtn").addEventListener("click", function () {
    const storyData = {
        title: document.getElementById("judul").value,
        description: document.getElementById("deskripsi").value,
        genre: document.getElementById("genre").value,
        tags: document.getElementById("tagar").value
    };

    localStorage.setItem("storyData", JSON.stringify(storyData));

    window.location.href = "Editor.html";
});