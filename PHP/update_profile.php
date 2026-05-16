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

// ── Helper: simpan file upload ─────────────────────────────────
function saveUpload(string $fieldName, string $subDir, string $prefix, int $userId): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
    finfo_close($finfo);

    $ext = array_search($mime, $allowed, true);
    if ($ext === false) {
        return null; // tipe file tidak didukung, lewati saja
    }

    // Ukuran maksimal 5 MB
    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $uploadDir = __DIR__ . '/../Uploads/' . $subDir . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Hapus avatar/banner lama milik user ini supaya tidak numpuk
    foreach (glob($uploadDir . $prefix . '_' . $userId . '_*') as $old) {
        @unlink($old);
    }

    $filename = $prefix . '_' . $userId . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadDir . $filename)) {
        return null;
    }

    return 'Uploads/' . $subDir . '/' . $filename;
}

// ── Upload foto profil & banner ────────────────────────────────
$profilePicPath = saveUpload('profile_pic', 'avatars', 'avatar', $_SESSION['user_id']);
$profileBanPath = saveUpload('profile_ban', 'banners', 'banner', $_SESSION['user_id']);

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
    'message' => 'Profil berhasil disimpan.',
    'name' => $name,
    'username' => $username,
];

if ($profilePicPath !== null)
    $response['profile_pic'] = $profilePicPath;
if ($profileBanPath !== null)
    $response['profile_ban'] = $profileBanPath;

echo json_encode($response);