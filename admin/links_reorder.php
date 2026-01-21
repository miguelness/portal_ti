<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_access.php';

// Verificar se o usuário possui acesso adequado (Links ou Gestão de Menu ou Super Administrador)
$user_id = $_SESSION['user_id'] ?? 0;
$accesses = [];
try {
    $stmt = $pdo->prepare("SELECT a.access_name FROM user_access ua JOIN accesses a ON ua.access_id = a.id WHERE ua.user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $accesses = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {
    // Se não conseguir consultar, negar por segurança
}

$hasAccess = in_array('Super Administrador', $accesses) || in_array('Gestão de Menu', $accesses) || in_array('Links', $accesses);
if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payload inválido']);
    exit;
}

$items = $data['items'];

try {
    $pdo->beginTransaction();
    $updated = 0;

    $update = $pdo->prepare("UPDATE menu_links SET parent_id = :parent_id, ordem = :ordem WHERE id = :id");

    foreach ($items as $i) {
        // Validar campos
        $id = isset($i['id']) ? (int)$i['id'] : 0;
        $ordem = isset($i['ordem']) ? (int)$i['ordem'] : null;
        $parentId = array_key_exists('parent_id', $i) ? $i['parent_id'] : null;
        if ($parentId !== null) {
            $parentId = (int)$parentId;
            if ($parentId <= 0) $parentId = null; // normaliza
        }

        if ($id <= 0 || $ordem === null) {
            continue; // ignora itens malformados
        }

        $update->execute([
            ':parent_id' => $parentId,
            ':ordem' => $ordem,
            ':id' => $id,
        ]);
        $updated += $update->rowCount();
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'updated' => $updated]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $e->getMessage()]);
}