<?php
// ==============================================================
// user_helpers.php — Fungsi-fungsi pembantu untuk endpoint User
// Require file ini setelah database.php sudah di-require.
// ==============================================================

/**
 * Verifikasi password user saat ini berdasarkan user_id dari session.
 *
 * @param PDO    $pdo       Koneksi database (dari getDB())
 * @param int    $userId    ID user yang sedang login ($_SESSION['user_id'])
 * @param string $password  Password plain-text yang ingin diverifikasi
 * @return bool  true  → password cocok
 *               false → user tidak ditemukan atau password salah
 */
function verifyCurrentPassword(PDO $pdo, int $userId, string $password): bool
{
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    return $user && password_verify($password, $user['password']);
}
