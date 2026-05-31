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
        (SELECT COUNT(*) FROM chapters c WHERE c.story_id = s.story_id AND c.status = 'published') AS chapter_count,
        (SELECT COUNT(*) FROM chapters c JOIN stories st ON c.story_id = st.story_id WHERE st.user_id = s.user_id AND c.status = 'published') AS author_chapter_count
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
$isFavorite = false;
$isSaved = false;
$isFollowing = false;

$isLoggedIn = isset($_SESSION['user_id']);

if ($isLoggedIn) {
    $currentUserId = $_SESSION['user_id'];
    
    // Cek favorite
    $stmtFav = $pdo->prepare("
        SELECT ls.is_favorite FROM library_stories ls
        JOIN library l ON l.library_id = ls.library_id
        WHERE l.user_id = ? AND ls.story_id = ?
    ");
    $stmtFav->execute([$currentUserId, $storyId]);
    $favRow = $stmtFav->fetch();
    if ($favRow) {
        $isFavorite = (bool)$favRow['is_favorite'];
    }

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

// ── 3b. Query list chapter cerita ─────────────────────────────
$stmtChapters = $pdo->prepare("
    SELECT chapter_id, chapter_title
    FROM chapters
    WHERE story_id = ? AND status = 'published'
    ORDER BY created_at ASC
");
$stmtChapters->execute([$storyId]);
$storyChapters = $stmtChapters->fetchAll();

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/detstory.css">
</head>

<body>

    <div class="back-button">
        <a href="javascript:void(0);" onclick="if(document.referrer && document.referrer.includes(window.location.hostname)) { history.back(); } else { window.location.href='homepage.php'; }">✕</a>
    </div>

    <section class="story-detail-page">

        <!-- Story Cover -->
        <div class="cover-section">
            <img src="<?= $coverSrc ?>"
                 alt="Cover <?= htmlspecialchars($story['title']) ?>"
                 onerror="this.src='Pic/cover-placeholder.png'">

            <!-- Chapters list for Desktop / Full screen (hidden on mobile/windowed) -->
            <?php if (!empty($storyChapters)): ?>
            <div class="desktop-chapters-section">
                <h2 class="chapters-title-label">Chapters</h2>
                <div class="chapters-pill-list">
                    <?php foreach ($storyChapters as $ch): ?>
                        <a href="Readingpage.php?story_id=<?= $storyId ?>&chapter_id=<?= $ch['chapter_id'] ?>" class="chapter-pill-btn">
                            <?= htmlspecialchars($ch['chapter_title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Story Info (Right Column) -->
        <div class="info-section">
            <h1 class="story-title"><?= htmlspecialchars($story['title']) ?></h1>

            <div class="story-meta-row">
                <span class="status">
                    Status: <?= $statusLabel ?>
                </span>

                <?php if (!empty($story['genre_name'])): ?>
                <span class="genre">
                    <?= htmlspecialchars($story['genre_name']) ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($story['description'])): ?>
            <div class="description-container" id="descContainer">
                <p class="description" id="descText"><?= nl2br(htmlspecialchars($story['description'])) ?><span class="desc-ellipsis" id="descEllipsis" style="display: none; color: #ffe66d;"> (.....)</span></p>
            </div>
            <button class="toggle-desc-btn" id="toggleDescBtn" style="display: none;" onclick="toggleDescription()">lihat lebih banyak</button>
            <script>
                function initDescToggle() {
                    const container = document.getElementById('descContainer');
                    const text = document.getElementById('descText');
                    const btn = document.getElementById('toggleDescBtn');
                    const ellipsis = document.getElementById('descEllipsis');
                    if (!container || !text || !btn) return;
                    
                    // We check height temporarily set to auto to get real scrollHeight
                    container.style.maxHeight = 'none';
                    const fullHeight = text.scrollHeight;
                    
                    if (fullHeight > 120) {
                        if (!container.classList.contains('expanded')) {
                            container.style.maxHeight = '120px';
                            if (ellipsis) ellipsis.style.display = 'inline';
                        } else {
                            container.style.maxHeight = 'none';
                            if (ellipsis) ellipsis.style.display = 'none';
                        }
                        btn.style.display = 'inline-block';
                    } else {
                        container.style.maxHeight = 'none';
                        btn.style.display = 'none';
                        if (ellipsis) ellipsis.style.display = 'none';
                    }
                }
                function toggleDescription() {
                    const container = document.getElementById('descContainer');
                    const btn = document.getElementById('toggleDescBtn');
                    const ellipsis = document.getElementById('descEllipsis');
                    if (!container || !btn) return;
                    
                    if (container.classList.contains('expanded')) {
                        container.classList.remove('expanded');
                        container.style.maxHeight = '120px';
                        btn.textContent = 'lihat lebih banyak';
                        if (ellipsis) ellipsis.style.display = 'inline';
                    } else {
                        container.classList.add('expanded');
                        container.style.maxHeight = 'none';
                        btn.textContent = 'lebih sedikit';
                        if (ellipsis) ellipsis.style.display = 'none';
                    }
                }
                window.addEventListener('DOMContentLoaded', initDescToggle);
                window.addEventListener('resize', initDescToggle);
            </script>
            <?php endif; ?>

            <!-- Tag List Section: Below description, above stats -->
            <?php if (!empty($tags)): ?>
            <div class="story-tags-container">
                <div class="tags-wrapper" id="tagsWrapper">
                    <?php foreach ($tags as $tag): ?>
                        <span class="story-tag">#<?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <button class="toggle-tags-btn" id="toggleTagsBtn" style="display: none;" onclick="toggleTags()">lebih banyak</button>
            </div>
            <script>
                function initTagsToggle() {
                    const wrapper = document.getElementById('tagsWrapper');
                    const btn = document.getElementById('toggleTagsBtn');
                    if (!wrapper || !btn) return;
                    
                    if (wrapper.scrollHeight > wrapper.clientHeight) {
                        btn.style.display = 'inline-block';
                    } else {
                        btn.style.display = 'none';
                    }
                }
                function toggleTags() {
                    const wrapper = document.getElementById('tagsWrapper');
                    const btn = document.getElementById('toggleTagsBtn');
                    if (!wrapper || !btn) return;
                    
                    wrapper.classList.toggle('expanded');
                    if (wrapper.classList.contains('expanded')) {
                        btn.textContent = 'lebih sedikit';
                    } else {
                        btn.textContent = 'lebih banyak';
                    }
                }
                window.addEventListener('DOMContentLoaded', initTagsToggle);
                window.addEventListener('resize', initTagsToggle);
            </script>
            <?php endif; ?>

            <!-- Stats -->
            <div class="story-stats">
                <span><span class="material-symbols-outlined">menu_book</span> <?= (int)$story['chapter_count'] ?></span>
                <span><span class="material-symbols-outlined">visibility</span> <span id="viewCount"><?= (int)$story['total_views'] ?></span></span>
                <span><span class="material-symbols-outlined">favorite</span> <span id="likeCount"><?= (int)$story['total_likes'] ?></span></span>
            </div>

            <!-- Buttons -->
            <div class="action-buttons">
                <?php if ($isLoggedIn): ?>
                    <button class="fav-btn<?= $isFavorite ? ' active' : '' ?>"   id="favBtn"><?= $isFavorite ? 'Favorited' : 'Favorite' ?></button>
                    <button class="save-btn<?= $isSaved ? ' active' : '' ?>"   id="saveBtn"><?= $isSaved ? 'Saved' : 'Save' ?></button>
                    <button class="follow-btn<?= $isFollowing ? ' active' : '' ?>" id="followBtn"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
                <?php endif; ?>
                <a href="Readingpage.php?story_id=<?= $storyId ?>">
                    <button class="read-btn" id="readBtn">Start Reading</button>
                </a>
            </div>

            <!-- Chapters list for Mobile / Windowed (hidden on desktop) -->
            <?php if (!empty($storyChapters)): ?>
            <div class="mobile-chapters-section">
                <h2 class="chapters-title-label">Chapters</h2>
                <div class="chapters-pill-list">
                    <?php foreach ($storyChapters as $ch): ?>
                        <a href="Readingpage.php?story_id=<?= $storyId ?>&chapter_id=<?= $ch['chapter_id'] ?>" class="chapter-pill-btn">
                            <?= htmlspecialchars($ch['chapter_title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Writer Info -->
        <a href="profile_person.php?id=<?= $story['user_id'] ?>" style="text-decoration:none; color:inherit; display: block; width: 100%;">
            <div class="writer-card" style="cursor:pointer; transition:0.2s;">
                <img src="<?= $authorAvatar ?>"
                     alt="<?= htmlspecialchars($story['author_name'] ?? $story['username']) ?>"
                     onerror="this.src='Pic/profileicon.jpg'">

                <div class="writer-info">
                    <h3>@<?= htmlspecialchars($story['username'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($story['bio'] ?? 'Penulis AUchive') ?></p>
                    <small><?= (int)$story['author_chapter_count'] ?> Chapter<?= $story['author_chapter_count'] != 1 ? 's' : '' ?> ditulis</small>
                </div>
            </div>
        </a>
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