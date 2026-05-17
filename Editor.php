<?php
session_start();

if (!isset($_SESSION['story_id'])) {
    header('Location: Profile.php');
    exit;
}

$story_id   = (int)$_SESSION['story_id'];
// chapter_id dari URL kalau edit chapter lama, 0 kalau baru
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

// Kalau edit chapter lama, ambil datanya dari DB untuk isi textarea
$chapter_title = '';
$chapter_text  = '';

if ($chapter_id > 0) {
    require_once 'PHP/database.php';
    $pdo  = getDB();
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

    <div class="editor-wrapper">
        <div class="editor-box">
            <div class="editor-top-buttons">
                <button id="publishBtn">Publish</button>
                <button id="draftBtn">Draft</button>
                <button id="previewBtn">Preview</button>
            </div>
            <textarea class="editor-title"
                      placeholder="Judul Bab"><?= $chapter_title ?></textarea>
            <div class="title-line"></div>
            <!-- Bubble Chat — chapter_id diisi JS setelah chapter tersimpan -->
            <button id="bubbleBtn" class="bubble-btn">+ Bubble Chat</button>
            <textarea class="editor-paragraph"
                      placeholder="Paragraf..."><?= $chapter_text ?></textarea>
            <!-- Preview Bubble Chat -->
        <div class="bubble-preview-section" id="bubblePreviewSection" style="display:none;">
            <div class="bubble-preview-header">
            <span>💬 Bubble Chat</span>
            <a href="bubblechat.php?chapter_id=<?= $chapter_id ?>" class="edit-bubble-btn">Edit</a>
        </div>
            <div class="bubble-preview-area" id="bubblePreviewArea"></div>
        </div>
        </div>
    </div>
    <script src="JS/Editor.js"></script>
</body>
</html>