<?php
// ============================================================
// search_stories.php  –  Live search: judul, tag, atau user
// Dipanggil via: PHP/search_stories.php?q=<keyword>
// ============================================================
require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

// ── 1. Ambil & validasi keyword ──────────────────────────────
$keyword = trim($_GET['q'] ?? '');

if (mb_strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

// ── 2. Siapkan pola LIKE ─────────────────────────────────────
$pattern = '%' . $keyword . '%';

try {
    $pdo  = getDB();
    $results = [];

    // ── 3. Query Users ──────────────────────────────────────────
    $sqlUsers = "
        SELECT 
            user_id,
            username,
            name,
            profile_pic,
            'user' as type
        FROM users
        WHERE username LIKE ? OR name LIKE ?
        LIMIT 5
    ";
    $stmtUsers = $pdo->prepare($sqlUsers);
    $stmtUsers->execute([$pattern, $pattern]);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // ── 4. Query Stories ────────────────────────────────────────
    $sqlStories = "
        SELECT DISTINCT
            s.story_id,
            s.title,
            s.cover,
            s.status,
            'story' as type
        FROM stories s
        LEFT JOIN story_tags st ON st.story_id = s.story_id
        LEFT JOIN tags t ON t.tag_id = st.tag_id
        WHERE (s.title LIKE ? OR t.tag_name LIKE ?)
          AND s.status = 'published'
        LIMIT 10
    ";
    $stmtStories = $pdo->prepare($sqlStories);
    $stmtStories->execute([$pattern, $pattern]);
    $stories = $stmtStories->fetchAll(PDO::FETCH_ASSOC);

    // Combine results
    $results = array_merge($users, $stories);

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query gagal: ' . $e->getMessage()]);
}
