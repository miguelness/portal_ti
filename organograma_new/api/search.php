<?php
/**
 * API para busca AJAX de colaboradores
 * Retorna resultados em JSON para atualização dinâmica
 */

require_once '../../admin/check_access.php';
require_once '../../organograma/config.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obter dados do POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Extrair parâmetros
$search = isset($input['search']) ? trim($input['search']) : '';
$page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
$limit = isset($input['limit']) ? max(15, (int)$input['limit']) : 15;
$empresas = isset($input['empresas']) ? (array)$input['empresas'] : ['todos'];

try {
    // Construir query
    $where = ["COALESCE(ativo, 1) = 1"];
    $params = [];
    $types = '';
    
    // Filtro por empresa
    if (!empty($empresas) && !in_array('todos', array_map('strtolower', $empresas))) {
        $empresasValidas = array_filter($empresas, function($e) {
            return in_array($e, ['Barão', 'Toymania', 'Alfaness']);
        });
        
        if (!empty($empresasValidas)) {
            $placeholders = str_repeat('?,', count($empresasValidas) - 1) . '?';
            $where[] = "(empresa IN ($placeholders) OR (LOWER(empresa) LIKE '%grupo%' AND (LOWER(empresa) LIKE '%barão%' OR LOWER(empresa) LIKE '%barao%')))";
            $params = array_merge($params, $empresasValidas);
            $types .= str_repeat('s', count($empresasValidas));
        }
    }
    
    // Busca textual
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $where[] = "(nome LIKE ? OR cargo LIKE ? OR departamento LIKE ? OR empresa LIKE ? OR email LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $types .= 'sssss';
    }
    
    $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
    
    // Contagem total
    $countSql = "SELECT COUNT(*) as total FROM colaboradores" . $whereClause;
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Dados paginados
    $offset = ($page - 1) * $limit;
    $sql = "SELECT * FROM colaboradores" . $whereClause . " ORDER BY nome LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $colaboradores = $result->fetch_all(MYSQLI_ASSOC);
    
    // Processar resultados
    $dados = array_map(function($colaborador) {
        // Gerar avatar URL se não houver foto
        $avatarUrl = '';
        if (!empty($colaborador['foto'])) {
            $foto = $colaborador['foto'];
            $rel = (strpos($foto, 'uploads/') === 0) ? $foto : 'uploads/' . $foto;
            if (file_exists(__DIR__ . '/../../organograma/' . $rel)) {
                $avatarUrl = '../organograma/' . $rel;
            }
        }
        
        if (empty($avatarUrl)) {
            $nome = $colaborador['nome'] ?? 'User';
            $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($nome) . '&background=random';
        }
        
        // Processar links
        $waLink = '';
        if (!empty($colaborador['telefone'])) {
            $num = preg_replace('/\D+/', '', $colaborador['telefone']);
            if ($num !== '') {
                if (strpos($num, '55') !== 0) {
                    $num = '55' . $num;
                }
                $waLink = 'https://wa.me/' . $num;
            }
        }
        
        $teamsLink = '';
        if (!empty($colaborador['teams'])) {
            $teams = trim($colaborador['teams']);
            if ($teams !== '') {
                if (preg_match('/^https?:/i', $teams)) {
                    $teamsLink = $teams;
                } else {
                    $teamsLink = 'https://teams.live.com/l/invite/' . rawurlencode($teams);
                }
            }
        }
        
        $emailLink = '';
        if (!empty($colaborador['email'])) {
            $emailLink = 'mailto:' . $colaborador['email'];
        }
        
        return [
            'id' => $colaborador['id'],
            'nome' => $colaborador['nome'],
            'cargo' => $colaborador['cargo'],
            'departamento' => $colaborador['departamento'],
            'empresa' => $colaborador['empresa'],
            'ramal' => $colaborador['ramal'],
            'telefone' => $colaborador['telefone'],
            'email' => $colaborador['email'],
            'teams' => $colaborador['teams'],
            'tipo_contrato' => $colaborador['tipo_contrato'],
            'data_admissao' => $colaborador['data_admissao'],
            'observacoes' => $colaborador['observacoes'],
            'foto' => $avatarUrl,
            'ativo' => $colaborador['ativo'],
            'links' => [
                'whatsapp' => $waLink,
                'teams' => $teamsLink,
                'email' => $emailLink
            ]
        ];
    }, $colaboradores);
    
    // Retornar resposta
    echo json_encode([
        'success' => true,
        'colaboradores' => $dados,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'per_page' => $limit,
            'total_records' => $total,
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ],
        'search_info' => [
            'query' => $search,
            'results_count' => count($dados),
            'total_count' => $total
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}