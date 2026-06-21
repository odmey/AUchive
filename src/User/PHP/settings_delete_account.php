<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
require_once __DIR__ . '/../../Core/PHP/user_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required to delete account']);
    exit;
}

try {
    $pdo = getDB();

    // Verify password first
    if (!verifyCurrentPassword($pdo, (int)$_SESSION['user_id'], $password)) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

