<?php
// ============================================================
// admin_get_analytics.php  –  Analytics data for charts
// ============================================================
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDB();

    // Stories by genre
    $genreData = $pdo->query("
        SELECT g.genre_name, COUNT(s.story_id) AS count
        FROM genres g
        LEFT JOIN stories s ON s.genre_id = g.genre_id
        GROUP BY g.genre_id, g.genre_name
        ORDER BY count DESC
    ")->fetchAll();

    // Stories by status
    $statusData = $pdo->query("
        SELECT status, COUNT(*) AS count
        FROM stories
        GROUP BY status
    ")->fetchAll();

    // Top 5 authors by story count
    $topAuthors = $pdo->query("
        SELECT u.username, u.profile_pic,
               COUNT(s.story_id)              AS story_count,
               IFNULL(SUM(s.total_views), 0)  AS total_views,
               IFNULL(SUM(s.total_likes), 0)  AS total_likes
        FROM users u
        INNER JOIN stories s ON s.user_id = u.user_id
        WHERE u.role != 'admin'
        GROUP BY u.user_id, u.username, u.profile_pic
        ORDER BY story_count DESC, total_views DESC
        LIMIT 5
    ")->fetchAll();

    // Aggregates
    $agg = $pdo->query("
        SELECT IFNULL(SUM(total_views), 0) AS total_views,
               IFNULL(SUM(total_likes), 0) AS total_likes
        FROM stories
    ")->fetch();

    $totalComments = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $totalFollows  = (int)$pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();

    echo json_encode([
        'success'       => true,
        'genre_data'    => $genreData,
        'status_data'   => $statusData,
        'top_authors'   => $topAuthors,
        'total_views'   => (int)$agg['total_views'],
        'total_likes'   => (int)$agg['total_likes'],
        'total_comments'=> $totalComments,
        'total_follows' => $totalFollows,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

