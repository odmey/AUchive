<?php
// ============================================================
// admin_get_reports.php  –  Reports list (auto-creates table)
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



    $status = in_array($_GET['status'] ?? '', ['pending', 'resolved', 'dismissed'])
                ? $_GET['status'] : 'pending';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 15;
    $offset = ($page - 1) * $limit;

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE status = ?");
    $totalStmt->execute([$status]);
    $total = (int)$totalStmt->fetchColumn();

    $reportsStmt = $pdo->prepare("
        SELECT r.report_id, r.reason, r.description, r.status, r.created_at,
               r.reported_user_id, r.reported_story_id,
               IFNULL(reporter.username, 'Unknown')     AS reporter_name,
               IFNULL(rep_user.username, '-')           AS reported_user_name,
               IFNULL(s.title, '-')                     AS reported_story_title
        FROM reports r
        LEFT JOIN users  reporter ON r.reporter_id       = reporter.user_id
        LEFT JOIN users  rep_user ON r.reported_user_id  = rep_user.user_id
        LEFT JOIN stories s       ON r.reported_story_id = s.story_id
        WHERE r.status = ?
        ORDER BY r.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $reportsStmt->execute([$status]);
    $reports = $reportsStmt->fetchAll();

    // Counts for each tab
    $counts = [];
    foreach (['pending', 'resolved', 'dismissed'] as $st) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE status = ?");
        $s->execute([$st]);
        $counts[$st] = (int)$s->fetchColumn();
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'total'   => $total,
        'page'    => $page,
        'limit'   => $limit,
        'counts'  => $counts,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

