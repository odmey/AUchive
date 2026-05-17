<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($chapter_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT bubble_id, bubble_text, contact_name, color, position, sort_order, time_label
        FROM bubbles
        WHERE chapter_id = ?
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$chapter_id]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    echo json_encode([]);
}
?>