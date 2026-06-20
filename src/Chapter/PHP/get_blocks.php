<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json');

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($chapter_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT cb.block_id, cb.type, cb.content, cb.sort_order,
               r.roomchat_id, r.theme, r.contact_name, r.my_avatar, r.contact_avatar, r.bg_image
        FROM chapter_blocks cb
        LEFT JOIN roomchats r ON cb.block_id = r.block_id
        WHERE cb.chapter_id = ?
        ORDER BY cb.block_id ASC
    ");
    $stmt->execute([$chapter_id]);
    $blocks = $stmt->fetchAll();

    foreach ($blocks as &$block) {
        if ($block['type'] === 'roomchat' && $block['roomchat_id']) {
            $stmt2 = $pdo->prepare("
                SELECT bubble_id, bubble_text, contact_name, color, position, time_label, sort_order, sender_avatar, bubble_image
                FROM bubbles
                WHERE roomchat_id = ?
                ORDER BY sort_order ASC
            ");
            $stmt2->execute([$block['roomchat_id']]);
            $block['bubbles'] = $stmt2->fetchAll();
        }
    }

    echo json_encode($blocks);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
