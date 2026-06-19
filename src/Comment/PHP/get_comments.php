<?php
// ============================================================
// get_comments.php
// Mengambil daftar komentar reader pada suatu chapter
// Digunakan oleh: Readingpage.html (section Comments)
//
// Skema DB:
//   comments: comment_id, user_id, chapter_id,
//             comment_text, comment_like, created_at
// ============================================================

session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json');

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($chapter_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'chapter_id tidak valid.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT c.comment_id, c.comment_text, c.comment_like, c.created_at, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.chapter_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$chapter_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($comments);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil komentar: ' . $e->getMessage()]);
}
?>

