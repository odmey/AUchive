<?php
// ============================================================
// save_progress.php
// Mencatat bab terakhir yang dibaca + persentase progress
// Digunakan oleh: Readingpage.html (auto-save saat ganti bab)
//
// Skema DB:
//   library_stories: library_id, story_id,
//                    last_read_chapter_id, progress_percent,
//                    is_complete, update_at
// ============================================================

session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json');

// ── Session Check ────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin tidak memiliki reading progress.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────
$story_id        = (int)($_POST['story_id']        ?? 0);
$chapter_id      = (int)($_POST['chapter_id']      ?? 0);
$progress_pct    = (float)($_POST['progress_pct']  ?? 0);   // 0.00 – 100.00

if ($story_id <= 0 || $chapter_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'story_id dan chapter_id wajib diisi.']);
    exit;
}

// Clamp ke 0–100
$progress_pct = max(0, min(100, $progress_pct));
$is_complete  = ($progress_pct >= 100) ? 1 : 0;

$user_id = $_SESSION['user_id'];
$pdo     = getDB();

// ── Validasi: chapter harus milik story ini ──────────────────
$stmt = $pdo->prepare("
    SELECT chapter_id FROM chapters
    WHERE chapter_id = ? AND story_id = ?
");
$stmt->execute([$chapter_id, $story_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Chapter tidak valid untuk cerita ini.']);
    exit;
}

// ── Ambil atau buat library untuk user ini ───────────────────
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

// ── Cek apakah cerita sudah ada di library_stories ───────────
$stmt = $pdo->prepare("
    SELECT library_id FROM library_stories
    WHERE library_id = ? AND story_id = ?
");
$stmt->execute([$library_id, $story_id]);
$existing = $stmt->fetch();

if ($existing) {
    // ── Update progress yang sudah ada ───────────────────────
    // Hanya update jika progress NAIK (jangan turunkan progress)
    $stmt = $pdo->prepare("
        UPDATE library_stories
        SET last_read_chapter_id = ?,
            progress_percent     = GREATEST(progress_percent, ?),
            is_complete          = IF(? >= 100, 1, is_complete),
            update_at            = NOW()
        WHERE library_id = ? AND story_id = ?
    ");
    $stmt->execute([$chapter_id, $progress_pct, $progress_pct, $library_id, $story_id]);
} else {
    // ── Insert baru (user langsung baca tanpa klik simpan) ───
    $stmt = $pdo->prepare("
        INSERT INTO library_stories
            (library_id, story_id, saved_at, last_read_chapter_id,
             progress_percent, is_complete, update_at)
        VALUES (?, ?, NOW(), ?, ?, ?, NOW())
    ");
    $stmt->execute([$library_id, $story_id, $chapter_id, $progress_pct, $is_complete]);
}

echo json_encode([
    'success'         => true,
    'message'         => 'Progress tersimpan.',
    'progress_percent'=> $progress_pct,
    'is_complete'     => (bool)$is_complete,
]);
