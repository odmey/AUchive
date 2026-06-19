<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Auth check — uncomment kalau login sudah stabil
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Belum login']);
//     exit;
// }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Body tidak valid']);
    exit;
}
$chapter_id  = isset($body['chapter_id'])  ? (int)$body['chapter_id']    : 0;
$roomchat_id = isset($body['roomchat_id']) ? (int)$body['roomchat_id']   : 0;
$message     = isset($body['message'])     ? trim($body['message'])       : '';
$sender_name = isset($body['sender_name']) ? trim($body['sender_name'])   : 'Unknown';
$position    = isset($body['position'])    ? trim($body['position'])      : 'left';
$color       = isset($body['color'])       ? trim($body['color'])         : '#005c4b';
$sort_order  = isset($body['sort_order'])  ? (int)$body['sort_order']     : 0;
$time_label  = isset($body['time_label'])  ? trim($body['time_label'])    : '';
$sender_avatar = isset($body['sender_avatar']) ? $body['sender_avatar']    : null;
$bubble_image  = isset($body['bubble_image'])  ? $body['bubble_image']     : null;

$sender_avatar = uploadToCloud($sender_avatar);
$bubble_image  = uploadToCloud($bubble_image);

if ($chapter_id <= 0 || $roomchat_id<=0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'chapter_id tidak valid roomchat_id tidak valid']);
    exit;
}
if ($message === '' && empty($bubble_image)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'message atau foto tidak boleh kosong']);
    exit;
}
if (!in_array($position, ['left', 'right', 'center'])) {
    $position = 'left';
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO bubbles
            (chapter_id, roomchat_id, bubble_text, contact_name, color, position, sort_order, time_label, sender_avatar, bubble_image)
        VALUES
            (:chapter_id, :roomchat_id, :bubble_text, :contact_name, :color, :position, :sort_order, :time_label, :sender_avatar, :bubble_image)
    ");
    $stmt->execute([
        ':chapter_id'     => $chapter_id,
        ':roomchat_id'    => $roomchat_id,
        ':bubble_text'    => $message,
        ':contact_name'   => $sender_name,
        ':color'          => $color,
        ':position'       => $position,
        ':sort_order'     => $sort_order,
        ':time_label'     => $time_label,
        ':sender_avatar'  => $sender_avatar,
        ':bubble_image'   => $bubble_image,
    ]);

    echo json_encode([
        'success'   => true,
        'bubble_id' => (int)$pdo->lastInsertId(),
        'message'   => 'Bubble berhasil disimpan'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
