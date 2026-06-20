<?php
session_start();
require_once 'src/Core/PHP/database.php';

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

    <link rel="stylesheet" href="src/User/CSS/style_profile.css">
    <script src="src/User/JS/profile_person.js" defer></script>
    <script src="src/Core/JS/custom_alert.js"></script>

    <title>Profile – @<?= htmlspecialchars($author['username']) ?></title>
</head>

<body class="own-profile">
    <header class="profile-header">
        <div class="left">
            <a href="homepage.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        </div>

        <div class="center">
            <h3 id="headerUsername"><?= htmlspecialchars($author['username']) ?></h3>
            <p><?= count($stories) ?> posts</p>
        </div>
    </header>

    <div class="profile-container">
        <div class="cover">
            <img src="<?= $profileBanSrc ?>" class="cover-img" onerror="this.style.display='none'">
        </div>

        <div class="profile-info">
            <img src="<?= $profilePicSrc ?>" class="profile-pic" onerror="this.style.opacity='0'">

            <div class="profile-actions-row">
                <?php if ($isLoggedIn): ?>
                    <button class="follow-btn<?= $isFollowing ? ' active' : '' ?>" id="followBtn" style="<?= $isFollowing ? 'background:transparent; color:#FFF44F; border:1px solid #FFF44F;' : '' ?>">
                        <?= $isFollowing ? 'Following' : 'Follow' ?>
                    </button>
                    <button class="report-btn" id="reportUserBtn" title="Report User">
                        <span class="material-symbols-outlined">flag</span>
                    </button>
                <?php else: ?>
                    <button class="follow-btn" onclick="window.location.href='homepage.php?auth=login'">
                        Follow
                    </button>
                    <button class="report-btn" onclick="window.location.href='homepage.php?auth=login'" title="Report User">
                        <span class="material-symbols-outlined">flag</span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="profile-details">
                <h2 class="name"><?= htmlspecialchars($author['name']) ?></h2>
                <p class="username">@<?= htmlspecialchars($author['username']) ?></p>
                
                <p class="bio">
                    <?= nl2br(htmlspecialchars($author['bio'] ?? 'Penulis AUchive.')) ?>
                </p>

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
        </div>
    </div>

    <div class="story-section">
        <?php if (empty($stories)): ?>
            <p style="text-align:center; color:#888; padding:30px;">This author hasn't published any stories yet.</p>
        <?php else: ?>
            <?php foreach ($stories as $s): 
                $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
            ?>
                <div class="story-card" style="cursor:pointer;" onclick="window.location.href='Detstory.php?id=<?= $s['story_id'] ?>'">
                    <div class="story-cover">
                        <img src="<?= $coverSrc ?>" onerror="this.style.display='none'">
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

    <!-- Modal Followers / Following (Matched to Profile.php) -->
    <div id="followModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#1e1e1e; border-radius:12px; width:90%; max-width:400px; max-height:80vh; overflow:hidden; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #333;">
                <h3 id="modalTitle" style="margin:0; font-size:16px; color:#fff;">Followers</h3>
                <span id="closeFollowModal" style="cursor:pointer; font-size:22px; color:#aaa;">&times;</span>
            </div>
            <div id="userList" style="overflow-y:auto; padding:10px 0;"></div>
        </div>
    </div>

    <!-- Modal Report User -->
    <div id="reportModalContainer" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        <div style="background:#1e1e1e; border: 1px solid rgba(255, 244, 79, 0.2); border-radius:18px; width:90%; max-width:400px; padding: 24px; display:flex; flex-direction:column; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom: 10px;">
                <h3 style="margin:0; font-size:18px; color:#FFF44F; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined">flag</span> Report User
                </h3>
                <span id="closeReportModal" style="cursor:pointer; font-size:22px; color:#aaa; font-weight:bold;">&times;</span>
            </div>
            
            <form id="reportForm" style="display:flex; flex-direction:column; gap:12px;">
                <div>
                    <label style="color:#ccc; font-size:13px; display:block; margin-bottom:6px;">Reason for Report</label>
                    <select id="reportReason" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #333; background:#2a2a2a; color:white; outline:none; font-family:inherit;">
                        <option value="spam">Spam / Advertising</option>
                        <option value="harassment">Harassment / Bullying</option>
                        <option value="inappropriate">Inappropriate / Adult Content</option>
                        <option value="violence">Violence / Gore</option>
                        <option value="plagiarism">Plagiarism / Copyright Violation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label style="color:#ccc; font-size:13px; display:block; margin-bottom:6px;">Details / Description (Optional)</label>
                    <textarea id="reportDescription" placeholder="Provide additional details..." style="width:100%; min-height:80px; padding:10px; border-radius:8px; border:1px solid #333; background:#2a2a2a; color:white; outline:none; resize:vertical; font-family:inherit;"></textarea>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" id="cancelReportBtn" style="background:#444; color:white; border:none; padding:8px 16px; border-radius:20px; font-weight:600; cursor:pointer; font-family:inherit;">Cancel</button>
                    <button type="submit" id="submitReportBtn" style="background:#FFF44F; color:black; border:none; padding:8px 16px; border-radius:20px; font-weight:600; cursor:pointer; font-family:inherit;">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Inject database follow lists directly to JS variables
        const followersData = <?= json_encode(array_map(fn($x) => [
            'user_id'  => $x['user_id'],
            'name' => htmlspecialchars($x['name']),
            'username' => '@' . htmlspecialchars($x['username']),
            'image' => !empty($x['profile_pic']) ? htmlspecialchars($x['profile_pic']) : 'Pic/profileicon.jpg'
        ], $followersList)) ?>;
        
        const followingData = <?= json_encode(array_map(fn($x) => [
            'user_id'  => $x['user_id'],
            'name' => htmlspecialchars($x['name']),
            'username' => '@' . htmlspecialchars($x['username']),
            'image' => !empty($x['profile_pic']) ? htmlspecialchars($x['profile_pic']) : 'Pic/profileicon.jpg'
        ], $followingList)) ?>;
        
        const AUTHOR_ID = <?= $authorId ?>;
    </script>
</body>

</html>

