<?php
// ============================================================
// update_progress_status.php
// Updates story's progress status: ongoing | complete | hiatus
// Called via AJAX POST with JSON body: { story_id, progress_status }
// ============================================================
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

$body            = json_decode(file_get_contents('php://input'), true);
$story_id        = isset($body['story_id'])        ? (int)$body['story_id']        : 0;
$progress_status = isset($body['progress_status']) ? trim($body['progress_status']) : '';

if ($story_id <= 0 || !in_array($progress_status, ['ongoing', 'complete', 'hiatus'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        UPDATE stories
        SET progress_status = ?
        WHERE story_id = ? AND user_id = ?
    ");
    $stmt->execute([$progress_status, $story_id, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'progress_status' => $progress_status]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

