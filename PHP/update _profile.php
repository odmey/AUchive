<?php
session_start();
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if (empty($name) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Nama dan username wajib diisi.']);
    exit;
}

$pdo = getDB();

// Cek username duplikat (kecuali milik sendiri)
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ?');
$stmt->execute([$username, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username sudah dipakai.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE users SET name = ?, username = ?, bio = ? WHERE user_id = ?');
$stmt->execute([$name, $username, $bio, $_SESSION['user_id']]);

// Update session
$_SESSION['username'] = $username;
$_SESSION['name'] = $name;

echo json_encode(['success' => true, 'message' => 'Profil berhasil disimpan.']);