<?php
// ============================================================
// favorite_action.php
// Menandai cerita sebagai favorit (favorite) / membatalkan favorit
// Digunakan oleh: Detstory.php
// ============================================================

session_start();
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

// 1. Session Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin tidak dapat memfavoritkan cerita.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// 2. Parse Input
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$story_id = 0;

if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $story_id = (int)($jsonData['story_id'] ?? 0);
    }
} else {
    $story_id = (int)($_POST['story_id'] ?? 0);
}

$user_id = $_SESSION['user_id'];

if ($story_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'story_id tidak valid.']);
    exit;
}

$pdo = getDB();

// 3. Validasi Story
$stmt = $pdo->prepare("SELECT story_id FROM stories WHERE story_id = ?");
$stmt->execute([$story_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Cerita tidak ditemukan.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 4. Ambil atau buat library untuk user ini
    $stmt = $pdo->prepare("SELECT library_id FROM library WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $lib = $stmt->fetch();

    if (!$lib) {
        $stmt = $pdo->prepare("INSERT INTO library (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $library_id = (int)$pdo->lastInsertId();
    } else {
        $library_id = (int)$lib['library_id'];
    }

    // 5. Cek apakah cerita sudah ada di library_stories
    $stmt = $pdo->prepare("SELECT is_favorite, is_saved FROM library_stories WHERE library_id = ? AND story_id = ?");
    $stmt->execute([$library_id, $story_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Toggle is_favorite
        $new_favorite = (int)$existing['is_favorite'] === 1 ? 0 : 1;
        if ($new_favorite === 1) {
            // Favoriting: automatically saves the story too
            $stmt = $pdo->prepare("UPDATE library_stories SET is_favorite = 1, is_saved = 1, saved_at = NOW() WHERE library_id = ? AND story_id = ?");
            $stmt->execute([$library_id, $story_id]);
        } else {
            // Unfavoriting: 
            if ((int)$existing['is_saved'] === 0) {
                // If it is also not saved, we delete the row entirely
                $stmt = $pdo->prepare("DELETE FROM library_stories WHERE library_id = ? AND story_id = ?");
                $stmt->execute([$library_id, $story_id]);
            } else {
                // Keep the row but set is_favorite = 0
                $stmt = $pdo->prepare("UPDATE library_stories SET is_favorite = 0 WHERE library_id = ? AND story_id = ?");
                $stmt->execute([$library_id, $story_id]);
            }
        }
    } else {
        // Tambahkan ke library dan set is_favorite = 1, is_saved = 1
        $new_favorite = 1;
        $stmt = $pdo->prepare("INSERT INTO library_stories (library_id, story_id, saved_at, is_favorite, is_saved) VALUES (?, ?, NOW(), 1, 1)");
        $stmt->execute([$library_id, $story_id]);
    }

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'is_favorite' => $new_favorite === 1,
        'message'     => $new_favorite === 1 ? 'Cerita ditambahkan ke favorit.' : 'Cerita dihapus dari favorit.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status favorit: ' . $e->getMessage()]);
}
?>
