<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if (empty($name) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Name and username are required.']);
    exit;
}

$pdo = getDB();

// Cek username duplikat (kecuali milik sendiri)
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ?');
$stmt->execute([$username, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username is already used.']);
    exit;
}

// ── Helper: read uploaded file and push to cloud ───────────────
function saveUpload(string $fieldName): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) return null;

    // Ukuran maksimal 5 MB
    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) return null;

    // Read file and convert to base64 for cloud upload
    $fileContent = file_get_contents($_FILES[$fieldName]['tmp_name']);
    if ($fileContent === false) return null;

    $base64 = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
    return uploadToCloud($base64);
}

// ── Upload foto profil & banner ────────────────────────────────
$profilePicPath = saveUpload('profile_pic');
$profileBanPath = saveUpload('profile_ban');

// ── Build query dinamis (hanya update kolom yang berubah) ──────
$fields = ['name = ?', 'username = ?', 'bio = ?'];
$values = [$name, $username, $bio];

if ($profilePicPath !== null) {
    $fields[] = 'profile_pic = ?';
    $values[] = $profilePicPath;
}
if ($profileBanPath !== null) {
    $fields[] = 'profile_ban = ?';
    $values[] = $profileBanPath;
}

$values[] = $_SESSION['user_id'];

$sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute($values);

// ── Update session ─────────────────────────────────────────────
$_SESSION['username'] = $username;
$_SESSION['name'] = $name;

// ── Response ───────────────────────────────────────────────────
$response = [
    'success' => true,
    'message' => 'Profile successfully saved.',
    'name' => $name,
    'username' => $username,
];

if ($profilePicPath !== null)
    $response['profile_pic'] = $profilePicPath;
if ($profileBanPath !== null)
    $response['profile_ban'] = $profileBanPath;

echo json_encode($response);
