<?php
require_once __DIR__ . '/database.php';
$pdo = getDB();
$cols = $pdo->query('DESCRIBE stories')->fetchAll();
foreach ($cols as $c) {
    echo $c['Field'] . ' | ' . $c['Type'] . ' | ' . $c['Default'] . "\n";
}
