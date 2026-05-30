<?php
session_start();
require_once 'PHP/database.php';

$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : 0;

if ($story_id <= 0) {
    header('Location: homepage.php');
    exit;
}

$pdo = getDB();
$from_editor = (isset($_GET['from']) && $_GET['from'] === 'editor') || (isset($_GET['preview']) && $_GET['preview'] == '1') || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'Editor.php') !== false);

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
    GROUP BY s.story_id, s.title, s.description, u.username, g.genre_name
");
$stmt->execute([$story_id]);
$story = $stmt->fetch();

if (!$story) {
    header('Location: homepage.php');
    exit;
}

// Ambil daftar chapter yang sudah published (atau semua chapter jika diakses dari editor)
$sql_chapters = "
    SELECT chapter_id, chapter_title
    FROM chapters
    WHERE story_id = ? AND status = 'published'
    ORDER BY created_at ASC
";
if ($from_editor) {
    $sql_chapters = "
        SELECT chapter_id, chapter_title
        FROM chapters
        WHERE story_id = ?
        ORDER BY created_at ASC
    ";
}
$stmt2 = $pdo->prepare($sql_chapters);
$stmt2->execute([$story_id]);
$chapters = $stmt2->fetchAll();

// Chapter yang dibuka — default chapter pertama
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
if ($chapter_id <= 0 && !empty($chapters)) {
    $chapter_id = $chapters[0]['chapter_id'];
}

// ====================================================================
// LOGIKA NAVIGASI URUTAN CHAPTER (Mencegah Undefined Variable)
// ====================================================================
$prev_chapter_id = null;
$next_chapter_id = null;
$current_index = 0;
$total_chapters = count($chapters);

foreach ($chapters as $index => $ch) {
    if ($ch['chapter_id'] == $chapter_id) {
        $current_index = $index + 1;
        
        // Periksa keberadaan chapter sebelum ini
        if (isset($chapters[$index - 1])) {
            $prev_chapter_id = $chapters[$index - 1]['chapter_id'];
        }
        // Periksa keberadaan chapter setelah ini
        if (isset($chapters[$index + 1])) {
            $next_chapter_id = $chapters[$index + 1]['chapter_id'];
        }
        break;
    }
}

