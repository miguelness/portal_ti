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
    // Buscar o status atual do colaborador
    $stmt = $pdo->prepare("SELECT nome, status FROM colaboradores WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colaborador) {
        $_SESSION['error'] = 'Colaborador não encontrado';
        header('Location: colaboradores_admin.php');
        exit;
    }
    
    // Alternar o status
    $novoStatus = ($colaborador['status'] === 'ativo') ? 'inativo' : 'ativo';
    
    $stmt = $pdo->prepare("UPDATE colaboradores SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$novoStatus, $id]);
    
    $statusTexto = ($novoStatus === 'ativo') ? 'ativado' : 'inativado';
    $_SESSION['success'] = 'Colaborador "' . htmlspecialchars($colaborador['nome']) . '" ' . $statusTexto . ' com sucesso!';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erro ao alterar status do colaborador: ' . $e->getMessage();
}

header('Location: colaboradores_admin.php');
exit;
?>