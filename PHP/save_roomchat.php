<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body         = json_decode(file_get_contents('php://input'), true);
$block_id     = isset($body['block_id'])     ? (int)$body['block_id']        : 0;
$chapter_id   = isset($body['chapter_id'])   ? (int)$body['chapter_id']      : 0;
$roomchat_id  = isset($body['roomchat_id'])  ? (int)$body['roomchat_id']     : 0;
$theme        = isset($body['theme'])        ? trim($body['theme'])           : 'wa';
$contact_name = isset($body['contact_name']) ? trim($body['contact_name'])   : 'Contact';
$sort_order   = isset($body['sort_order'])   ? (int)$body['sort_order']      : 0;

if ($block_id <= 0 || $chapter_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

if (!in_array($theme, ['wa', 'im'])) $theme = 'wa';

try {
    $pdo = getDB();

    if ($roomchat_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE roomchats
            SET theme = :theme, contact_name = :contact_name, sort_order = :sort_order
            WHERE roomchat_id = :roomchat_id
        ");
        $stmt->execute([
            ':theme'        => $theme,
            ':contact_name' => $contact_name,
            ':sort_order'   => $sort_order,
            ':roomchat_id'  => $roomchat_id,
        ]);
        echo json_encode(['success' => true, 'roomchat_id' => $roomchat_id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO roomchats (block_id, chapter_id, theme, contact_name, sort_order)
            VALUES (:block_id, :chapter_id, :theme, :contact_name, :sort_order)
        ");
        $stmt->execute([
            ':block_id'     => $block_id,
            ':chapter_id'   => $chapter_id,
            ':theme'        => $theme,
            ':contact_name' => $contact_name,
            ':sort_order'   => $sort_order,
        ]);
        echo json_encode(['success' => true, 'roomchat_id' => (int)$pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>