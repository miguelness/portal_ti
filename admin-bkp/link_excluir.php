<?php
// link_excluir.php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Gestão de Menu';
include 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso
include 'menu.php'; // Caso queira manter o menu superior do seu painel

if (!isset($_GET['id'])) {
    header('Location: links_admin.php');
    exit;
}
$id = $_GET['id'];

// Verifica se o link possui filhos
$sqlChild = "SELECT COUNT(*) FROM menu_links WHERE parent_id = :id";
$stmtChild = $pdo->prepare($sqlChild);
$stmtChild->bindParam(':id', $id);
$stmtChild->execute();
$countChildren = $stmtChild->fetchColumn();

if ($countChildren > 0) {
    echo "<script>alert('Este link possui sublinks! Exclua primeiro os filhos.'); window.location='links_admin.php';</script>";
    exit;
}

// Excluir o link
$sql = "DELETE FROM menu_links WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();

header('Location: links_admin.php');
exit;
