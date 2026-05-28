<?php
// ============================================================
// like_action.php
// Menyukai (like) / membatalkan suka (unlike) suatu cerita
// Digunakan oleh: Detstory.php
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
$story_id = 0;

if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $story_id = (int)($jsonData['story_id'] ?? 0);
    }
} else {
    $story_id = (int)($_POST['story_id'] ?? 0);
}

$user_id = $_SESSION['user_id'];

if ($story_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'story_id tidak valid.']);
    exit;
}

$pdo = getDB();

// 3. Validasi Cerita & Ambil Author
$stmt = $pdo->prepare("SELECT user_id, title FROM stories WHERE story_id = ?");
$stmt->execute([$story_id]);
$story = $stmt->fetch();

if (!$story) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Cerita tidak ditemukan.']);
    exit;
}

$author_id = (int)$story['user_id'];
$story_title = $story['title'];

try {
    $pdo->beginTransaction();

    // 4. Cek apakah sudah dilike
    $stmt = $pdo->prepare("SELECT like_id FROM story_likes WHERE user_id = ? AND story_id = ?");
    $stmt->execute([$user_id, $story_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 5. Unlike
        $stmt = $pdo->prepare("DELETE FROM story_likes WHERE user_id = ? AND story_id = ?");
        $stmt->execute([$user_id, $story_id]);

        $stmtUpdate = $pdo->prepare("UPDATE stories SET total_likes = GREATEST(0, total_likes - 1) WHERE story_id = ?");
        $stmtUpdate->execute([$story_id]);

        $action = 'unliked';
    } else {
        // 6. Like
        $stmt = $pdo->prepare("INSERT INTO story_likes (user_id, story_id, liked_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $story_id]);

        $stmtUpdate = $pdo->prepare("UPDATE stories SET total_likes = total_likes + 1 WHERE story_id = ?");
        $stmtUpdate->execute([$story_id]);

        $action = 'liked';

        // 7. Trigger Notification (jika menyukai cerita orang lain)
        if ($author_id !== $user_id) {
            $actor_name = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Seseorang';
            $title = 'Cerita Disukai';
            $body = "{$actor_name} (@{$_SESSION['username']}) menyukai cerita Anda \"{$story_title}\".";
            $link_url = "Detstory.php?id=" . $story_id;

            $stmtNotif = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, body, ref_story_id, actor_user_id, link_url, created_at)
                VALUES (?, 'social', ?, ?, ?, ?, ?, NOW())
            ");
            $stmtNotif->execute([$author_id, $title, $body, $story_id, $user_id, $link_url]);
        }
    }

    $pdo->commit();

    // 8. Hitung total likes terbaru
    $stmtCount = $pdo->prepare("SELECT total_likes FROM stories WHERE story_id = ?");
    $stmtCount->execute([$story_id]);
    $total_likes = (int)$stmtCount->fetchColumn();

    echo json_encode([
        'success'     => true,
        'action'      => $action,
        'total_likes' => $total_likes,
        'message'     => $action === 'liked' ? 'Menyukai cerita.' : 'Membatalkan suka cerita.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status suka: ' . $e->getMessage()]);
}
?>
