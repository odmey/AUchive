<?php
session_start();
require_once 'PHP/database.php';

// ── 1. Ambil & validasi story_id dari URL ────────────────────
$storyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$storyId || $storyId < 1) {
    header('Location: homepage.php');
    exit;
}

// ── 2. Query detail cerita ───────────────────────────────────
$pdo  = getDB();

// Increment views live!
$stmtIncView = $pdo->prepare("UPDATE stories SET total_views = total_views + 1 WHERE story_id = ?");
$stmtIncView->execute([$storyId]);

$stmt = $pdo->prepare("
    SELECT
        s.story_id,
        s.user_id,
        s.title,
        s.description,
        s.cover,
        s.status,
        s.total_views,
        s.total_likes,
        s.published_at,
        g.genre_name,
        u.username,
        u.name      AS author_name,
        u.profile_pic,
        u.bio,
        (SELECT COUNT(*) FROM chapters c WHERE c.story_id = s.story_id) AS chapter_count
    FROM stories s
    LEFT JOIN genres g ON g.genre_id   = s.genre_id
    LEFT JOIN users  u ON u.user_id    = s.user_id
    WHERE s.story_id = ?
    LIMIT 1
");
$stmt->execute([$storyId]);
$story = $stmt->fetch();

// Kalau story tidak ditemukan, balik ke homepage
if (!$story) {
    header('Location: homepage.php');
    exit;
}

// ── 2b. Query status interaksi user (like, follow, save) ──────
$isLiked = false;
$isSaved = false;
$isFollowing = false;

$isLoggedIn = isset($_SESSION['user_id']);

if ($isLoggedIn) {
    $currentUserId = $_SESSION['user_id'];
    
    // Cek likes
    $stmtLike = $pdo->prepare("SELECT 1 FROM story_likes WHERE user_id = ? AND story_id = ?");
    $stmtLike->execute([$currentUserId, $storyId]);
    $isLiked = (bool)$stmtLike->fetch();

    // Cek library
    $stmtLib = $pdo->prepare("
        SELECT 1 FROM library_stories ls
        JOIN library l ON l.library_id = ls.library_id
        WHERE l.user_id = ? AND ls.story_id = ?
    ");
    $stmtLib->execute([$currentUserId, $storyId]);
    $isSaved = (bool)$stmtLib->fetch();

    // Cek follow
    $stmtFollow = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmtFollow->execute([$currentUserId, $story['user_id']]);
    $isFollowing = (bool)$stmtFollow->fetch();
}

// ── 3. Query tag cerita ──────────────────────────────────────
$stmtTags = $pdo->prepare("
    SELECT t.tag_name
    FROM tags t
    INNER JOIN story_tags st ON st.tag_id = t.tag_id
    WHERE st.story_id = ?
");
$stmtTags->execute([$storyId]);
$tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);

// ── 4. Siapkan variabel tampilan ─────────────────────────────
// cover & profile_pic di DB sudah menyimpan path lengkap ("Uploads/...")
$coverSrc     = !empty($story['cover'])
    ? htmlspecialchars($story['cover'])
    : 'Pic/cover-placeholder.png';

$authorAvatar = !empty($story['profile_pic'])
    ? htmlspecialchars($story['profile_pic'])
    : 'Pic/profileicon.jpg';

$statusLabel  = match($story['status']) {
    'published' => 'Terbit',
    'ongoing'   => 'Ongoing',
    default     => 'Draft',
};

$tagList = !empty($tags)
    ? implode(' • ', array_map('htmlspecialchars', $tags))
    : '';

$genreTag = htmlspecialchars($story['genre_name'] ?? '');
$genrePart = $genreTag ? $genreTag . ($tagList ? ' • ' : '') : '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($story['title']) ?> — AUchive</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($story['description'] ?? '', 0, 160)) ?>">
    <link rel="stylesheet" href="CSS/detstory.css">
</head>

<body>

    <div class="back-button">
        <a href="homepage.php">✕</a>
    </div>

    <section class="story-detail-page">

        <!-- Story Cover -->
        <div class="cover-section">
            <img src="<?= $coverSrc ?>"
                 alt="Cover <?= htmlspecialchars($story['title']) ?>"
                 onerror="this.src='Pic/cover-placeholder.png'">
        </div>

        <!-- Story Info -->
        <div class="info-section">

            <h1 class="story-title"><?= htmlspecialchars($story['title']) ?></h1>

            <?php if ($genrePart || $tagList): ?>
            <p class="genre">
                <?= $genrePart . $tagList ?>
            </p>
            <?php endif; ?>

            <p class="status">
                Status: <?= $statusLabel ?>
            </p>

            <?php if (!empty($story['description'])): ?>
            <p class="description">
                <?= nl2br(htmlspecialchars($story['description'])) ?>
            </p>
            <?php endif; ?>

            <!-- Stats -->
            <div class="story-stats">
                <span>📚 <?= (int)$story['chapter_count'] ?> Chapter<?= $story['chapter_count'] != 1 ? 's' : '' ?></span>
                <span>👁️ <span id="viewCount"><?= (int)$story['total_views'] ?></span> Views</span>
                <span>❤️ <span id="likeCount"><?= (int)$story['total_likes'] ?></span> Likes</span>
            </div>

            <!-- Buttons -->
            <div class="action-buttons">
                <?php if ($isLoggedIn): ?>
                    <button class="like-btn<?= $isLiked ? ' active' : '' ?>"   id="likeBtn"><?= $isLiked ? 'Liked' : 'Like' ?></button>
                    <button class="save-btn<?= $isSaved ? ' active' : '' ?>"   id="saveBtn"><?= $isSaved ? 'Saved' : 'Save' ?></button>
                    <button class="follow-btn<?= $isFollowing ? ' active' : '' ?>" id="followBtn"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
                <?php endif; ?>
                <a href="Readingpage.php?story_id=<?= $storyId ?>">
                    <button class="read-btn" id="readBtn">Start Reading</button>
                </a>
            </div>

            <!-- Writer Info -->
            <a href="profile_person.php?id=<?= $story['user_id'] ?>" style="text-decoration:none; color:inherit;">
                <div class="writer-card" style="cursor:pointer; transition:0.2s;">
                    <img src="<?= $authorAvatar ?>"
                         alt="<?= htmlspecialchars($story['author_name'] ?? $story['username']) ?>"
                         onerror="this.src='Pic/profileicon.jpg'">

                    <div class="writer-info">
                        <h3>@<?= htmlspecialchars($story['username'] ?? '') ?></h3>
                        <p><?= htmlspecialchars($story['bio'] ?? 'Penulis AUchive') ?></p>
                        <small><?= (int)$story['chapter_count'] ?> Chapter<?= $story['chapter_count'] != 1 ? 's' : '' ?> ditulis</small>
                    </div>
                </div>
            </a>

        </div>

    </section>

    <?php if ($isLoggedIn): ?>
        <script>
            const STORY_ID = <?= $storyId ?>;
            const AUTHOR_ID = <?= (int)$story['user_id'] ?>;
        </script>
        <script src="JS/detstory.js"></script>
    <?php endif; ?>
</body>

</html>