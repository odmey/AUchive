<?php
// ============================================================
// admin_get_stories.php  –  Paginated story list for admin
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
    $pdo    = getDB();
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 15;
    $offset = ($page - 1) * $limit;

    $where  = "WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $where   .= " AND (s.title LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (in_array($status, ['published', 'draft'])) {
        $where   .= " AND s.status = ?";
        $params[] = $status;
    }

    $totalStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM stories s
        LEFT JOIN users u ON s.user_id = u.user_id
        $where
    ");
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetchColumn();

    $storiesStmt = $pdo->prepare("
        SELECT s.story_id, s.title, s.cover, s.status,
               s.published_at,
               IFNULL(s.total_views, 0) AS total_views,
               IFNULL(s.total_likes, 0) AS total_likes,
               IFNULL(u.username, 'Unknown')         AS author,
               u.user_id,
               IFNULL(g.genre_name, 'Uncategorized') AS category
        FROM stories s
        LEFT JOIN users  u ON s.user_id  = u.user_id
        LEFT JOIN genres g ON s.genre_id = g.genre_id
        $where
        ORDER BY s.published_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $storiesStmt->execute($params);
    $stories = $storiesStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'stories' => $stories,
        'total'   => $total,
        'page'    => $page,
        'limit'   => $limit,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

