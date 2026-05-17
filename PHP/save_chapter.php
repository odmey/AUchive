<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Auth check — uncomment kalau login sudah stabil
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Belum login']);
//     exit;
// }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Body tidak valid']);
    exit;
}

$story_id      = isset($body['story_id'])      ? (int)$body['story_id']       : 0;
$chapter_id    = isset($body['chapter_id'])    ? (int)$body['chapter_id']     : 0;
$chapter_title = isset($body['chapter_title']) ? trim($body['chapter_title']) : '';
$chapter_text  = isset($body['chapter_text'])  ? trim($body['chapter_text'])  : '';
$status        = isset($body['status'])        ? trim($body['status'])        : 'draft';

if ($story_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'story_id tidak valid']);
    exit;
}
if ($chapter_title === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Judul bab tidak boleh kosong']);
    exit;
}
if (!in_array($status, ['draft', 'published'])) {
    $status = 'draft';
}

try {
    $pdo = getDB();

    if ($chapter_id > 0) {
        // UPDATE chapter yang sudah ada
        $stmt = $pdo->prepare("
            UPDATE chapters
            SET chapter_title = :title,
                chapter_text  = :text,
                status        = :status
            WHERE chapter_id = :chapter_id
              AND story_id   = :story_id
        ");
        $stmt->execute([
            ':title'      => $chapter_title,
            ':text'       => $chapter_text,
            ':status'     => $status,
            ':chapter_id' => $chapter_id,
            ':story_id'   => $story_id,
        ]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Chapter tidak ditemukan atau story_id tidak cocok']);
            exit;
        }

    } else {
        // INSERT chapter baru
        $stmt = $pdo->prepare("
            INSERT INTO chapters (story_id, chapter_title, chapter_text, status)
            VALUES (:story_id, :title, :text, :status)
        ");
        $stmt->execute([
            ':story_id' => $story_id,
            ':title'    => $chapter_title,
            ':text'     => $chapter_text,
            ':status'   => $status,
        ]);
        $chapter_id = (int)$pdo->lastInsertId();
    }

    // Set published_at kalau status published
    if ($status === 'published') {
        $stmt = $pdo->prepare("
            UPDATE chapters
            SET published_at = NOW()
            WHERE chapter_id = :chapter_id
        ");
        $stmt->execute([':chapter_id' => $chapter_id]);
    }

    echo json_encode([
        'success'    => true,
        'chapter_id' => $chapter_id,
        'status'     => $status,
        'message'    => $status === 'published' ? 'Chapter berhasil dipublish' : 'Chapter berhasil disimpan'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>