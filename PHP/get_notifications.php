<?php
// ============================================================
// get_notifications.php
// Mengambil notifikasi untuk user yang sedang login
// Digunakan oleh: Notification.html
//
// Karena tabel `notifications` belum ada di skema,
// file ini MENSIMULASIKAN notifikasi dari data yang sudah ada:
//
//   Type 'story'  → chapter baru dari cerita yang ada di library user
//   Type 'social' → (placeholder, siap diisi saat tabel notif dibuat)
//
// ⚠️  Tambahkan tabel notifications ke DB agar fitur ini penuh:
//     Lihat blok SQL di bagian bawah file ini.
// ============================================================

session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json');

// ── Session Check ────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Endpoint ini untuk user biasa.']);
    exit;
}

// ── Query Params ─────────────────────────────────────────────
// ?type=all | story | social
// ?page=1&limit=20
$type   = $_GET['type']  ?? 'all';
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = max(1, min(50, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$user_id = $_SESSION['user_id'];
$pdo     = getDB();

// ── Cek apakah tabel notifications sudah ada ─────────────────
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM notifications LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    $tableExists = false;
}

// ════════════════════════════════════════════════════════════
// MODE A: Tabel notifications SUDAH ADA
// ════════════════════════════════════════════════════════════
if ($tableExists) {

    $sql = "
        SELECT
            n.notif_id,
            n.type,
            n.title,
            n.body,
            n.is_read,
            n.created_at,
            n.link_url,
            s.title     AS story_title,
            s.cover     AS story_cover,
            u.username  AS actor_username
        FROM notifications n
        LEFT JOIN stories s ON s.story_id  = n.ref_story_id
        LEFT JOIN users   u ON u.user_id   = n.actor_user_id
        WHERE n.user_id = :user_id
    ";

    if ($type === 'story')  $sql .= " AND n.type = 'story'";
    if ($type === 'social') $sql .= " AND n.type = 'social'";

    $sql .= " ORDER BY n.created_at DESC LIMIT :lim OFFSET :off";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':lim',     $limit,   PDO::PARAM_INT);
    $stmt->bindValue(':off',     $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();

    // Hitung total
    $cSql = "SELECT COUNT(*) FROM notifications WHERE user_id = ?";
    if ($type === 'story')  $cSql .= " AND type = 'story'";
    if ($type === 'social') $cSql .= " AND type = 'social'";
    $cStmt = $pdo->prepare($cSql);
    $cStmt->execute([$user_id]);
    $total = (int)$cStmt->fetchColumn();

    // Hitung unread
    $uStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $uStmt->execute([$user_id]);
    $unread_count = (int)$uStmt->fetchColumn();

    // Mark as read — semua notif yang baru diambil
    $unreadIds = array_column(
        array_filter($notifications, fn($n) => !$n['is_read']),
        'notif_id'
    );
    if (!empty($unreadIds)) {
        $ph   = implode(',', array_fill(0, count($unreadIds), '?'));
        $mStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notif_id IN ($ph)");
        $mStmt->execute($unreadIds);
    }

    echo json_encode([
        'success'       => true,
        'source'        => 'notifications_table',
        'type'          => $type,
        'page'          => $page,
        'total'         => $total,
        'unread_count'  => $unread_count,
        'notifications' => $notifications,
    ]);

// ════════════════════════════════════════════════════════════
// MODE B: Tabel notifications BELUM ADA
//         → Bangun notif dari chapter terbaru di library user
// ════════════════════════════════════════════════════════════
} else {

    // Ambil library_id user
    $stmt = $pdo->prepare("SELECT library_id FROM library WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $lib = $stmt->fetch();

    $notifications = [];

    if ($lib && ($type === 'all' || $type === 'story')) {
        $library_id = $lib['library_id'];

        // Chapter terbaru dari setiap cerita yang ada di library user
        $stmt = $pdo->prepare("
            SELECT
                c.chapter_id,
                c.chapter_title,
                c.created_at,
                s.story_id,
                s.title     AS story_title,
                s.cover     AS story_cover,
                ls.last_read_chapter_id
            FROM library_stories ls
            JOIN stories  s ON s.story_id  = ls.story_id
            JOIN chapters c ON c.story_id  = s.story_id
                           AND c.status    = 'published'
            WHERE ls.library_id = :lib_id
              AND c.chapter_id  > COALESCE(ls.last_read_chapter_id, 0)
            ORDER BY c.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':lib_id', $library_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim',    $limit,       PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset,      PDO::PARAM_INT);
        $stmt->execute();
        $newChapters = $stmt->fetchAll();

        foreach ($newChapters as $ch) {
            $notifications[] = [
                'notif_id'     => null,
                'type'         => 'story',
                'title'        => 'Chapter baru: ' . $ch['story_title'],
                'body'         => '"' . $ch['chapter_title'] . '" sudah tersedia.',
                'is_read'      => false,
                'created_at'   => $ch['created_at'],
                'link_url'     => 'Readingpage.html?story_id=' . $ch['story_id']
                                  . '&chapter_id=' . $ch['chapter_id'],
                'story_title'  => $ch['story_title'],
                'story_cover'  => $ch['story_cover'],
                'actor_username' => null,
            ];
        }
    }

    echo json_encode([
        'success'       => true,
        'source'        => 'derived_from_library',
        'note'          => 'Tabel notifications belum ada. Tambahkan SQL di bawah untuk fitur penuh.',
        'type'          => $type,
        'page'          => $page,
        'total'         => count($notifications),
        'unread_count'  => count($notifications),
        'notifications' => $notifications,
    ]);
}

/*
=================================================================
SQL untuk membuat tabel notifications (jalankan di phpMyAdmin):
=================================================================

CREATE TABLE `notifications` (
  `notif_id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11)      NOT NULL,
  `type`           ENUM('story','social') NOT NULL,
  `title`          VARCHAR(255) DEFAULT NULL,
  `body`           TEXT         DEFAULT NULL,
  `is_read`        TINYINT(1)   DEFAULT 0,
  `ref_story_id`   INT(11)      DEFAULT NULL,
  `actor_user_id`  INT(11)      DEFAULT NULL,
  `link_url`       VARCHAR(500) DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notif_id`),
  KEY `idx_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_story`
    FOREIGN KEY (`ref_story_id`) REFERENCES `stories` (`story_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

=================================================================
*/
