<?php
// ============================================================
// search_stories.php  –  Live search: judul atau tag
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

// ── 3. Query: cari di title ATAU tag_name, gabungkan hasilnya ─
//    DISTINCT story_id agar satu cerita tidak muncul dua kali
//    walau punya banyak tag yang cocok.
$sql = "
    SELECT DISTINCT
        s.story_id,
        s.title,
        s.cover,
        s.status
    FROM stories s
    WHERE s.title LIKE ?
      AND s.status = 'published'

    UNION

    SELECT DISTINCT
        s.story_id,
        s.title,
        s.cover,
        s.status
    FROM stories s
    INNER JOIN story_tags st ON st.story_id = s.story_id
    INNER JOIN tags        t  ON t.tag_id   = st.tag_id
    WHERE t.tag_name LIKE ?
      AND s.status = 'published'

    LIMIT 10
";

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pattern, $pattern]); // ← ganti jadi array biasa

    $results = $stmt->fetchAll(); // PDO::FETCH_ASSOC sudah di-set di database.php

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query gagal: ' . $e->getMessage()]);
}
