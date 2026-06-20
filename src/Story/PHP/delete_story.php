<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$story_id = isset($body['story_id']) ? (int)$body['story_id'] : 0;

if ($story_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid story_id']);
    exit;
}

try {
    $pdo = getDB();

    // Pastikan story milik user yang login
    $stmt = $pdo->prepare("SELECT story_id FROM stories WHERE story_id = ? AND user_id = ?");
    $stmt->execute([$story_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Story is not found']);
        exit;
    }

    // Hapus semua data terkait (urutan penting)
    SET_FOREIGN_KEY_CHECKS:
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Hapus bubbles
    $pdo->prepare("
        DELETE b FROM bubbles b
        JOIN chapters c ON b.chapter_id = c.chapter_id
        WHERE c.story_id = ?
    ")->execute([$story_id]);

    // Hapus roomchats
    $pdo->prepare("
        DELETE r FROM roomchats r
        JOIN chapter_blocks cb ON r.block_id = cb.block_id
        JOIN chapters c ON cb.chapter_id = c.chapter_id
        WHERE c.story_id = ?
    ")->execute([$story_id]);

    // Hapus chapter_blocks
    $pdo->prepare("
        DELETE cb FROM chapter_blocks cb
        JOIN chapters c ON cb.chapter_id = c.chapter_id
        WHERE c.story_id = ?
    ")->execute([$story_id]);

    // Hapus chapters
    $pdo->prepare("DELETE FROM chapters WHERE story_id = ?")->execute([$story_id]);

    // Hapus story_tags
    $pdo->prepare("DELETE FROM story_tags WHERE story_id = ?")->execute([$story_id]);

    // Hapus story
    $pdo->prepare("DELETE FROM stories WHERE story_id = ?")->execute([$story_id]);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
