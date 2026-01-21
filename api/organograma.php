<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

try {
    $stmt = $pdo->query("
        SELECT id, nome, cargo, departamento, email, telefone, descricao, parent_id
        FROM organograma 
        WHERE ativo = 1 
        ORDER BY nivel_hierarquico, ordem_exibicao, nome
    ");
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function buildHierarchy($items, $parentId = null) {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $item['children'] = buildHierarchy($items, $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    $orgChart = buildHierarchy($colaboradores);

    $dept_stmt = $pdo->query("SELECT DISTINCT departamento FROM organograma WHERE ativo = 1 ORDER BY departamento");
    $departamentos = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => $orgChart,
        'departamentos' => $departamentos
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
