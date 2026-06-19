<?php
// ============================================================
// admin_get_stats.php  –  Dashboard statistics for admin panel
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

    $totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role NOT IN ('admin')")->fetchColumn();
    $totalStories = (int)$pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    $pendingReview = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status = 'draft'")->fetchColumn();
    $bannedUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'banned'")->fetchColumn();

    // Reports (table may not exist yet)
    $totalReports = 0;
    try {
        $totalReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
    } catch (PDOException $e) {
        $totalReports = 0;
    }

    // Recent 10 stories
    $recentStories = $pdo->query("
        SELECT s.story_id, s.title, s.status, s.published_at,
               IFNULL(u.username, 'Unknown')      AS author,
               IFNULL(g.genre_name, 'Uncategorized') AS category
        FROM stories s
        LEFT JOIN users  u ON s.user_id  = u.user_id
        LEFT JOIN genres g ON s.genre_id = g.genre_id
        ORDER BY s.published_at DESC
        LIMIT 10
    ")->fetchAll();

    echo json_encode([
        'success' => true,
        'stats'   => [
            'total_users'   => $totalUsers,
            'total_stories' => $totalStories,
            'total_reports' => $totalReports,
            'pending_review'=> $pendingReview,
            'banned_users'  => $bannedUsers,
        ],
        'recent_stories' => $recentStories,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

