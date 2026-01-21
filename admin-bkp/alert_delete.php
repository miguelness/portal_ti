<?php
session_start();
include 'check_access.php'; 
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: alerts_admin.php');
    exit;
}

$id = (int)$_GET['id'];

// Busca o registro para obter os nomes dos arquivos
$sql = "SELECT image, file_path FROM alerts WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$alert = $stmt->fetch(PDO::FETCH_ASSOC);

if ($alert) {
    // Diretório de upload (na raiz)
    $upload_dir = "../uploads_alertas/";
    if (!empty($alert['image']) && file_exists($upload_dir . $alert['image'])) {
        unlink($upload_dir . $alert['image']);
    }
    if (!empty($alert['file_path']) && file_exists($upload_dir . $alert['file_path'])) {
        unlink($upload_dir . $alert['file_path']);
    }
    $sqlDelete = "DELETE FROM alerts WHERE id = :id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtDelete->execute();
}

header('Location: alerts_admin.php');
exit;
?>
