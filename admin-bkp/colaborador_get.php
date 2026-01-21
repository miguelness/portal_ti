<?php
require_once 'config.php';
require_once 'check_access.php';

// Verificar se o usuário tem acesso
if (!hasAnyAccess(['Gestão de Colaboradores'], $user_accesses)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colaborador) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Colaborador não encontrado']);
        exit;
    }
    
    echo json_encode(['success' => true, 'colaborador' => $colaborador]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>