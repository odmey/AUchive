<?php
session_start();
require_once 'src/Core/PHP/database.php';

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
    SELECT s.story_id, s.title, s.description, s.cover, s.status, s.progress_status,
           g.genre_name,
           (SELECT GROUP_CONCAT(t.tag_name SEPARATOR \' \') 
            FROM story_tags st 
            JOIN tags t ON t.tag_id = st.tag_id 
            WHERE st.story_id = s.story_id) AS tags_str
    FROM stories s
    LEFT JOIN genres g ON s.genre_id = g.genre_id
    WHERE s.user_id = ?
    ORDER BY s.published_at DESC
');
$stmtStories->execute([$_SESSION['user_id']]);
$stories = $stmtStories->fetchAll();

$profilePic = $user['profile_pic'] ?: 'Pic/profileicon.jpg';
$profileBan = $user['profile_ban'] ?: 'Pic/profilebanner.jpg';
$bio        = $user['bio'] ?: 'Your bio goes here...';
$joinDate   = date('F Y', strtotime($user['created_at']));

$stmtFollowersCount = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
$stmtFollowersCount->execute([$_SESSION['user_id']]);
$followersCount = (int)$stmtFollowersCount->fetchColumn();

$stmtFollowingCount = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
$stmtFollowingCount->execute([$_SESSION['user_id']]);
$followingCount = (int)$stmtFollowingCount->fetchColumn();

$stmtFollowersList = $pdo->prepare("
    SELECT u.user_id, u.name, u.username, u.profile_pic
    FROM followers f JOIN users u ON u.user_id = f.follower_id
    WHERE f.following_id = ?
");
$stmtFollowersList->execute([$_SESSION['user_id']]);
$followersList = $stmtFollowersList->fetchAll();

$stmtFollowingList = $pdo->prepare("
    SELECT u.user_id, u.name, u.username, u.profile_pic
    FROM followers f JOIN users u ON u.user_id = f.following_id
    WHERE f.follower_id = ?
");
$stmtFollowingList->execute([$_SESSION['user_id']]);
$followingList = $stmtFollowingList->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="src/User/CSS/style_profile.css">

    <!-- Cropper.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Harus SETELAH cropper.min.js -->
    <title>Profile – <?= htmlspecialchars($user['username']) ?></title>
    <script src="src/Core/JS/custom_alert.js"></script>
</head>

<body class="own-profile">

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

            <div class="profile-actions-row">
                <button data-modal-target="#editMenu" class="edit-btn" type="button">Edit Profile</button>
                <a href="Notification.php" class="action-icon-btn" title="Notifications">
                    <span class="material-symbols-outlined">notifications</span>
                </a>
                <a href="Setting.php" class="action-icon-btn" title="Settings">
                    <span class="material-symbols-outlined">settings</span>
                </a>
            </div>

            <div class="profile-details">
                <h2 class="name"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="username">@<?= htmlspecialchars($user['username']) ?></p>
                <p class="bio"><?= htmlspecialchars($bio) ?></p>
                <p class="join">Joined <?= $joinDate ?></p>

                <div class="stats">
                    <span class="stat-btn" id="followingBtn" style="cursor:pointer;">
                        <b id="followingCountVal"><?= $followingCount ?></b> Following
                    </span>
                    <span class="stat-btn" id="followersBtn" style="cursor:pointer;">
                        <b id="followersCountVal"><?= $followersCount ?></b> Followers
                    </span>
                </div>
            </div>

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
    </div>
    <!-- Modal Followers / Following -->
    <div id="followModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#1e1e1e; border-radius:12px; width:90%; max-width:400px; max-height:80vh; overflow:hidden; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #333;">
                <h3 id="modalTitle" style="margin:0; font-size:16px; color:#fff;">Followers</h3>
                <span id="closeFollowModal" style="cursor:pointer; font-size:22px; color:#aaa;">&times;</span>
            </div>
            <div id="userList" style="overflow-y:auto; padding:10px 0;"></div>
        </div>
    </div>

    <!-- Inject data dari PHP ke JS, HARUS sebelum script src -->
    <script>
        const followersData = <?= json_encode(array_map(fn($x) => [
            'user_id'  => $x['user_id'],
            'name'     => htmlspecialchars($x['name']),
            'username' => '@' . htmlspecialchars($x['username']),
            'image'    => !empty($x['profile_pic']) ? htmlspecialchars($x['profile_pic']) : 'Pic/profileicon.jpg'
        ], $followersList)) ?>;

        const followingData = <?= json_encode(array_map(fn($x) => [
            'user_id'  => $x['user_id'],
            'name'     => htmlspecialchars($x['name']),
            'username' => '@' . htmlspecialchars($x['username']),
            'image'    => !empty($x['profile_pic']) ? htmlspecialchars($x['profile_pic']) : 'Pic/profileicon.jpg'
        ], $followingList)) ?>;
    </script>

    <!-- Logic JS-nya di file terpisah -->
    <script src="src/User/JS/profileuser.js" defer></script>   
    <!-- STORIES dari DB -->
    <div class="story-section">
        <?php if (empty($stories)): ?>
            <p style="text-align:center; color:#888; padding:30px;">No stories yet. Let's write the first one!</p>
        <?php else: ?>
           <?php foreach ($stories as $s):
                $prog = $s['progress_status'] ?? 'ongoing';
                $progLabel = match($prog) { 'complete' => 'Complete', 'hiatus' => 'Hiatus', default => 'Ongoing' };
           ?>
                <div class="story-card" id="story-<?= $s['story_id'] ?>"
                    data-title="<?= htmlspecialchars($s['title']) ?>"
                    data-description="<?= htmlspecialchars($s['description'] ?? '') ?>"
                    data-genre="<?= htmlspecialchars($s['genre_name'] ?? '') ?>"
                    data-tags="<?= htmlspecialchars($s['tags_str'] ?? '') ?>"
                    data-cover="<?= htmlspecialchars($s['cover'] ?? '') ?>"
                    data-progress-status="<?= htmlspecialchars($prog) ?>"
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
                            <span class="status-badge progress-badge <?= $prog ?>" id="progress-badge-<?= $s['story_id'] ?>">
                                <?= $progLabel ?>
                            </span>
                            <span class="status-badge publish-badge <?= $s['status'] ?>" id="publish-badge-<?= $s['story_id'] ?>">
                                <?= $s['status'] === 'published' ? 'Published' : 'Draft' ?>
                            </span>
                        </div>
                    </div>
                    <div class="story-actions" onclick="event.stopPropagation()">
                        <button type="button" class="edit-details-btn" onclick="openEditStoryPrep(<?= $s['story_id'] ?>)" title="Edit Story Details">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <div class="story-status">
                            <select onchange="handleProgress(this.value, 'story-<?= $s['story_id'] ?>', this)">
                                <option value="ongoing" <?= $prog === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="complete" <?= $prog === 'complete' ? 'selected' : '' ?>>Complete</option>
                                <option value="hiatus" <?= $prog === 'hiatus' ? 'selected' : '' ?>>Hiatus</option>
                            </select>
                        </div>
                        <div class="story-status">
                            <select onchange="handleAction(this.value, 'story-<?= $s['story_id'] ?>', this)">
                                <option value="publish" <?= $s['status'] === 'published' ? 'selected' : '' ?>>Publish</option>
                                <option value="draft" <?= $s['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="hapus">&#9888; Delete</option>
                            </select>
                        </div>
                    </div>
                </div>
    <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="add-story">
        <button id="openstoryprep" type="button">+</button>
    </div>

    <div id="confirmBox" class="popup">
        <div class="popup-content popup-danger">
            <div class="popup-icon">
                <span class="material-symbols-outlined">delete_forever</span>
            </div>
            <h3 class="popup-title">Delete Story?</h3>
            <p class="popup-subtitle">This action <strong>cannot be undone</strong>.<br>All chapters and story data will be permanently deleted.</p>
            <div class="popup-actions-row">
                <button onclick="closePopup()" type="button" class="popup-cancel-btn">Cancel</button>
                <button onclick="yesAction()" type="button" class="popup-confirm-delete-btn">Yes, Delete!</button>
            </div>
        </div>
    </div>

    <div class="story-prep" id="storyprep">
        <div class="storyprepcnt">
            <span class="close-story-prep" id="closeStoryPrep">&times;</span>
            <section class="page-title">
                <h1>Create Your Story</h1>
                <p>Start your writing journey and share your universe with readers.</p>
            </section>
            <form action="src/Story/PHP/story_prep.php" method="POST" enctype="multipart/form-data">
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

    <!-- EDIT STORY PREP MODAL -->
    <div class="story-prep" id="editstoryprep">
        <div class="storyprepcnt">
            <span class="close-story-prep" id="closeEditStoryPrep">&times;</span>
            <section class="page-title">
                <h1>Edit Your Story</h1>
                <p>Modify your story's details and settings.</p>
            </section>
            <form action="src/Story/PHP/edit_story_prep.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="story_id" id="editStoryId">
                <div class="container-upload">
                    <div class="cover-box">
                        <span class="material-symbols-outlined cover-icon">image</span>
                        <label for="editCover" class="upload-btn-label">Upload Cover</label>
                        <input type="file" id="editCover" class="real-file" name="cover" accept="image/*">
                        <img id="editPreviewCover" class="preview-cover" alt="Preview cover">
                    </div>
                    <div class="form-box">
                        <h2>Story Information</h2>
                        <label for="editStoryTitle">Story Title</label>
                        <input type="text" id="editStoryTitle" name="title" placeholder="Enter your story title" required>
                        <label for="editStoryDesc">Description</label>
                        <textarea id="editStoryDesc" name="description"
                            placeholder="Tell readers about your story..."></textarea>
                        <label for="editStoryGenre">Genre</label>
                        <select id="editStoryGenre" name="genre" required>
                            <option value="">Choose Genre</option>
                            <option>Romance</option>
                            <option>Action</option>
                            <option>Fantasy</option>
                            <option>Drama</option>
                            <option>Comedy</option>
                            <option>Mystery</option>
                            <option>Fanfiction</option>
                        </select>
                        <label for="editStoryTags">Tags</label>
                        <input type="text" id="editStoryTags" name="tags" placeholder="space to divide the tags...">
                        <label for="editStoryProgress">Story Progress</label>
                        <select id="editStoryProgress" name="progress_status" style="width:100%;margin-top:8px;margin-bottom:15px;padding:10px 12px;border-radius:10px;border:1px solid #333;background:#2a2a2a;color:white;outline:none;font-family:inherit;cursor:pointer;">
                            <option value="ongoing">Ongoing</option>
                            <option value="complete">Complete</option>
                            <option value="hiatus">Hiatus</option>
                        </select>
                        <button type="submit" class="next-btn">Save Changes</button>
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
                <input type="range" id="cropZoom" min="0.5" max="2" step="0.01" value="1">
            </div>
        </div>
    </div>

</body>

</html>

