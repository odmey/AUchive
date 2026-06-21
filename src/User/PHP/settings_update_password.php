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

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match']);
    exit;
}

try {
    $pdo = getDB();

    if (!verifyCurrentPassword($pdo, (int)$_SESSION['user_id'], $current_password)) {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
        exit;
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

