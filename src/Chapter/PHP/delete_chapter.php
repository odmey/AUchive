<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$chapter_id = isset($body['chapter_id']) ? (int)$body['chapter_id'] : 0;
$story_id   = isset($body['story_id'])   ? (int)$body['story_id']   : 0;

if ($chapter_id <= 0 || $story_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $pdo = getDB();

    // Hapus bubble dulu, baru chapter
    $pdo->prepare("DELETE FROM bubbles WHERE chapter_id = ?")->execute([$chapter_id]);
    $pdo->prepare("DELETE FROM chapters WHERE chapter_id = ? AND story_id = ?")->execute([$chapter_id, $story_id]);

    echo json_encode(['success' => true, 'message' => 'Chapter deleted successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
