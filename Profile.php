<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style_profile.css">
    <script src="JS/profileuser.js" defer></script>
    <!-- <link href="https://unpkg.com/cropperjs/dist/cropper.min.css" rel="stylesheet"/>
    <script src="https://unpkg.com/cropperjs/dist/cropper.min.js"></script> -->
    <title>Profile</title>
</head>

<body>

<header class="profile-header">
    <div class="left">
        <a href="homepage.html" class="back-link">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
    </div>
    <div class="center">
        <h3>odmey_</h3>
        <p>465 posts</p>
    </div>
</header>

<div class="profile-container">
    <div class="cover">
        <img src="Pic/profilebanner.jpg" class="cover-img" onerror="this.style.display='none'">
    </div>

    <div class="profile-info">
        <img src="Pic/profileicon.jpg" class="profile-pic" onerror="this.style.opacity='0'">

        <div class="top-row">
            <div>
                <h2 class="name">odmey_</h2>
                <p class="username">@dambee395444</p>
            </div>

            <button data-modal-target="#editMenu" class="edit-btn" type="button">Edit Profile</button>

            <div class="edit-menu" id="editMenu">
                <div class="edit-menu-header">
                    <div class="title">Edit Profile</div>
                    <button data-close-button class="close-button" type="button">&times;</button>
                </div>

                <div class="edit-menu-body">
                    <div class="edit-section">
                        <label>Profile Picture</label>
                        <div class="image-row">
                            <img id="profilePreview" class="preview-img preview-circle" src="Pic/profileicon.jpg" alt="Profile preview">
                            <input type="file" id="editProfileImage" accept="image/*">
                        </div>
                    </div>

                    <div class="edit-section">
                        <label>Banner</label>
                        <div class="image-row banner-row">
                            <img id="bannerPreview" class="preview-img preview-banner" src="Pic/profilebanner.jpg" alt="Banner preview">
                            <input type="file" id="editBannerImage" accept="image/*">
                        </div>
                    </div>

                    <div class="edit-section">
                        <label for="editName">Name</label>
                        <input type="text" id="editName" placeholder="Your name">

                        <label for="editUsername">Username</label>
                        <input type="text" id="editUsername" placeholder="Your username">

                        <label for="editBio">Bio</label>
                        <textarea id="editBio" placeholder="Write your bio..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="cancel-btn" data-close-button>Cancel</button>
                        <button type="button" class="save-btn" id="saveProfileBtn">Save</button>
                    </div>
                </div>
            </div>

            <div id="overlay"></div>
        </div>

        <p class="bio">Your bio goes here...</p>
        <p class="join">Joined October 2023</p>

        <div class="stats">
            <span><b>13</b> Following</span>
            <span><b>3</b> Followers</span>
        </div>
    </div>
</div>

<div class="story-section">
    <div class="story-card" id="story1">
        <div class="story-cover">
            <img src="Pic/karya1.jpg" onerror="this.style.display='none'">
        </div>

        <div class="story-content">
            <div class="story-title">Judul Cerita Kamu</div>
            <div class="story-desc">
                Tak kala gelap yang dirasakannya berubah menjadi sebuah rona cemerlang...
            </div>
            <div class="story-tags">
                <span class="story-tag">Romance</span>
                <span class="story-tag">Fantasy</span>
                <span class="story-tag">Drama</span>
            </div>
        </div>

        <div class="story-status">
            <select onchange="handleAction(this.value, 'story1', this)">
                <option>Publikasikan</option>
                <option>Draft</option>
                <option value="hapus">Hapus</option>
            </select>
        </div>
    </div>
</div>

<div class="add-story">
    <button id="openstoryprep" type="button">+</button>
</div>

<!-- POPUP -->
<div id="confirmBox" class="popup">
    <div class="popup-content">
        <p>Yakin mau hapus cerita ini?</p>
        <button onclick="yesAction()" type="button">Yes</button>
        <button onclick="closePopup()" type="button">No</button>
    </div>
</div>

<div class="story-prep" id="storyprep">
    <div class="storyprepcnt">
        <span class="close-story-prep" id="closeStoryPrep">&times;</span>

        <section class="page-title">
            <h1>Create Your Story</h1>
            <p>Start your writing journey and share your universe with readers.</p>
        </section>
  <form action="PHP/story_prep.php" method="POST" enctype="multipart/form-data">    
    <div class="container-upload">
            <div class="cover-box">
                <span class="material-symbols-outlined cover-icon">image</span>
                <label for="cover" class="upload-btn-label">Upload Cover</label>
                <input type="file" id="cover" class="real-file" name="cover" accept="image/*">
                <img id="previewCover" class="preview-cover" alt="Preview cover">
            </div>

            <div class="form-box">
                <h2>Story Information</h2>

                <label for="judul">Story Title</label>
                <input type="text" id="judul" name="title" placeholder="Enter your story title">

                <label for="deskripsi">Description</label>
                <textarea id="deskripsi" name="description" placeholder="Tell readers about your story..."></textarea>

                <label for="genre">Genre</label>
                <select id="genre" name="genre">
                    <option value="">Choose Genre</option>
                    <option>Romance</option>
                    <option>Action</option>
                    <option>Fantasy</option>
                    <option>Drama</option>
                    <option>Comedy</option>
                    <option>Mystery</option>
                    <option>Fanfiction</option>
                </select>

                <label for="tagar">Tags</label>
                <input type="text" id="tagar" name="tags" placeholder="space to divide the tags...">

                <button type="submit" class="next-btn" id="nextBtn">Next</a>
            </div>
        </div>
    </form>  
    </div>
</div>
<!-- IMAGE EDITOR MODAL -->
<div id="imageEditorModal" class="image-editor">
    <div class="image-editor-content">
        <div class="image-editor-topbar">
            <button type="button" id="cancelCrop" class="icon-btn">←</button>
            <h3>Edit media</h3>
            <button type="button" id="applyCrop" class="apply-top-btn">Apply</button>
        </div>

        <p class="editor-hint">Drag untuk geser. Scroll atau pinch untuk zoom.</p>

        <div class="crop-container">
            <img id="cropperImage" alt="Image editor preview">
        </div>

        <div class="zoom-row">
            <span>−</span>
            <input type="range" id="cropZoom" min="0.5" max="2" step="0.01" value="1">
            <span>+</span>
        </div>
    </div>
</div>


</html>