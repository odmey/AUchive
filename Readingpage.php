<?php
session_start();
require_once 'PHP/database.php';

$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : 0;

if ($story_id <= 0) {
    header('Location: homepage.php');
    exit;
}

$pdo = getDB();

// Ambil data story + penulis + genre + tags
$stmt = $pdo->prepare("
    SELECT 
        s.title,
        s.description,
        u.username,
        g.genre_name,
        GROUP_CONCAT(t.tag_name SEPARATOR ', ') as tags
    FROM stories s
    JOIN users u ON s.user_id = u.user_id
    JOIN genres g ON s.genre_id = g.genre_id
    LEFT JOIN story_tags st ON s.story_id = st.story_id
    LEFT JOIN tags t ON st.tag_id = t.tag_id
    WHERE s.story_id = ?
    GROUP BY s.story_id
");
$stmt->execute([$story_id]);
$story = $stmt->fetch();

if (!$story) {
    header('Location: homepage.php');
    exit;
}

// Ambil daftar chapter yang sudah published
$stmt2 = $pdo->prepare("
    SELECT chapter_id, chapter_title
    FROM chapters
    WHERE story_id = ? AND status = 'published'
    ORDER BY created_at ASC
");
$stmt2->execute([$story_id]);
$chapters = $stmt2->fetchAll();

// Chapter yang dibuka — default chapter pertama
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
if ($chapter_id <= 0 && !empty($chapters)) {
    $chapter_id = $chapters[0]['chapter_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($story['title']) ?> - AUchive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bitter:ital,wght@0,100..900;1,100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/readingpage.css">
</head>
<body>
<div class="reading-page">

    <!-- SIDEBAR CHAPTER -->
    <aside class="chapter-sidebar">
        <h2>Chapters</h2>
        <div class="chapter-list">
            <?php if (empty($chapters)): ?>
                <p style="color:#888; font-size:13px; padding:10px;">
                    Belum ada chapter.
                </p>
            <?php else: ?>
                <?php foreach ($chapters as $ch): ?>
                    <button
                        class="chapter-btn <?= $ch['chapter_id'] == $chapter_id ? 'active' : '' ?>"
                        onclick="window.location.href='Readingpage.php?story_id=<?= $story_id ?>&chapter_id=<?= $ch['chapter_id'] ?>'">
                        <?= htmlspecialchars($ch['chapter_title']) ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="reading-content">

        <!-- HEADER STORY -->
        <div class="story-header">
            <p>
                <?= htmlspecialchars($story['genre_name']) ?>
                <?= $story['tags'] ? ' • ' . htmlspecialchars($story['tags']) : '' ?>
            </p>
            <h1><?= htmlspecialchars($story['title']) ?></h1>
            <p class="author">by <?= htmlspecialchars($story['username']) ?></p>
            <?php if ($story['description']): ?>
                <p class="story-desc"><?= htmlspecialchars($story['description']) ?></p>
                <!-- tampilkan isi chapternya yang kita tulis itu -->
            <?php endif; ?>
        </div>

        <!-- BUBBLE CHAT — belum dirender, menyusul -->
        <section class="chat-story" id="chatStory">
            <p style="color:#888; text-align:center; padding:40px;">
                Konten chapter akan segera hadir.
            </p>
        </section>

        <!-- AKSI -->
        <div class="chapter-actions">
            <button class="like-btn" onclick="addToLibrary(<?= $story_id ?>)">
                ❤️ Like Chapter
            </button>
        </div>

        <!-- KOMENTAR -->
        <section class="comments-section">
            <h2>Comments</h2>
            <div class="comment-box">
                <textarea
                    id="commentInput"
                    placeholder="Write your thoughts about this chapter...">
                </textarea>
                <button class="post-btn" onclick="postComment()">
                    Post Comment
                </button>
            </div>
            <div id="commentList"></div>
        </section>

    </main>
</div>

<script src="JS/readingpage.js"></script>
</body>
</html>