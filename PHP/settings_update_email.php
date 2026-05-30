<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$new_email = trim($_POST['new_email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required to update email']);
    exit;
}

try {
    $pdo = getDB();
    
    // Verify password first
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
    $stmt->execute([$new_email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email is already in use by another account']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE user_id = ?');
    $stmt->execute([$new_email, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Email updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
