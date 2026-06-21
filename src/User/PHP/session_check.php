<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../Core/PHP/database.php';

    // Ambil profile_pic terbaru langsung dari DB (bukan cache session)
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT profile_pic FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row  = $stmt->fetch();

    // profile_pic di DB sudah menyimpan path lengkap, misal: "Uploads/avatars/avatar_1_xxx.jpg"
    // Kalau belum ada foto, pakai default
    $profilePic = (!empty($row['profile_pic']))
        ? $row['profile_pic']
        : 'Pic/PP kosongan.jpg';

    echo json_encode([
        'loggedIn'   => true,
        'user_id'    => $_SESSION['user_id'],
        'username'   => $_SESSION['username'] ?? '',
        'name'       => $_SESSION['name']     ?? '',
        'email'      => $_SESSION['email']    ?? '',
        'profilePic' => $profilePic,
        'role'       => $_SESSION['role']     ?? 'user',
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
exit;

