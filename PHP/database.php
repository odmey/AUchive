<?php
// ==========================
// DATABASE CONFIG - PDO
// ==========================
define('DB_HOST', 'localhost');
define('DB_NAME', 'auchive1');
define('DB_USER', 'root');   // ganti sesuai MySQL kamu
define('DB_PASS', '');       // ganti sesuai MySQL kamu
define('DB_CHARSET', 'utf8mb4');

// ==========================
// CLOUDINARY STORAGE CONFIG
// ==========================
define('CLOUDINARY_CLOUD_NAME', 'dbptuppyp');
define('CLOUDINARY_UPLOAD_PRESET', 'auchive_preset');

/**
 * Upload a base64-encoded image to Cloudinary.
 * Returns the public CDN URL on success, or null on failure.
 * If the input is already a URL (not base64), returns it as-is.
 */
function uploadToCloud(?string $base64Str): ?string {
    if (empty($base64Str)) return null;

    // If it's already a URL (from a previous upload), return it as-is
    if (str_starts_with($base64Str, 'http://') || str_starts_with($base64Str, 'https://')) {
        return $base64Str;
    }
    // If it's an old local path (legacy), return it as-is
    if (str_starts_with($base64Str, 'Uploads/')) {
        return $base64Str;
    }

    // Cloudinary expects the full data URI (e.g., data:image/png;base64,iVBORw...)
    $imageData = $base64Str;
    if (!str_starts_with($imageData, 'data:image')) {
        // Fallback for raw base64 just in case
        $imageData = 'data:image/png;base64,' . $imageData; 
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => [
            'file' => $imageData,
            'upload_preset' => CLOUDINARY_UPLOAD_PRESET
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        // Log the error to debug if it fails
        file_put_contents(__DIR__ . '/cloud_error.log', date('Y-m-d H:i:s') . " - HTTP $httpCode - Response: " . var_export($response, true) . "\n", FILE_APPEND);
        return null;
    }

    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}

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