<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Helper function to decode and save base64 image
function saveBase64Image($base64Str, $subDir, $prefix) {
    if (empty($base64Str)) {
        return null;
    }
    // Check if it's actually base64
    if (preg_match('/^data:image\/(\w+);base64,(.*)$/is', $base64Str, $matches)) {
        $ext = strtolower($matches[1]);
        $data = base64_decode($matches[2]);
        if ($data === false) {
            return $base64Str; // Return original if decode failed
        }
        
        $uploadDir = __DIR__ . '/../Uploads/' . $subDir . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Map common extensions
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        
        $filename = uniqid($prefix . '_') . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $data) !== false) {
            return 'Uploads/' . $subDir . '/' . $filename;
        }
    }
    return $base64Str; // Return as-is if it's already a URL/path
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

// Convert base64 data to files
$my_avatar      = saveBase64Image($my_avatar, 'roomchats', 'avatar_me');
$contact_avatar = saveBase64Image($contact_avatar, 'roomchats', 'avatar_contact');
$bg_image       = saveBase64Image($bg_image, 'roomchats', 'bg_image');

if ($block_id <= 0 || $chapter_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

if (!in_array($theme, ['wa', 'im'])) $theme = 'wa';

try {
    $pdo = getDB();

    if ($roomchat_id > 0) {
        // Fetch old paths to delete them if updated
        $stmtFetch = $pdo->prepare("SELECT my_avatar, contact_avatar, bg_image FROM roomchats WHERE roomchat_id = ?");
        $stmtFetch->execute([$roomchat_id]);
        $oldRoomchat = $stmtFetch->fetch();

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

        // Clean up old files if they are replaced
        if ($oldRoomchat) {
            if ($oldRoomchat['my_avatar'] && $oldRoomchat['my_avatar'] !== $my_avatar && file_exists(__DIR__ . '/../' . $oldRoomchat['my_avatar'])) {
                @unlink(__DIR__ . '/../' . $oldRoomchat['my_avatar']);
            }
            if ($oldRoomchat['contact_avatar'] && $oldRoomchat['contact_avatar'] !== $contact_avatar && file_exists(__DIR__ . '/../' . $oldRoomchat['contact_avatar'])) {
                @unlink(__DIR__ . '/../' . $oldRoomchat['contact_avatar']);
            }
            if ($oldRoomchat['bg_image'] && $oldRoomchat['bg_image'] !== $bg_image && file_exists(__DIR__ . '/../' . $oldRoomchat['bg_image'])) {
                @unlink(__DIR__ . '/../' . $oldRoomchat['bg_image']);
            }
        }

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