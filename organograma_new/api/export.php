<?php
/**
 * API para exportação de dados do organograma
 * Suporta múltiplos formatos: CSV, JSON, Excel
 */

require_once '../../admin/check_access.php';
require_once '../../organograma/config.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obter parâmetros
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'json';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$empresas = isset($_GET['empresas']) ? (array)$_GET['empresas'] : ['todos'];

// Validar formato
$allowedFormats = ['csv', 'json', 'xlsx'];
if (!in_array($format, $allowedFormats)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato inválido. Use: csv, json, xlsx']);
    exit;
}

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
    
    // Executar query
    $sql = "SELECT * FROM colaboradores" . $whereClause . " ORDER BY nome";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $colaboradores = $result->fetch_all(MYSQLI_ASSOC);
    
    // Processar dados
    $dados = array_map(function($colaborador) {
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
            'foto' => $colaborador['foto'],
            'nivel_hierarquico' => $colaborador['nivel_hierarquico'],
            'parent_id' => $colaborador['parent_id']
        ];
    }, $colaboradores);
    
    // Exportar no formato solicitado
    switch ($format) {
        case 'csv':
            exportarCSV($dados);
            break;
        case 'json':
            exportarJSON($dados);
            break;
        case 'xlsx':
            exportarExcel($dados);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao exportar dados: ' . $e->getMessage()]);
    exit;
}

function exportarCSV($dados) {
    $filename = 'organograma_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Adicionar BOM para UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Cabeçalhos
    $headers = [
        'ID', 'Nome', 'Cargo', 'Departamento', 'Empresa', 
        'Ramal', 'Telefone', 'E-mail', 'Teams', 'Tipo Contrato',
        'Data Admissão', 'Observações'
    ];
    fputcsv($output, $headers, ';');
    
    // Dados
    foreach ($dados as $colaborador) {
        fputcsv($output, [
            $colaborador['id'],
            $colaborador['nome'],
            $colaborador['cargo'],
            $colaborador['departamento'],
            $colaborador['empresa'],
            $colaborador['ramal'],
            $colaborador['telefone'],
            $colaborador['email'],
            $colaborador['teams'],
            $colaborador['tipo_contrato'],
            $colaborador['data_admissao'],
            $colaborador['observacoes']
        ], ';');
    }
    
    fclose($output);
    exit;
}

function exportarJSON($dados) {
    $filename = 'organograma_' . date('Y-m-d') . '.json';
    
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode([
        'exportado_em' => date('Y-m-d H:i:s'),
        'total_registros' => count($dados),
        'colaboradores' => $dados
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function exportarExcel($dados) {
    // Redirecionar para o exportador Excel existente
    $params = $_GET;
    unset($params['format']);
    
    $queryString = http_build_query($params);
    header('Location: ../../organograma/export_xlsx.php?' . $queryString);
    exit;
}