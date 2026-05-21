<?php
session_start();

if (!isset($_SESSION['story_id'])) {
    header('Location: Profile.php');
    exit;
}

$story_id   = (int)$_SESSION['story_id'];
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
// chapter_id dari URL kalau edit chapter lama, 0 kalau baru

// Kalau edit chapter lama, ambil datanya dari DB untuk isi textarea
require_once 'PHP/database.php';
$pdo  = getDB();

$stmt = $pdo->prepare("
    SELECT chapter_id, chapter_title, status
    FROM chapters WHERE story_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$story_id]);
$all_chapters = $stmt->fetchAll();

if ($chapter_id <= 0 && !empty($all_chapters)) {
    $chapter_id = $all_chapters[0]['chapter_id'];
}

$chapter_title = '';
$chapter_text  = '';
if ($chapter_id > 0) {
    $stmt = $pdo->prepare("
        SELECT chapter_title, chapter_text
        FROM chapters
        WHERE chapter_id = ? AND story_id = ?
    ");
    $stmt->execute([$chapter_id, $story_id]);
    $row = $stmt->fetch();
    if ($row) {
        $chapter_title = htmlspecialchars($row['chapter_title']);
        $chapter_text  = htmlspecialchars($row['chapter_text']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/style_story.css">
</head>
<body>

    <!-- Meta tersembunyi — dibaca oleh Editor.js -->
    <input type="hidden" id="meta-story-id"   value="<?= $story_id ?>">
    <input type="hidden" id="meta-chapter-id" value="<?= $chapter_id ?>">

    <header>
        <a href="Profile.php" class="back-link">
            <span class="material-symbols-outlined">arrow_back</span>
            Back Edit
        </a>
        <div class="right"><img src="Pic/TextLogo.png" alt=""></div>
    </header>

    <div class="editor-layout">
        <!-- SIDEBAR CHAPTER LIST -->
        <aside class="chapter-sidebar">
            <div class="sidebar-header">
                <span>Chapters</span>
                <button class="add-chapter-btn" onclick="addNewChapter()">+ New</button>
            </div>
            <div class="chapter-list">
                <?php if (empty($all_chapters)): ?>
                    <p class="no-chapter">Belum ada chapter.</p>
                <?php else: ?>
                    <?php foreach ($all_chapters as $ch): ?>
                        <div class="chapter-item <?= $ch['chapter_id'] == $chapter_id ? 'active' : '' ?>">
                            <a href="Editor.php?chapter_id=<?= $ch['chapter_id'] ?>" class="chapter-link">
                                <?= htmlspecialchars($ch['chapter_title']) ?>
                                <span class="chapter-status <?= $ch['status'] ?>"><?= $ch['status'] ?></span>
                            </a>
                            <button class="delete-chapter-btn"
                                onclick="deleteChapter(<?= $ch['chapter_id'] ?>)"
                                title="Hapus chapter">✕</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <div class="editor-wrapper">
            <div class="editor-box">
                <div class="editor-top-buttons">
                    <button id="publishBtn">Publish</button>
                    <button id="draftBtn">Draft</button>
                    <button id="previewBtn">Preview</button>
                </div>
                <textarea class="editor-title" placeholder="Judul Bab"><?= $chapter_title ?></textarea>
                <div class="title-line"></div>
                <div id="blocksContainer"></div>

                <div class="add-block-row">
                    <button class="add-block-btn narration" onclick="addNarrationBlock()">+ Narasi</button>
                    <button class="add-block-btn roomchat"  onclick="addRoomchatBlock()">+ Roomchat</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="JS/Editor.js"></script>
</body>
</html>