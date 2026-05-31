<?php
// ============================================================
// get_system_settings.php  –  Public fetch of system settings
// ============================================================
session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();

    // Auto-create table if needed
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `setting_key`   varchar(100) NOT NULL,
            `setting_value` text         DEFAULT NULL,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Ensure defaults exist
    $defaults = [
        'site_name'      => 'AUchive Fanfiction Platform',
        'system_warning' => '',
        'server_mode'    => 'online',
        'engine_version' => 'v1.4.0-production'
    ];
    foreach ($defaults as $k => $v) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
        $chk->execute([$k]);
        if ((int)$chk->fetchColumn() === 0) {
            $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$k, $v]);
        }
    }

    $rows = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    echo json_encode(['success' => true, 'settings' => $settings]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
