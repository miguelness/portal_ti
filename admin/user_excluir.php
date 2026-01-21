<?php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Gestão de Usuários';
include 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso
include 'menu.php'; // Caso queira manter o menu superior do seu painel

// Verifica se o ID do usuário foi passado
if (!isset($_GET['id'])) {
    header('Location: users_admin.php');
    exit;
}

$id = $_GET['id'];

// Exclui o usuário
$sql = "DELETE FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// Redireciona de volta para a lista de usuários
header('Location: users_admin.php');
exit;
?>