$progress_pct = $total_chapters > 0 ? round(($current_index / $total_chapters) * 100, 2) : 0;
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
                        onclick="window.location.href='Readingpage.php?story_id=<?= $story_id ?>&chapter_id=<?= $ch['chapter_id'] ?><?= $from_editor ? '&from=editor' : '' ?>'">
                        <?= htmlspecialchars($ch['chapter_title']) ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <main class="reading-content">
        <div class="back-reading">
            <?php if ($from_editor): ?>
                <a href="Editor.php?story_id=<?= $story_id ?>&chapter_id=<?= $chapter_id ?>">
                    ← Back to Editor
                </a>
            <?php else: ?>
                <a href="Detstory.php?story_id=<?= $story_id ?>">
                    ← Back to Story
                </a>
            <?php endif; ?>
        </div>
        <div class="story-header">
            <p>
                <?= htmlspecialchars($story['genre_name']) ?>
                <?= $story['tags'] ? ' • ' . htmlspecialchars($story['tags']) : '' ?>
            </p>
            <h1><?= htmlspecialchars($story['title']) ?></h1>
            <p class="author">by <?= htmlspecialchars($story['username']) ?></p>
            
        </div>

        <section class="chapter-blocks" id="chapterBlocks">
            <?php if ($chapter_id > 0):
                $stmt_blocks = $pdo->prepare("
                    SELECT cb.block_id, cb.type, cb.content,
                        r.roomchat_id, r.theme, r.contact_name, r.my_avatar, r.contact_avatar, r.bg_image
                    FROM chapter_blocks cb
                    LEFT JOIN roomchats r ON cb.block_id = r.block_id
                    WHERE cb.chapter_id = ?
                    ORDER BY cb.sort_order ASC
                ");
                $stmt_blocks->execute([$chapter_id]);
                $blocks = $stmt_blocks->fetchAll();

                foreach ($blocks as $block):
                    if ($block['type'] === 'narration'): ?>
                        <div class="reader-narration">
                            <?= nl2br(htmlspecialchars($block['content'])) ?>
                        </div>

                    <?php elseif ($block['type'] === 'roomchat' && $block['roomchat_id']):
                        $stmt_b = $pdo->prepare("
                            SELECT bubble_text, contact_name, color, position, time_label, sender_avatar
                            FROM bubbles WHERE roomchat_id = ?
                            ORDER BY sort_order ASC
                        ");
                        $stmt_b->execute([$block['roomchat_id']]);
                        $bubbles = $stmt_b->fetchAll();
                        $isWa    = $block['theme'] === 'wa';
                    ?>
                        <div class="reader-roomchat theme-<?= $block['theme'] ?>">
                            <div class="reader-chat-header">
                                <div class="reader-avatar">
                                    <?php if (!empty($block['contact_avatar'])): ?>
                                        <img src="<?= $block['contact_avatar'] ?>" alt="avatar">
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="reader-contact-name"><?= htmlspecialchars($block['contact_name']) ?></div>
                                    <div class="reader-contact-status"><?= $isWa ? 'online' : 'iMessage' ?></div>
                                </div>
                            </div>
                            <div class="reader-chat-area" <?= !empty($block['bg_image']) ? 'style="background-image: url(' . $block['bg_image'] . '); background-size: cover; background-position: center;"' : '' ?>>
                                <?php
                                $isGroupChat = false;
                                foreach ($bubbles as $b) {
                                    if (!empty($b['sender_avatar']) || ($b['position'] === 'left' && !empty($b['contact_name']) && $b['contact_name'] !== $block['contact_name'])) {
                                        $isGroupChat = true;
                                        break;
                                    }
                                }
                                ?>
                                <?php foreach ($bubbles as $b): ?>
                                <div class="reader-bubble-row <?= $b['position'] ?>">
                                    <?php if ($b['position'] === 'left'): ?>
                                        <div class="reader-bubble-av">
                                            <?php if (!empty($b['sender_avatar'])): ?>
                                                <img src="<?= $b['sender_avatar'] ?>" alt="avatar">
                                            <?php elseif (!empty($block['contact_avatar'])): ?>
                                                <img src="<?= $block['contact_avatar'] ?>" alt="avatar">
                                            <?php else: ?>
                                                👤
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="reader-bubble <?= $b['position'] ?>"
                                        style="background:<?= htmlspecialchars($b['color']) ?>">
                                        <?php if ($b['position'] === 'left' && $isGroupChat && !empty($b['contact_name'])): ?>
                                            <div class="bubble-sender-name"><?= htmlspecialchars($b['contact_name']) ?></div>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($b['bubble_text']) ?>
                                        <span class="reader-bubble-time"><?= htmlspecialchars($b['time_label']) ?></span>
                                    </div>
                                    <?php if ($b['position'] === 'right'): ?>
                                        <div class="reader-bubble-av">
                                            <?php if (!empty($b['sender_avatar'])): ?>
                                                <img src="<?= $b['sender_avatar'] ?>" alt="avatar">
                                            <?php elseif (!empty($block['my_avatar'])): ?>
                                                <img src="<?= $block['my_avatar'] ?>" alt="avatar">
                                            <?php else: ?>
                                                🙂
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($bubbles)): ?>
                                    <p style="color:#888;text-align:center;padding:20px;font-size:13px;">Belum ada bubble.</p>
                                <?php endif; ?>
                            </div>
                            <div class="reader-chat-inputbar">
                                <div class="reader-input-fake"><?= $isWa ? 'Type a message' : 'iMessage' ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($blocks)): ?>
                    <p style="color:#888;text-align:center;padding:40px;font-size:13px;">
                        Konten chapter belum tersedia.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <div class="bottom-navigation">
            <?php if ($prev_chapter_id): ?>
                <a href="Readingpage.php?story_id=<?= $story_id ?>&chapter_id=<?= $prev_chapter_id ?><?= $from_editor ? '&from=editor' : '' ?>" class="nav-link-btn prev">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Prev
                </a>
            <?php else: ?>
                <span></span> <?php endif; ?>

            <div class="nav-progress-text">
                Chapter <?= $current_index ?> of <?= $total_chapters ?> (<?= round($progress_pct) ?>%)
            </div>

            <?php if ($next_chapter_id): ?>
                <a href="Readingpage.php?story_id=<?= $story_id ?>&chapter_id=<?= $next_chapter_id ?><?= $from_editor ? '&from=editor' : '' ?>" class="nav-link-btn next">
                    Next <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                </a>
            <?php else: ?>
                <span></span> <?php endif; ?>
        </div>
                    
        <?php if (!$from_editor): ?>
            <div class="chapter-actions">
               <button class="like-btn" id="likeChapterBtn">❤️ Like</button>
            </div>
        <?php endif; ?>

        <?php if (!$from_editor): ?>
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
        <?php endif; ?>

    </main>
</div>

<script>
    const CURRENT_STORY_ID = <?= (int)$story_id ?>;
    const CURRENT_CHAPTER_ID = <?= (int)$chapter_id ?>;
    const CURRENT_PROGRESS_PCT = <?= (float)$progress_pct ?>;
</script>
<script src="JS/readingpage.js"></script>
</body>
</html>