<?php
// ============================================================
// like_chapter_action.php
// Menyukai (like) / membatalkan suka (unlike) suatu chapter
// Digunakan oleh: Readingpage.php
// ============================================================

session_start();
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

// 1. Session Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// 2. Parse Input
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$chapter_id = 0;

if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $chapter_id = (int)($jsonData['chapter_id'] ?? 0);
    }
} else {
    $chapter_id = (int)($_POST['chapter_id'] ?? 0);
}

$user_id = $_SESSION['user_id'];

if ($chapter_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'chapter_id tidak valid.']);
    exit;
}

$pdo = getDB();

// 3. Validasi Chapter & Ambil Story ID
$stmt = $pdo->prepare("SELECT story_id, chapter_title FROM chapters WHERE chapter_id = ?");
$stmt->execute([$chapter_id]);
$chapter = $stmt->fetch();

if (!$chapter) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Chapter tidak ditemukan.']);
    exit;
}

$story_id = (int)$chapter['story_id'];
$chapter_title = $chapter['chapter_title'];

try {
    $pdo->beginTransaction();

    // 4. Cek apakah sudah dilike
    $stmt = $pdo->prepare("SELECT like_id FROM chapter_likes WHERE user_id = ? AND chapter_id = ?");
    $stmt->execute([$user_id, $chapter_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 5. Unlike
        $stmt = $pdo->prepare("DELETE FROM chapter_likes WHERE user_id = ? AND chapter_id = ?");
        $stmt->execute([$user_id, $chapter_id]);

        $stmtUpdate = $pdo->prepare("UPDATE chapters SET likes = GREATEST(0, likes - 1) WHERE chapter_id = ?");
        $stmtUpdate->execute([$chapter_id]);

        $action = 'unliked';
    } else {
        // 6. Like
        $stmt = $pdo->prepare("INSERT INTO chapter_likes (user_id, chapter_id, liked_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $chapter_id]);

        $stmtUpdate = $pdo->prepare("UPDATE chapters SET likes = likes + 1 WHERE chapter_id = ?");
        $stmtUpdate->execute([$chapter_id]);

        $action = 'liked';
    }

    // 7. Recalculate story's total_likes (Sum of all its chapter likes)
    $stmtSum = $pdo->prepare("SELECT IFNULL(SUM(likes), 0) FROM chapters WHERE story_id = ?");
    $stmtSum->execute([$story_id]);
    $total_story_likes = (int)$stmtSum->fetchColumn();

    $stmtUpdateStory = $pdo->prepare("UPDATE stories SET total_likes = ? WHERE story_id = ?");
    $stmtUpdateStory->execute([$total_story_likes, $story_id]);

    $pdo->commit();

    // 8. Hitung total likes chapter terbaru
    $stmtCount = $pdo->prepare("SELECT likes FROM chapters WHERE chapter_id = ?");
    $stmtCount->execute([$chapter_id]);
    $likes = (int)$stmtCount->fetchColumn();

    echo json_encode([
        'success'           => true,
        'action'            => $action,
        'likes'             => $likes,
        'total_story_likes' => $total_story_likes,
        'message'           => $action === 'liked' ? 'Menyukai chapter.' : 'Membatalkan suka chapter.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status suka chapter: ' . $e->getMessage()]);
}
?>
