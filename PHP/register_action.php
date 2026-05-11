<?php
session_start();
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'message' => 'Sudah login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
    exit;
}

if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username minimal 3 karakter, hanya huruf/angka/underscore.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 8 karakter.']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar.']);
    exit;
}

$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username sudah dipakai.']);
    exit;
}

// password_hash() sebelum INSERT
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('INSERT INTO users (username, name, email, password) VALUES (?, ?, ?, ?)');
$stmt->execute([$username, $name, $email, $hash]);
$newUserId = $pdo->lastInsertId();

// Langsung set session setelah register
session_regenerate_id(true);
$_SESSION['user_id'] = $newUserId;
$_SESSION['username'] = $username;
$_SESSION['name'] = $name;
$_SESSION['email'] = $email;
$_SESSION['login_at'] = date('Y-m-d H:i:s');

echo json_encode([
    'success' => true,
    'message' => 'Akun berhasil dibuat!',
    'username' => $username,
    'name' => $name,
]);