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
$my_avatar      = isset($body['my_avatar'])      ? $body['my_avatar']            : null;
$contact_avatar = isset($body['contact_avatar']) ? $body['contact_avatar']       : null;
$bg_image       = isset($body['bg_image'])       ? $body['bg_image']             : null;

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
            SET theme = :theme, 
                contact_name = :contact_name, 
                sort_order = :sort_order,
                my_avatar = :my_avatar,
                contact_avatar = :contact_avatar,
                bg_image = :bg_image
            WHERE roomchat_id = :roomchat_id
        ");
        $stmt->execute([
            ':theme'          => $theme,
            ':contact_name'   => $contact_name,
            ':sort_order'     => $sort_order,
            ':my_avatar'      => $my_avatar,
            ':contact_avatar' => $contact_avatar,
            ':bg_image'       => $bg_image,
            ':roomchat_id'    => $roomchat_id,
        ]);
        echo json_encode(['success' => true, 'roomchat_id' => $roomchat_id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO roomchats (block_id, chapter_id, theme, contact_name, sort_order, my_avatar, contact_avatar, bg_image)
            VALUES (:block_id, :chapter_id, :theme, :contact_name, :sort_order, :my_avatar, :contact_avatar, :bg_image)
        ");
        $stmt->execute([
            ':block_id'       => $block_id,
            ':chapter_id'     => $chapter_id,
            ':theme'          => $theme,
            ':contact_name'   => $contact_name,
            ':sort_order'     => $sort_order,
            ':my_avatar'      => $my_avatar,
            ':contact_avatar' => $contact_avatar,
            ':bg_image'       => $bg_image,
        ]);
        echo json_encode(['success' => true, 'roomchat_id' => (int)$pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>