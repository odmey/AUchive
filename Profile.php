<?php
session_start();
require_once 'PHP/database.php';

// Redirect kalau belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php?auth=login');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT username, name, bio, profile_pic, profile_ban, created_at FROM users WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: homepage.php?auth=login');
    exit;
}

// Ambil stories milik user ini
$stmtStories = $pdo->prepare('
    SELECT s.story_id, s.title, s.description, s.cover, s.status,
           g.genre_name
    FROM stories s
    LEFT JOIN genres g ON s.genre_id = g.genre_id
    WHERE s.user_id = ?
    ORDER BY s.published_at DESC
');
$stmtStories->execute([$_SESSION['user_id']]);
$stories = $stmtStories->fetchAll();

$profilePic = $user['profile_pic'] ?: 'Pic/profileicon.jpg';
$profileBan = $user['profile_ban'] ?: 'Pic/profilebanner.jpg';
$bio = $user['bio'] ?: 'Your bio goes here...';
$joinDate = date('F Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style_profile.css">

    <!-- Cropper.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Harus SETELAH cropper.min.js -->
    <script src="JS/profileuser.js" defer></script>
    <title>Profile – <?= htmlspecialchars($user['username']) ?></title>
</head>

<body>

    <header class="profile-header">
        <div class="left">
            <a href="homepage.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        </div>
        <div class="center">
            <h3 id="headerUsername"><?= htmlspecialchars($user['username']) ?></h3>
            <p><?= count($stories) ?> posts</p>
        </div>
    </header>

    <div class="profile-container">
        <div class="cover">
            <img src="<?= htmlspecialchars($profileBan) ?>" class="cover-img" onerror="this.style.display='none'">
        </div>

        <div class="profile-info">
            <img src="<?= htmlspecialchars($profilePic) ?>" class="profile-pic" onerror="this.style.opacity='0'">

            <div class="top-row">
                <div>
                    <h2 class="name"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="username">@<?= htmlspecialchars($user['username']) ?></p>
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
                                <img id="profilePreview" class="preview-img preview-circle"
                                    src="<?= htmlspecialchars($profilePic) ?>" alt="Profile preview">
                                <input type="file" id="editProfileImage" accept="image/*">
                            </div>
                        </div>
                        <div class="edit-section">
                            <label>Banner</label>
                            <div class="image-row banner-row">
                                <img id="bannerPreview" class="preview-img preview-banner"
                                    src="<?= htmlspecialchars($profileBan) ?>" alt="Banner preview">
                                <input type="file" id="editBannerImage" accept="image/*">
                            </div>
                        </div>
                        <div class="edit-section">
                            <label for="editName">Name</label>
                            <input type="text" id="editName" placeholder="Your name"
                                value="<?= htmlspecialchars($user['name']) ?>">

                            <label for="editUsername">Username</label>
                            <input type="text" id="editUsername" placeholder="Your username"
                                value="<?= htmlspecialchars($user['username']) ?>">

                            <label for="editBio">Bio</label>
                            <textarea id="editBio"
                                placeholder="Write your bio..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="cancel-btn" data-close-button>Cancel</button>
                            <button type="button" class="save-btn" id="saveProfileBtn">Save</button>
                        </div>
                    </div>
                </div>

                <div id="overlay"></div>
            </div>

            <p class="bio"><?= htmlspecialchars($bio) ?></p>
            <p class="join">Joined <?= $joinDate ?></p>

            <div class="stats">
                <span><b>0</b> Following</span>
                <span><b>0</b> Followers</span>
            </div>
        </div>
    </div>

    <!-- STORIES dari DB -->
    <div class="story-section">
        <?php if (empty($stories)): ?>
            <p style="text-align:center; color:#888; padding:30px;">Belum ada cerita. Yuk buat yang pertama!</p>
        <?php else: ?>
           <?php foreach ($stories as $s): ?>
                <div class="story-card" id="story-<?= $s['story_id'] ?>"
                    style="cursor:pointer;">
                    <div class="story-cover">
                        <img src="<?= htmlspecialchars($s['cover'] ?? 'Pic/karya1.jpg') ?>"
                            onerror="this.style.display='none'">
                    </div>
                    <div class="story-content">
                        <div class="story-title"><?= htmlspecialchars($s['title']) ?></div>
                        <div class="story-desc"><?= htmlspecialchars($s['description'] ?? '') ?></div>
                        <div class="story-tags">
                            <?php if ($s['genre_name']): ?>
                                <span class="story-tag"><?= htmlspecialchars($s['genre_name']) ?></span>
                            <?php endif; ?>
                            <span class="status-badge <?= $s['status'] ?>">
                                <?= $s['status'] === 'published' ? 'Published' : 'Draft' ?>
                            </span>
                        </div>
                    </div>
                    <div class="story-status" onclick="event.stopPropagation()">
                        <select onchange="handleAction(this.value, 'story-<?= $s['story_id'] ?>', this)">
                            <option value="">Aksi</option>
                            <option value="publish">Publikasikan</option>
                            <option value="draft">Jadikan Draft</option>
                            <option value="hapus">Hapus</option>
                        </select>
                    </div>
                </div>
    <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="add-story">
        <button id="openstoryprep" type="button">+</button>
    </div>

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
                        <textarea id="deskripsi" name="description"
                            placeholder="Tell readers about your story..."></textarea>
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
                        <button type="submit" class="next-btn" id="nextBtn">Next</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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

</body>

</html>