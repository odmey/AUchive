<?php
// ============================================================
// get_library.php
// Mengambil koleksi buku (library) milik user yang sedang login
// Digunakan oleh: Library.html / Library.php
//
// Skema DB yang digunakan:
//   library        : library_id, user_id  (1 baris per user)
//   library_stories: library_id, story_id, saved_at,
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

// Role di DB adalah enum('user','admin').
// Admin tidak memiliki library personal.
if ($_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin tidak memiliki library.']);
    exit;
}

// ── Query Parameter ──────────────────────────────────────────
// ?filter=all | latest | ongoing
$filter  = $_GET['filter'] ?? 'all';
$user_id = $_SESSION['user_id'];
$pdo     = getDB();

// ── Ambil library_id milik user ──────────────────────────────
$stmt = $pdo->prepare("SELECT library_id FROM library WHERE user_id = ?");
$stmt->execute([$user_id]);
$lib = $stmt->fetch();

if (!$lib) {
    // User belum pernah menyimpan cerita apapun
    echo json_encode([
        'success'          => true,
        'filter'           => $filter,
        'continue_reading' => [],
        'all_stories'      => [],
    ]);
    exit;
}

$library_id = $lib['library_id'];

// ── Base Query ───────────────────────────────────────────────
$sql = "
    SELECT
        s.story_id,
        s.title,
        s.cover,
        s.status            AS story_status,
        g.genre_name        AS genre,
        ls.saved_at,
        ls.progress_percent,
        ls.last_read_chapter_id,
        ls.is_complete,
        ls.update_at        AS last_read_at,
        ls.is_favorite,
        c.chapter_title     AS last_chapter_title
    FROM library_stories ls
    JOIN  stories  s ON s.story_id   = ls.story_id
    LEFT JOIN genres   g ON g.genre_id   = s.genre_id
    LEFT JOIN chapters c ON c.chapter_id = ls.last_read_chapter_id
    WHERE ls.library_id = :library_id AND ls.is_saved = 1
";

switch ($filter) {
    case 'latest':
        $sql .= " ORDER BY ls.update_at DESC";
        break;
    case 'ongoing':
        // Hanya tampilkan yang sedang dibaca (belum selesai, sudah ada progress)
        $sql .= " AND ls.is_complete = 0 AND ls.progress_percent > 0
                  ORDER BY ls.update_at DESC";
        break;
    default: // 'all'
        $sql .= " ORDER BY ls.saved_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute([':library_id' => $library_id]);
$all_stories = $stmt->fetchAll();

// ── Pisahkan: sedang dibaca vs semua ─────────────────────────
// "Lanjutkan membaca" = progress > 0 dan belum is_complete
$continue_reading = array_values(array_filter(
    $all_stories,
    fn($s) => $s['progress_percent'] > 0 && !$s['is_complete']
));

echo json_encode([
    'success'          => true,
    'filter'           => $filter,
    'continue_reading' => $continue_reading,
    'all_stories'      => $all_stories,
]);
