<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

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
    echo json_encode(['success' => false, 'message' => 'Invalid body']);
    exit;
}

$story_id      = isset($body['story_id'])      ? (int)$body['story_id']       : 0;
$chapter_id    = isset($body['chapter_id'])    ? (int)$body['chapter_id']     : 0;
$chapter_title = isset($body['chapter_title']) ? trim($body['chapter_title']) : '';
$chapter_text  = isset($body['chapter_text'])  ? trim($body['chapter_text'])  : '';
$status        = isset($body['status'])        ? trim($body['status'])        : 'draft';

if ($story_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Story ID is invalid']);
    exit;
}
if ($chapter_title === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chapter title cannot be empty']);
    exit;
}
if (!in_array($status, ['draft', 'published'])) {
    $status = 'draft';
}

try {
    $pdo = getDB();

    if ($chapter_id > 0) {
        // UPDATE chapter yang sudah ada
        $stmtCheck = $pdo->prepare("SELECT status, published_at FROM chapters WHERE chapter_id = ? AND story_id = ?");
        $stmtCheck->execute([$chapter_id, $story_id]);
        $oldChapter = $stmtCheck->fetch();
        $isBrandNewPublish = false;
        if ($oldChapter && $status === 'published' && $oldChapter['status'] !== 'published') {
            $isBrandNewPublish = true;
        }

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

        // Cek pakai $oldChapter (sudah diambil di atas), bukan rowCount()
        // karena rowCount() = 0 juga saat UPDATE berhasil tapi nilai tidak berubah
        if (!$oldChapter) {
            echo json_encode(['success' => false, 'message' => 'Chapter not found']);
            exit;
        }

    } else {
        // INSERT chapter baru
        $isBrandNewPublish = ($status === 'published');

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

        // Send notifications to library subscribers
        if ($isBrandNewPublish) {
            try {
                $tableExists = false;
                try {
                    $pdo->query("SELECT 1 FROM notifications LIMIT 1");
                    $tableExists = true;
                } catch (PDOException $e) {}

                if ($tableExists) {
                    $stmtStory = $pdo->prepare("SELECT user_id, title FROM stories WHERE story_id = ?");
                    $stmtStory->execute([$story_id]);
                    $storyInfo = $stmtStory->fetch();

                    if ($storyInfo) {
                        $author_id = (int)$storyInfo['user_id'];
                        $story_title = $storyInfo['title'];

                        $stmtSubscribers = $pdo->prepare("
                            SELECT l.user_id 
                            FROM library_stories ls
                            JOIN library l ON ls.library_id = l.library_id
                            WHERE ls.story_id = ? AND l.user_id != ?
                        ");
                        $stmtSubscribers->execute([$story_id, $author_id]);
                        $subscribers = $stmtSubscribers->fetchAll(PDO::FETCH_COLUMN);

                        if (!empty($subscribers)) {
                            $stmtNotif = $pdo->prepare("
                                INSERT INTO notifications (user_id, type, title, body, ref_story_id, actor_user_id, link_url, created_at)
                                VALUES (?, 'story', ?, ?, ?, ?, ?, NOW())
                            ");
                            foreach ($subscribers as $sub_id) {
                                $notifTitle = 'New chapter: ' . $story_title;
                                $notifBody = '"' . $chapter_title . '" is now available.';
                                $link_url = "Readingpage.php?story_id=" . $story_id . "&chapter_id=" . $chapter_id;

                                $stmtNotif->execute([
                                    $sub_id,
                                    $notifTitle,
                                    $notifBody,
                                    $story_id,
                                    $author_id,
                                    $link_url
                                ]);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Failed to send new chapter notifications: " . $e->getMessage());
            }
        }
    }

    echo json_encode([
        'success'    => true,
        'chapter_id' => $chapter_id,
        'status'     => $status,
        'message'    => $status === 'published' ? 'Chapter published successfully' : 'Chapter saved successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
