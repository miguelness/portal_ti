<?php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Feeds';
include 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso
include 'menu.php'; // Caso queira manter o menu superior do seu painel

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $ids = $_POST['ids'];
    $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
    $query = "DELETE FROM noticias WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    if ($stmt->execute($ids)) {
        header("Location: index.php?msg=deleted");
        exit;
    } else {
        echo "Erro ao excluir os itens selecionados.";
    }
} else {
    header("Location: index.php");
    exit;
}
?>
