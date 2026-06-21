<?php
session_start();
require_once 'src/Core/PHP/database.php';

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
        s.progress_status,
        s.total_views,
        s.total_likes,
        s.published_at,
        g.genre_name,
        u.username,
        u.name      AS author_name,
        u.profile_pic,
        u.bio,
        (SELECT COUNT(*) FROM chapters c WHERE c.story_id = s.story_id AND c.status = 'published') AS chapter_count,
        (SELECT COUNT(*) FROM stories st WHERE st.user_id = s.user_id AND st.status != 'draft') AS author_story_count
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
        WHERE l.user_id = ? AND ls.story_id = ? AND ls.is_saved = 1
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
    : 'Pic/PP kosongan.jpg';

$prog = $story['progress_status'] ?? 'ongoing';
$statusLabel  = match($prog) {
    'complete' => 'Complete',
    'hiatus'   => 'Hiatus',
    default     => 'Ongoing',
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
    <link rel="stylesheet" href="src/Story/CSS/detstory.css">
    <script src="src/Core/JS/custom_alert.js"></script>
</head>

<body>

    <div class="back-button">
        <a href="javascript:void(0);" id="backButtonLink">✕</a>
    </div>
    <script>
        (function() {
            // Catat asal halaman sebelum masuk ke halaman baca
            if (document.referrer && !document.referrer.includes('Readingpage.php') && document.referrer.includes(window.location.hostname)) {
                sessionStorage.setItem('story_back_url', document.referrer);
            }
            document.getElementById('backButtonLink').addEventListener('click', function(e) {
                e.preventDefault();
                const backUrl = sessionStorage.getItem('story_back_url');
                if (backUrl) {
                    window.location.href = backUrl;
                } else {
                    window.location.href = 'homepage.php';
                }
            });
        })();
    </script>

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
                <span class="status <?= htmlspecialchars($prog) ?>">
                    <?= $statusLabel ?>
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
                        <a href="search_result.php?q=<?= urlencode('#' . $tag) ?>" class="story-tag" style="text-decoration: none; display: inline-block;">#<?= htmlspecialchars($tag) ?></a>
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
                <?php else: ?>
                    <button class="fav-btn" onclick="handleFavoriteGuest(event)">Favorite</button>
                    <button class="save-btn" onclick="handleSaveGuest(event)">Save</button>
                <?php endif; ?>
                <a href="Readingpage.php?story_id=<?= $storyId ?>">
                    <button class="read-btn" id="readBtn">Start Reading</button>
                </a>
                <?php if ($isLoggedIn): ?>
                    <button class="report-btn" id="reportStoryBtn" title="Report Story">
                        <span class="material-symbols-outlined">flag</span>
                    </button>
                <?php else: ?>
                    <button class="report-btn" onclick="handleReportGuest(event)" title="Report Story">
                        <span class="material-symbols-outlined">flag</span>
                    </button>
                <?php endif; ?>
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
        <div class="writer-card" style="cursor:pointer; transition:0.2s;">
            <a href="profile_person.php?id=<?= $story['user_id'] ?>" style="text-decoration:none; color:inherit; display: flex; align-items: center; gap: 20px;">
                <img src="<?= $authorAvatar ?>"
                     alt="<?= htmlspecialchars($story['author_name'] ?? $story['username']) ?>"
                     onerror="this.src='Pic/PP kosongan.jpg'">

                <div class="writer-info">
                    <h3 style="margin: 0; color: #fff4a3; font-size: 18px; font-weight: 700;">@<?= htmlspecialchars($story['username'] ?? '') ?></h3>
                </div>
            </a>

            <?php if ($isLoggedIn && $currentUserId != $story['user_id']): ?>
                <div class="writer-actions" onclick="event.stopPropagation()">
                    <button class="follow-btn<?= $isFollowing ? ' active' : '' ?>" id="followBtn">
                        <?= $isFollowing ? 'Following' : 'Follow' ?>
                    </button>
                </div>
            <?php elseif (!$isLoggedIn): ?>
                <div class="writer-actions" onclick="event.stopPropagation()">
                    <button class="follow-btn" onclick="handleFollowGuest(event)">
                        Follow
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal Report Story -->
    <div id="reportModalContainer" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        <div style="background:#1e1e1e; border: 1px solid rgba(255, 244, 79, 0.2); border-radius:18px; width:90%; max-width:400px; padding: 24px; display:flex; flex-direction:column; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6); font-family: 'Poppins', sans-serif;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom: 10px;">
                <h3 style="margin:0; font-size:18px; color:#FFF44F; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined">flag</span> Report Story
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

    <?php if ($isLoggedIn): ?>
        <script>
            const STORY_ID = <?= $storyId ?>;
            const AUTHOR_ID = <?= (int)$story['user_id'] ?>;
        </script>
        <script src="src/Story/JS/detstory.js"></script>
    <?php else: ?>
        <script>
            function handleFollowGuest(e) {
                if (e) e.preventDefault();
                customConfirm("You must log in to follow this author. Would you like to log in now?").then((confirmed) => {
                    if (confirmed) {
                        window.location.href = 'homepage.php?auth=login&redirect=' + encodeURIComponent(window.location.href);
                    }
                });
            }
            function handleReportGuest(e) {
                if (e) e.preventDefault();
                customConfirm("You must log in to report this story. Would you like to log in now?").then((confirmed) => {
                    if (confirmed) {
                        window.location.href = 'homepage.php?auth=login&redirect=' + encodeURIComponent(window.location.href);
                    }
                });
            }
            function handleFavoriteGuest(e) {
                if (e) e.preventDefault();
                customConfirm("You must log in to favorite this story. Would you like to log in now?").then((confirmed) => {
                    if (confirmed) {
                        window.location.href = 'homepage.php?auth=login&redirect=' + encodeURIComponent(window.location.href);
                    }
                });
            }
            function handleSaveGuest(e) {
                if (e) e.preventDefault();
                customConfirm("You must log in to save this story. Would you like to log in now?").then((confirmed) => {
                    if (confirmed) {
                        window.location.href = 'homepage.php?auth=login&redirect=' + encodeURIComponent(window.location.href);
                    }
                });
            }
        </script>
    <?php endif; ?>
</body>

</html>
