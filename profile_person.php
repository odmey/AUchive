<?php
session_start();
require_once 'PHP/database.php';

// 1. Ambil & validasi ID target author dari URL
$authorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$authorId || $authorId < 1) {
    header('Location: homepage.php');
    exit;
}

// 2. Redirect jika target adalah diri sendiri
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $authorId) {
    header('Location: Profile.php');
    exit;
}

$pdo = getDB();

// 3. Query detail author
$stmt = $pdo->prepare("
    SELECT username, name, bio, profile_pic, profile_ban, created_at 
    FROM users 
    WHERE user_id = ? AND role = 'user'
    LIMIT 1
");
$stmt->execute([$authorId]);
$author = $stmt->fetch();

if (!$author) {
    header('Location: homepage.php');
    exit;
}

// 4. Query statistika (Followers & Following counts)
$stmtFollowersCount = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
$stmtFollowersCount->execute([$authorId]);
$followersCount = (int)$stmtFollowersCount->fetchColumn();

$stmtFollowingCount = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
$stmtFollowingCount->execute([$authorId]);
$followingCount = (int)$stmtFollowingCount->fetchColumn();

// 5. Cek apakah user saat ini sudah follow
$isFollowing = false;
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    $stmtCheck = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmtCheck->execute([$_SESSION['user_id'], $authorId]);
    $isFollowing = (bool)$stmtCheck->fetch();
}

// 6. Query cerita terbitan author
$stmtStories = $pdo->prepare("
    SELECT s.story_id, s.title, s.description, s.cover,
           g.genre_name
    FROM stories s
    LEFT JOIN genres g ON g.genre_id = s.genre_id
    WHERE s.user_id = ? AND s.status = 'published'
    ORDER BY s.published_at DESC
");
$stmtStories->execute([$authorId]);
$stories = $stmtStories->fetchAll();

// 7. Ambil daftar followers & following untuk modal
$stmtFollowersList = $pdo->prepare("
    SELECT u.user_id, u.name, u.username, u.profile_pic 
    FROM followers f
    JOIN users u ON u.user_id = f.follower_id
    WHERE f.following_id = ?
");
$stmtFollowersList->execute([$authorId]);
$followersList = $stmtFollowersList->fetchAll();

$stmtFollowingList = $pdo->prepare("
    SELECT u.user_id, u.name, u.username, u.profile_pic 
    FROM followers f
    JOIN users u ON u.user_id = f.following_id
    WHERE f.follower_id = ?
");
$stmtFollowingList->execute([$authorId]);
$followingList = $stmtFollowingList->fetchAll();

// Formatting paths
$profilePicSrc = !empty($author['profile_pic']) ? htmlspecialchars($author['profile_pic']) : 'Pic/profileicon.jpg';
$profileBanSrc = !empty($author['profile_ban']) ? htmlspecialchars($author['profile_ban']) : 'Pic/profilebanner.jpg';
$joinDate = date('F Y', strtotime($author['created_at']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <link rel="stylesheet" href="CSS/profile_person.css">
    <script src="JS/profile_person.js" defer></script>

    <title>Profile – @<?= htmlspecialchars($author['username']) ?></title>
</head>

<body>
    <header class="profile-header">
        <div class="left">
            <a href="homepage.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        </div>

        <div class="center">
            <h3><?= htmlspecialchars($author['name']) ?></h3>
            <p><?= count($stories) ?> published stories</p>
        </div>
    </header>

    <div class="profile-container">
        <div class="cover">
            <img src="<?= $profileBanSrc ?>" class="cover-img" onerror="this.src='Pic/profilebanner.jpg'">
        </div>

        <div class="profile-info">
            <img src="<?= $profilePicSrc ?>" class="profile-pic" onerror="this.src='Pic/profileicon.jpg'">
            <div class="top-row">
                <div>
                    <div class="name-wrap">
                        <h2 class="name"><?= htmlspecialchars($author['name']) ?></h2>
                    </div>
                    <p class="username">@<?= htmlspecialchars($author['username']) ?></p>
                </div>

                <div class="profile-actions">
                    <?php if ($isLoggedIn): ?>
                        <button class="follow-btn<?= $isFollowing ? ' active' : '' ?>" id="followBtn" style="<?= $isFollowing ? 'background:transparent; color:#FFF44F; border:1px solid #FFF44F;' : '' ?>">
                            <?= $isFollowing ? 'Following' : 'Follow' ?>
                        </button>
                    <?php else: ?>
                        <button class="follow-btn" onclick="window.location.href='homepage.php?auth=login'">
                            Follow
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <p class="bio">
                <?= nl2br(htmlspecialchars($author['bio'] ?? 'Penulis AUchive.')) ?>
            </p>

            <p class="join">Joined <?= $joinDate ?></p>

            <div class="stats">
                <span class="stat-btn" id="followingBtn">
                    <b id="followingCountVal"><?= $followingCount ?></b> Following
                </span>
                <span class="stat-btn" id="followersBtn">
                    <b id="followersCountVal"><?= $followersCount ?></b> Followers
                </span>
            </div>
        </div>
    </div>

    <div class="story-section">
        <?php if (empty($stories)): ?>
            <p style="text-align:center; color:#888; padding:30px;">Belum ada cerita yang dipublikasikan oleh penulis ini.</p>
        <?php else: ?>
            <?php foreach ($stories as $s): 
                $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
            ?>
                <div class="story-card" style="cursor:pointer;" onclick="window.location.href='Detstory.php?id=<?= $s['story_id'] ?>'">
                    <div class="story-cover">
                        <img src="<?= $coverSrc ?>" onerror="this.src='Pic/cover-placeholder.png'">
                    </div>
                    <div class="story-content">
                        <div class="story-title">
                            <?= htmlspecialchars($s['title']) ?>
                        </div>
                        <div class="story-desc">
                            <?= htmlspecialchars($s['description'] ?? '') ?>
                        </div>
                        <div class="story-tags">
                            <?php if (!empty($s['genre_name'])): ?>
                                <span class="story-tag"><?= htmlspecialchars($s['genre_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="follow-modal" id="followModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Followers</h3>
                <span class="material-symbols-outlined close-modal" id="closeModal">
                    close
                </span>
            </div>
            <div class="user-list" id="userList">
            </div>
        </div>
    </div>

    <script>
        // Inject database follow lists directly to JS variables
        const followersData = <?= json_encode(array_map(fn($x) => [
            'name' => htmlspecialchars($x['name']),
            'username' => '@' . htmlspecialchars($x['username']),
            'image' => !empty($x['profile_pic']) ? htmlspecialchars($x['profile_pic']) : 'Pic/profileicon.jpg'
        ], $followersList)) ?>;
        
        const followingData = <?= json_encode(array_map(fn($x) => [
            'name' => htmlspecialchars($x['name']),
            'username' => '@' . htmlspecialchars($x['username']),
            'image' => !empty($x['profile_pic']) ? htmlspecialchars($x['profile_pic']) : 'Pic/profileicon.jpg'
        ], $followingList)) ?>;
        
        const AUTHOR_ID = <?= $authorId ?>;
    </script>
</body>

</html>
