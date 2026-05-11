<?php
// ==========================
// DATABASE CONFIG - PDO
// ==========================
define('DB_HOST', 'localhost');
define('DB_NAME', 'auchive');
define('DB_USER', 'root');   // ganti sesuai MySQL kamu
define('DB_PASS', '');       // ganti sesuai MySQL kamu
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // error langsung throw exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // hasil query langsung jadi array
            PDO::ATTR_EMULATE_PREPARES   => false,                   // pakai prepared statement asli
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    return $pdo;
}
?>