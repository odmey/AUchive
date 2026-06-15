<?php
// ============================================================
// follow_action.php
// Mengikuti (follow) / berhenti mengikuti (unfollow) seorang author
// Digunakan oleh: Detstory.php, profile_person.php
// ============================================================

session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

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
$following_id = 0;

if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $following_id = (int)($jsonData['following_id'] ?? 0);
    }
} else {
    $following_id = (int)($_POST['following_id'] ?? 0);
}

$follower_id = $_SESSION['user_id'];

if ($following_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'following_id wajib diisi.']);
    exit;
}

if ($following_id === $follower_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Anda tidak bisa mengikuti diri sendiri.']);
    exit;
}

$pdo = getDB();

// 3. Validasi Target User Exists
$stmt = $pdo->prepare("SELECT name, username FROM users WHERE user_id = ?");
$stmt->execute([$following_id]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User yang ingin diikuti tidak ditemukan.']);
    exit;
}

try {
    // 4. Cek apakah sudah follow
    $stmt = $pdo->prepare("
        SELECT follow_id FROM followers 
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$follower_id, $following_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 5. Unfollow
        $stmt = $pdo->prepare("
            DELETE FROM followers 
            WHERE follower_id = ? AND following_id = ?
        ");
        $stmt->execute([$follower_id, $following_id]);
        $action = 'unfollowed';
    } else {
        // 6. Follow
        $stmt = $pdo->prepare("
            INSERT INTO followers (follower_id, following_id, followed_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$follower_id, $following_id]);
        $action = 'followed';

        // 7. Trigger Notification
        $actor_name = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Seseorang';
        $title = 'Pengikut Baru';
        $body = "{$actor_name} (@{$_SESSION['username']}) mulai mengikuti Anda.";
        $link_url = "profile_person.php?id=" . $follower_id;

        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, body, actor_user_id, link_url, created_at)
            VALUES (?, 'social', ?, ?, ?, ?, NOW())
        ");
        $stmtNotif->execute([$following_id, $title, $body, $follower_id, $link_url]);
    }

    // 8. Hitung total follower terbaru
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
    $stmtCount->execute([$following_id]);
    $follower_count = (int)$stmtCount->fetchColumn();

    echo json_encode([
        'success'        => true,
        'action'         => $action,
        'follower_count' => $follower_count,
        'message'        => $action === 'followed' ? 'Berhasil mengikuti penulis.' : 'Berhasil berhenti mengikuti.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status ikuti: ' . $e->getMessage()]);
}
?>

