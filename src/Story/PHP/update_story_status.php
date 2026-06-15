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
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$story_id = isset($body['story_id']) ? (int)$body['story_id'] : 0;
$status   = isset($body['status'])   ? trim($body['status'])   : '';

if ($story_id <= 0 || !in_array($status, ['published', 'draft'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    $pdo = getDB();

    if ($status === 'published') {
        $stmt = $pdo->prepare("
            UPDATE stories
            SET status = 'published', published_at = NOW()
            WHERE story_id = ? AND user_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE stories
            SET status = 'draft', published_at = NULL
            WHERE story_id = ? AND user_id = ?
        ");
    }

    $stmt->execute([$story_id, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

