<?php
session_start();
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Documentos RH';
include 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso
include 'menu.php'; // Caso queira manter o menu superior do seu painel

if (!isset($_GET['id'])) {
    header('Location: rh_documents_admin.php');
    exit;
}

$id = (int)$_GET['id'];

// Busca o registro para obter o nome do arquivo
$sql = "SELECT file_path FROM rh_documents WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if ($document) {
    $file_name = $document['file_path']; // Apenas o nome do arquivo
    $file_full_path = "../uploads_rh/" . $file_name;
    if (file_exists($file_full_path)) {
        unlink($file_full_path);
    }
    $sqlDelete = "DELETE FROM rh_documents WHERE id = :id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtDelete->execute();
}

header('Location: rh_documents_admin.php');
exit;
?>
