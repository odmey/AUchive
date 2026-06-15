<?php
// ============================================================
// report_action.php
// Mengirimkan laporan (report) user atau story ke database
// Digunakan oleh: Detstory.php, profile_person.php
// ============================================================

session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Session Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu untuk mengirimkan laporan.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// 2. Parse Input
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$reported_user_id = null;
$reported_story_id = null;
$reason = 'violation';
$description = '';

if (stripos($contentType, 'application/json') !== false) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $reported_user_id = isset($jsonData['reported_user_id']) && (int)$jsonData['reported_user_id'] > 0 ? (int)$jsonData['reported_user_id'] : null;
        $reported_story_id = isset($jsonData['reported_story_id']) && (int)$jsonData['reported_story_id'] > 0 ? (int)$jsonData['reported_story_id'] : null;
        $reason = trim($jsonData['reason'] ?? 'violation');
        $description = trim($jsonData['description'] ?? '');
    }
} else {
    $reported_user_id = isset($_POST['reported_user_id']) && (int)$_POST['reported_user_id'] > 0 ? (int)$_POST['reported_user_id'] : null;
    $reported_story_id = isset($_POST['reported_story_id']) && (int)$_POST['reported_story_id'] > 0 ? (int)$_POST['reported_story_id'] : null;
    $reason = trim($_POST['reason'] ?? 'violation');
    $description = trim($_POST['description'] ?? '');
}

$reporter_id = $_SESSION['user_id'];

if (!$reported_user_id && !$reported_story_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Target laporan tidak valid.']);
    exit;
}

if (empty($reason)) {
    $reason = 'violation';
}

$pdo = getDB();

try {
    // 3. Insert report
    $stmt = $pdo->prepare("
        INSERT INTO reports (reporter_id, reported_user_id, reported_story_id, reason, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$reporter_id, $reported_user_id, $reported_story_id, $reason, $description]);

    echo json_encode([
        'success' => true,
        'message' => 'Laporan berhasil dikirim dan akan segera diproses oleh administrator.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengirimkan laporan: ' . $e->getMessage()]);
}
?>

