<?php
// ============================================================
// admin_action.php  –  Centralised admin action handler
// ============================================================
session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($body['action'] ?? '');
$pdo    = getDB();

// Auto-create system_settings table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_key`   varchar(100) NOT NULL,
        `setting_value` text         DEFAULT NULL,
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Insert defaults if not present
$defaults = [
    'site_name'      => 'AUchive Fanfiction Platform',
    'system_warning' => '',
    'server_mode'    => 'online',
    'engine_version' => 'v1.4.0-production'
];
foreach ($defaults as $k => $v) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
    $check->execute([$k]);
    if ((int)$check->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$k, $v]);
    }
}

try {
    switch ($action) {

        // ── ADMIN REGISTRATION ───────────────────────────
        case 'create_admin':
            $username = trim($body['username'] ?? '');
            $name     = trim($body['name'] ?? '');
            $email    = trim($body['email'] ?? '');
            $password = $body['password'] ?? '';

            if (empty($username) || empty($name) || empty($email) || empty($password)) {
                throw new Exception('All fields are required.');
            }
            if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                throw new Exception('Username must be at least 3 alphanumeric characters/underscores.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters.');
            }

            // Check if username or email already exists
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? OR username = ?');
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                throw new Exception('Email or username is already registered.');
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$username, $name, $email, $hash, 'admin']);

            echo json_encode(['success' => true, 'message' => 'New admin account created successfully!']);
            break;

        // ── USER ACTIONS ─────────────────────────────────
        case 'ban_user':
            $uid = (int)($body['user_id'] ?? 0);
            if ($uid <= 0) throw new Exception('Invalid user ID');
            $stmt = $pdo->prepare("UPDATE users SET role = 'banned' WHERE user_id = ? AND role != 'admin'");
            $stmt->execute([$uid]);
            if ($stmt->rowCount() === 0) throw new Exception('User not found or already an admin');
            echo json_encode(['success' => true, 'message' => 'User has been banned successfully']);
            break;

        case 'unban_user':
            $uid = (int)($body['user_id'] ?? 0);
            if ($uid <= 0) throw new Exception('Invalid user ID');
            $pdo->prepare("UPDATE users SET role = 'user' WHERE user_id = ?")->execute([$uid]);
            echo json_encode(['success' => true, 'message' => 'User has been unbanned successfully']);
            break;

        case 'delete_user':
            $uid = (int)($body['user_id'] ?? 0);
            if ($uid <= 0) throw new Exception('Invalid user ID');
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ? OR actor_user_id = ?")->execute([$uid, $uid]);
            $pdo->prepare("DELETE FROM followers   WHERE follower_id = ? OR following_id = ?")->execute([$uid, $uid]);
            $pdo->prepare("DELETE FROM library      WHERE user_id = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM comments     WHERE user_id = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM users        WHERE user_id = ? AND role != 'admin'")->execute([$uid]);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(['success' => true, 'message' => 'User account has been permanently deleted']);
            break;

        // ── STORY ACTIONS ────────────────────────────────
        case 'approve_story':
            $sid = (int)($body['story_id'] ?? 0);
            if ($sid <= 0) throw new Exception('Invalid story ID');
            $pdo->prepare("UPDATE stories SET status = 'published', published_at = NOW() WHERE story_id = ?")->execute([$sid]);
            echo json_encode(['success' => true, 'message' => 'Story has been published successfully']);
            break;

        case 'reject_story':
            $sid = (int)($body['story_id'] ?? 0);
            if ($sid <= 0) throw new Exception('Invalid story ID');
            $pdo->prepare("UPDATE stories SET status = 'draft', published_at = NULL WHERE story_id = ?")->execute([$sid]);
            echo json_encode(['success' => true, 'message' => 'Story has been set back to draft']);
            break;

        case 'delete_story':
            $sid = (int)($body['story_id'] ?? 0);
            if ($sid <= 0) throw new Exception('Invalid story ID');
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            // Bubbles
            $pdo->prepare("
                DELETE b FROM bubbles b
                JOIN chapters c ON b.chapter_id = c.chapter_id
                WHERE c.story_id = ?
            ")->execute([$sid]);
            // Roomchats
            $pdo->prepare("
                DELETE r FROM roomchats r
                JOIN chapter_blocks cb ON r.block_id   = cb.block_id
                JOIN chapters       c  ON cb.chapter_id = c.chapter_id
                WHERE c.story_id = ?
            ")->execute([$sid]);
            // Chapter blocks
            $pdo->prepare("
                DELETE cb FROM chapter_blocks cb
                JOIN chapters c ON cb.chapter_id = c.chapter_id
                WHERE c.story_id = ?
            ")->execute([$sid]);
            // Comments
            $pdo->prepare("
                DELETE cm FROM comments cm
                JOIN chapters c ON cm.chapter_id = c.chapter_id
                WHERE c.story_id = ?
            ")->execute([$sid]);
            $pdo->prepare("DELETE FROM chapters       WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM story_tags     WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM library_stories WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM notifications  WHERE ref_story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM stories        WHERE story_id = ?")->execute([$sid]);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(['success' => true, 'message' => 'Story has been permanently deleted']);
            break;

        // ── REPORT ACTIONS ───────────────────────────────
        case 'resolve_report':
            $rid = (int)($body['report_id'] ?? 0);
            if ($rid <= 0) throw new Exception('Invalid report ID');
            $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE report_id = ?")->execute([$rid]);
            echo json_encode(['success' => true, 'message' => 'Report has been marked as resolved']);
            break;

        case 'dismiss_report':
            $rid = (int)($body['report_id'] ?? 0);
            if ($rid <= 0) throw new Exception('Invalid report ID');
            $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE report_id = ?")->execute([$rid]);
            echo json_encode(['success' => true, 'message' => 'Report has been dismissed']);
            break;

        // ── SYSTEM SETTINGS ACTIONS ──────────────────────
        case 'update_system_warning':
            $warning = trim($body['warning'] ?? '');
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'system_warning'")->execute([$warning]);
            echo json_encode(['success' => true, 'message' => $warning ? 'System warning published successfully!' : 'System warning cleared.']);
            break;

        case 'update_system_settings':
            $allowed = ['site_name', 'engine_version', 'server_mode'];
            foreach ($allowed as $key) {
                if (isset($body[$key])) {
                    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([trim($body[$key]), $key]);
                }
            }
            echo json_encode(['success' => true, 'message' => 'System settings updated successfully!']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
    }

} catch (Exception $e) {
    // Re-enable FK checks on any error
    try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch (Exception $_) {}
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
