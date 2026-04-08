<?php
require_once 'config.php';
try {
    $stmt = $pdo->query("SELECT * FROM accesses");
    $accesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($accesses, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
