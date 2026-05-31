<?php
// ============================================================
// post_comment.php
// Mengirim komentar reader pada suatu chapter
// Digunakan oleh: Readingpage.html (section Comments)
//
// Skema DB:
//   comments: comment_id, user_id, chapter_id,
//             comment_text, comment_like, created_at
// ============================================================

session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json');

// ── Session Check ────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

// Admin tetap bisa berkomentar, tidak ada restriksi role di sini.
// Kalau mau batasi hanya 'user': uncomment blok di bawah.
// if ($_SESSION['role'] === 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Admin tidak dapat berkomentar.']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────
$chapter_id   = 0;
$comment_text = '';

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $comment_text = trim($jsonData['comment_text'] ?? $jsonData['comment'] ?? '');
        $chapter_id   = (int)($jsonData['chapter_id'] ?? 0);
    }
} else {
    $chapter_id   = (int)($_POST['chapter_id']   ?? 0);
    $comment_text = trim($_POST['comment_text']  ?? $_POST['comment'] ?? '');
}

if ($chapter_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'chapter_id tidak valid.']);
    exit;
}

if (empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Komentar tidak boleh kosong.']);
    exit;
}

if (mb_strlen($comment_text) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Komentar maksimal 2000 karakter.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo     = getDB();

// ── Validasi: chapter harus ada ──────────────────────────────
$stmt = $pdo->prepare("SELECT chapter_id FROM chapters WHERE chapter_id = ?");
$stmt->execute([$chapter_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Chapter tidak ditemukan.']);
    exit;
}

// ── Insert komentar ──────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO comments (user_id, chapter_id, comment_text, comment_like, created_at)
    VALUES (:user_id, :chapter_id, :comment_text, 0, NOW())
");
$stmt->execute([
    ':user_id'      => $user_id,
    ':chapter_id'   => $chapter_id,
    ':comment_text' => $comment_text,
]);
$comment_id = $pdo->lastInsertId();

// ── Trigger Notification untuk Author ────────────────────────
try {
    $stmtStory = $pdo->prepare("
        SELECT c.story_id, s.title AS story_title, s.user_id AS author_id 
        FROM chapters c 
        JOIN stories s ON c.story_id = s.story_id 
        WHERE c.chapter_id = ?
    ");
    $stmtStory->execute([$chapter_id]);
    $storyInfo = $stmtStory->fetch();

    if ($storyInfo && (int)$storyInfo['author_id'] !== (int)$user_id) {
        $actor_username = $_SESSION['username'] ?? 'Seseorang';
        $title = "@{$actor_username} mengomentari cerita Anda \"" . $storyInfo['story_title'] . "\"";
        $body = $comment_text;
        $link_url = "Readingpage.php?story_id=" . $storyInfo['story_id'] . "&chapter_id=" . $chapter_id;

        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, body, ref_story_id, actor_user_id, link_url, created_at)
            VALUES (?, 'story', ?, ?, ?, ?, ?, NOW())
        ");
        $stmtNotif->execute([
            $storyInfo['author_id'],
            $title,
            $body,
            $storyInfo['story_id'],
            $user_id,
            $link_url
        ]);
    }
} catch (Exception $e) {
    // Log error but don't fail the comment posting
    error_log("Failed to create comment notification: " . $e->getMessage());
}

// ── Kembalikan data komentar baru ────────────────────────────
// JS bisa langsung render tanpa reload halaman
echo json_encode([
    'success' => true,
    'message' => 'Komentar berhasil diposting.',
    'comment' => [
        'comment_id'   => $comment_id,
        'user_id'      => $user_id,
        'username'     => $_SESSION['username'] ?? '',
        'chapter_id'   => $chapter_id,
        'comment_text' => $comment_text,
        'comment_like' => 0,
        'created_at'   => date('Y-m-d H:i:s'),
    ],
]);
