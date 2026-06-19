<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT name, bio, birth_date, email FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

