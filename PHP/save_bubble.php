<?php
session_start();
require_once 'database.php';
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

// Helper: decode base64 image and save to disk
function saveBase64Image($base64Str, $subDir, $prefix) {
    if (empty($base64Str)) return null;
    if (preg_match('/^data:image\/(\w+);base64,(.*)$/is', $base64Str, $matches)) {
        $ext = strtolower($matches[1]);
        $data = base64_decode($matches[2]);
        if ($data === false) return $base64Str;
        $uploadDir = __DIR__ . '/../Uploads/' . $subDir . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if ($ext === 'jpeg') $ext = 'jpg';
        $filename = uniqid($prefix . '_') . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, $data) !== false) {
            return 'Uploads/' . $subDir . '/' . $filename;
        }
    }
    return $base64Str;
}

$sender_avatar = saveBase64Image($sender_avatar, 'roomchats', 'bubble_av');

if ($chapter_id <= 0 || $roomchat_id<=0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'chapter_id tidak valid roomchat_id tidak valid']);
    exit;
}
if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'message tidak boleh kosong']);
    exit;
}
if (!in_array($position, ['left', 'right', 'center'])) {
    $position = 'left';
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO bubbles
            (chapter_id, roomchat_id, bubble_text, contact_name, color, position, sort_order, time_label, sender_avatar)
        VALUES
            (:chapter_id, :roomchat_id, :bubble_text, :contact_name, :color, :position, :sort_order, :time_label, :sender_avatar)
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