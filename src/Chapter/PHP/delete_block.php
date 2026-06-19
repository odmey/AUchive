<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$block_id = isset($body['block_id']) ? (int)$body['block_id'] : 0;

if ($block_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'block_id tidak valid']);
    exit;
}

try {
    $pdo = getDB();
    $pdo->prepare("DELETE FROM chapter_blocks WHERE block_id = ?")->execute([$block_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
