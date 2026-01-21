<?php
require_once 'config.php';
require_once 'check_access.php';

// Verificar se o usuário tem acesso
if (!hasAnyAccess(['Gestão de Colaboradores'], $user_accesses)) {
    $_SESSION['error'] = 'Acesso negado';
    header('Location: colaboradores_admin.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID inválido';
    header('Location: colaboradores_admin.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // Verificar se o colaborador existe
    $stmt = $pdo->prepare("SELECT nome FROM colaboradores WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colaborador) {
        $_SESSION['error'] = 'Colaborador não encontrado';
        header('Location: colaboradores_admin.php');
        exit;
    }
    
    // Excluir o colaborador
    $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = 'Colaborador "' . htmlspecialchars($colaborador['nome']) . '" excluído com sucesso!';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erro ao excluir colaborador: ' . $e->getMessage();
}

header('Location: colaboradores_admin.php');
exit;
?>