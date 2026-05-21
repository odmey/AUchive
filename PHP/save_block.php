<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true);
$chapter_id = isset($body['chapter_id']) ? (int)$body['chapter_id'] : 0;
$block_id   = isset($body['block_id'])   ? (int)$body['block_id']   : 0;
$type       = isset($body['type'])       ? trim($body['type'])       : 'narration';
$content    = isset($body['content'])    ? trim($body['content'])    : '';
$sort_order = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

if ($chapter_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'chapter_id tidak valid']);
    exit;
}

if (!in_array($type, ['narration', 'roomchat'])) {
    $type = 'narration';
}

try {
    $pdo = getDB();

    if ($block_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE chapter_blocks
            SET content = :content, sort_order = :sort_order
            WHERE block_id = :block_id AND chapter_id = :chapter_id
        ");
        $stmt->execute([
            ':content'    => $content,
            ':sort_order' => $sort_order,
            ':block_id'   => $block_id,
            ':chapter_id' => $chapter_id,
        ]);
        echo json_encode(['success' => true, 'block_id' => $block_id, 'action' => 'updated']);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO chapter_blocks (chapter_id, type, content, sort_order)
            VALUES (:chapter_id, :type, :content, :sort_order)
        ");
        $stmt->execute([
            ':chapter_id' => $chapter_id,
            ':type'       => $type,
            ':content'    => $content,
            ':sort_order' => $sort_order,
        ]);
        echo json_encode(['success' => true, 'block_id' => (int)$pdo->lastInsertId(), 'action' => 'created']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>