<?php
require_once __DIR__ . '/database.php';
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE stories ADD COLUMN progress_status ENUM('ongoing', 'complete', 'hiatus') NOT NULL DEFAULT 'ongoing' AFTER status");
    echo 'SUCCESS: Column progress_status added to stories table.';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo 'INFO: Column progress_status already exists.';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
?>
