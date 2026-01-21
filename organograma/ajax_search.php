<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }

    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'Dados JSON inválidos']);
        exit;
    }

    $search = $input['search'] ?? '';
    $page = max(1, intval($input['page'] ?? 1));
    $limit = max(10, min(100, intval($input['limit'] ?? 10)));
    $empresas = isset($input['empresas']) && is_array($input['empresas']) ? $input['empresas'] : [];
    $offset = ($page - 1) * $limit;

    // Construir query dinâmica com filtros
    $conditions = ["COALESCE(ativo,1)=1"]; // manter somente ativos por padrão, igual à Lista
    $params = [];
    $types = '';

    if (!empty($search)) {
        $conditions[] = "(nome LIKE ? OR email LIKE ? OR cargo LIKE ? OR departamento LIKE ? OR empresa LIKE ? OR telefone LIKE ? OR ramal LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        $types .= 'sssssss';
    }

    // Filtro por empresas, preservando "Grupo Barão" sempre visível
    if (!empty($empresas) && !in_array('todos', array_map('strtolower', $empresas))) {
        // normaliza acentos e prepara IN
        $validas = [];
        foreach ($empresas as $em) {
            $em = trim((string)$em);
            if (in_array($em, ['Barão','Toymania','Alfaness','Barao'])) {
                if ($em === 'Barao') $em = 'Barão';
                $validas[] = $em;
            }
        }
        if (!empty($validas)) {
            $placeholders = implode(',', array_fill(0, count($validas), '?'));
            $conditions[] = "(empresa IN ($placeholders) OR (LOWER(empresa) LIKE '%grupo%' AND (LOWER(empresa) LIKE '%barão%' OR LOWER(empresa) LIKE '%barao%')))";
            foreach ($validas as $v) { $params[] = $v; $types .= 's'; }
        }
    }

    $where_clause = '';
    if (!empty($conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $conditions);
    }

    // Contar total
    $count_sql = "SELECT COUNT(*) as total FROM colaboradores $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);

    // Buscar colaboradores
    $sql = "SELECT id, nome, email, cargo, departamento, empresa, telefone, ramal, teams, 
                   parent_id, nivel_hierarquico, ordem_exibicao, tipo_contrato, data_admissao, 
                   observacoes, descricao, ativo, foto 
            FROM colaboradores 
            $where_clause 
            ORDER BY nome 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $colaboradores = [];
    
    while ($row = $result->fetch_assoc()) {
        $colaboradores[] = $row;
    }

    // Retornar resultado
    echo json_encode([
        'success' => true,
        'colaboradores' => $colaboradores,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
}
?>