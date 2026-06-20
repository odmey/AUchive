<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

header('Content-Type: application/json');

// Kalau sudah login
if (isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'message' => 'Already logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$loginInput = trim($_POST['login_input'] ?? $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// ── Validasi ────────────────────────────────────────────────────
if (empty($loginInput) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username/Email and password are required.']);
    exit;
}

// ── Cek ke database ─────────────────────────────────────────────
$pdo = getDB();
$stmt = $pdo->prepare('SELECT user_id, username, name, email, password, profile_pic, role FROM users WHERE email = ? OR username = ?');
$stmt->execute([$loginInput, $loginInput]);
$user = $stmt->fetch();

// ── password_verify() ────────────────────────────────────────────
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect username/email or password.']);
    exit;
}

// ── Cek banned ───────────────────────────────────────────────────
if (($user['role'] ?? '') === 'banned') {
    echo json_encode(['success' => false, 'message' => 'Your account has been deactivated by the admin. Contact support if you have any questions.']);
    exit;
}


// ── Set session ──────────────────────────────────────────────────
session_regenerate_id(true);

$_SESSION['user_id']  = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name']     = $user['name'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'];
$_SESSION['login_at'] = date('Y-m-d H:i:s');

$profilePic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'Pic/profileicon.jpg';

echo json_encode([
    'success'    => true,
    'message'    => 'Login successful!',
    'username'   => $user['username'],
    'name'       => $user['name'],
    'profilePic' => $profilePic,
    'role'       => $user['role'],
]);
