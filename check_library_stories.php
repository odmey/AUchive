<?php
require_once 'PHP/database.php';
$pdo = getDB();
try {
    $stmt = $pdo->query("DESCRIBE library_stories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
