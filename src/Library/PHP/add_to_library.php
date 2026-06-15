<?php
// ============================================================
// add_to_library.php
// Menambah / menghapus cerita dari library user
// Digunakan oleh: Library.html, Readingpage.html
//
// Skema DB:
//   library        : library_id, user_id  (1 baris per user, auto-dibuat)
//   library_stories: library_id, story_id, saved_at, ...
// ============================================================

session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json');

// ── Session Check ────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin tidak dapat menyimpan cerita ke library.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────
// action: 'add' | 'remove'
$story_id = 0;
$action   = 'add';

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $story_id = (int)($jsonData['story_id'] ?? 0);
        $action   = trim($jsonData['action'] ?? 'add');
    }
} else {
    $story_id = (int)($_POST['story_id'] ?? 0);
    $action   = trim($_POST['action'] ?? 'add');
}

if ($story_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'story_id tidak valid.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo     = getDB();

// ── Validasi: story harus ada ────────────────────────────────
$stmt = $pdo->prepare("SELECT story_id FROM stories WHERE story_id = ?");
$stmt->execute([$story_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Cerita tidak ditemukan.']);
    exit;
}

// ── Ambil atau buat library untuk user ini ───────────────────
// Tabel library hanya menyimpan 1 baris per user (UNIQUE user_id)
$stmt = $pdo->prepare("SELECT library_id FROM library WHERE user_id = ?");
$stmt->execute([$user_id]);
$lib = $stmt->fetch();

if (!$lib) {
    // Buat library baru untuk user ini
    $stmt = $pdo->prepare("INSERT INTO library (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $library_id = (int)$pdo->lastInsertId();
} else {
    $library_id = (int)$lib['library_id'];
}

// ── Cek apakah cerita sudah ada di library_stories ───────────
$stmt = $pdo->prepare("
    SELECT library_id, is_saved, is_favorite FROM library_stories
    WHERE library_id = ? AND story_id = ?
");
$stmt->execute([$library_id, $story_id]);
$existing = $stmt->fetch();

switch ($action) {

    // ── Tambah ke library ─────────────────────────────────────
    case 'add':
        if ($existing) {
            if ((int)$existing['is_saved'] === 1) {
                echo json_encode([
                    'success'    => true,
                    'message'    => 'Cerita sudah ada di library.',
                    'in_library' => true,
                ]);
                exit;
            } else {
                // Baris ada tapi status is_saved = 0, update ke 1
                $stmt = $pdo->prepare("
                    UPDATE library_stories
                    SET is_saved = 1, saved_at = NOW()
                    WHERE library_id = ? AND story_id = ?
                ");
                $stmt->execute([$library_id, $story_id]);
            }
        } else {
            // Baris belum ada sama sekali
            $stmt = $pdo->prepare("
                INSERT INTO library_stories (library_id, story_id, saved_at, is_saved)
                VALUES (?, ?, NOW(), 1)
            ");
            $stmt->execute([$library_id, $story_id]);
        }

        echo json_encode([
            'success'    => true,
            'message'    => 'Cerita berhasil ditambahkan ke library.',
            'in_library' => true,
        ]);
        break;

    // ── Hapus dari library ────────────────────────────────────
    case 'remove':
        // Dianggap "tidak ada di library" jika baris tidak ada ATAU is_saved = 0
        if (!$existing || (int)$existing['is_saved'] === 0) {
            echo json_encode([
                'success'    => false,
                'message'    => 'Cerita tidak ada di library.',
                'in_library' => false,
            ]);
            exit;
        }

        if ((int)$existing['is_favorite'] === 1) {
            // Jika masih difavoritkan, jangan hapus baris. Cukup set is_saved = 0
            $stmt = $pdo->prepare("
                UPDATE library_stories
                SET is_saved = 0
                WHERE library_id = ? AND story_id = ?
            ");
            $stmt->execute([$library_id, $story_id]);
        } else {
            // Jika tidak difavoritkan, hapus baris dari database
            $stmt = $pdo->prepare("
                DELETE FROM library_stories
                WHERE library_id = ? AND story_id = ?
            ");
            $stmt->execute([$library_id, $story_id]);
        }

        echo json_encode([
            'success'    => true,
            'message'    => 'Cerita dihapus dari library.',
            'in_library' => false,
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenal. Gunakan: add | remove']);
        break;
}

