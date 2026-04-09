<?php
require_once 'admin/config.php';
$stmt = $pdo->query("DESCRIBE monitoramento_servidores");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns, JSON_PRETTY_PRINT);
