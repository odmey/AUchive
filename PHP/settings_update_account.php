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

$name = trim($_POST['name'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE users SET name = ?, bio = ?, birth_date = ? WHERE user_id = ?');
    $stmt->execute([$name, $bio, $birth_date, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
