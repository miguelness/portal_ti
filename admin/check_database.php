<?php
require_once 'config.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:\n" . implode("\n", $tables) . "\n\n";

    if (in_array('notifications', $tables)) {
        $stmt = $pdo->query("DESCRIBE notifications");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
