<?php
// ============================================================
// admin_get_users.php  –  Paginated user list for admin panel
// ============================================================
session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo    = getDB();
    $search = trim($_GET['search'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 15;
    $offset = ($page - 1) * $limit;

    $where  = "WHERE role != 'admin'";
    $params = [];

    if ($search !== '') {
        $where   .= " AND (username LIKE ? OR name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetchColumn();

    $usersStmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.name, u.email, u.profile_pic, u.role,
               (SELECT COUNT(*) FROM stories s WHERE s.user_id = u.user_id) AS story_count
        FROM users u
        $where
        ORDER BY u.user_id DESC
        LIMIT $limit OFFSET $offset
    ");
    $usersStmt->execute($params);
    $users = $usersStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'users'   => $users,
        'total'   => $total,
        'page'    => $page,
        'limit'   => $limit,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
