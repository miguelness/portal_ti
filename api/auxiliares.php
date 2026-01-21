<?php
/**
 * API para dados auxiliares do sistema de colaboradores
 * Grupo Barão - Portal TI
 * 
 * Endpoints:
 * GET /api/auxiliares.php?tipo=empresas - Lista empresas únicas
 * GET /api/auxiliares.php?tipo=setores - Lista setores únicos
 * GET /api/auxiliares.php?tipo=tipos_contato - Lista tipos de contato disponíveis
 * GET /api/auxiliares.php?tipo=estatisticas - Estatísticas gerais
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../admin/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }
    
    $tipo = $_GET['tipo'] ?? '';
    
    switch ($tipo) {
        case 'empresas':
            // Listar empresas únicas com contagem
            $stmt = $pdo->query("
                SELECT empresa, COUNT(*) as total
                FROM colaboradores 
                WHERE empresa IS NOT NULL AND empresa != ''
                GROUP BY empresa 
                ORDER BY empresa ASC
            ");
            
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'data' => $empresas,
                'total' => count($empresas)
            ]);
            break;
            
        case 'setores':
            // Listar setores únicos com contagem
            $stmt = $pdo->query("
                SELECT setor, COUNT(*) as total
                FROM colaboradores 
                WHERE setor IS NOT NULL AND setor != ''
                GROUP BY setor 
                ORDER BY setor ASC
            ");
            
            $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'data' => $setores,
                'total' => count($setores)
            ]);
            break;
            
        case 'tipos_contato':
            // Tipos de contato disponíveis
            $tipos = [
                ['valor' => 'email', 'label' => 'E-mail', 'icone' => 'ti ti-mail'],
                ['valor' => 'telefone', 'label' => 'Telefone', 'icone' => 'ti ti-phone'],
                ['valor' => 'celular', 'label' => 'Celular', 'icone' => 'ti ti-device-mobile'],
                ['valor' => 'whatsapp', 'label' => 'WhatsApp', 'icone' => 'ti ti-brand-whatsapp'],
                ['valor' => 'teams', 'label' => 'Microsoft Teams', 'icone' => 'ti ti-brand-teams'],
                ['valor' => 'skype', 'label' => 'Skype', 'icone' => 'ti ti-brand-skype'],
                ['valor' => 'linkedin', 'label' => 'LinkedIn', 'icone' => 'ti ti-brand-linkedin'],
                ['valor' => 'outro', 'label' => 'Outro', 'icone' => 'ti ti-dots']
            ];
            
            echo json_encode([
                'data' => $tipos,
                'total' => count($tipos)
            ]);
            break;
            
        case 'estatisticas':
            // Estatísticas gerais
            $stats = [];
            
            // Total de colaboradores
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores");
            $stats['total_colaboradores'] = $stmt->fetchColumn();
            
            // Colaboradores ativos
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE status = 'ativo'");
            $stats['colaboradores_ativos'] = $stmt->fetchColumn();
            
            // Colaboradores inativos
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE status = 'inativo'");
            $stats['colaboradores_inativos'] = $stmt->fetchColumn();
            
            // Total de empresas
            $stmt = $pdo->query("SELECT COUNT(DISTINCT empresa) as total FROM colaboradores WHERE empresa IS NOT NULL AND empresa != ''");
            $stats['total_empresas'] = $stmt->fetchColumn();
            
            // Total de setores
            $stmt = $pdo->query("SELECT COUNT(DISTINCT setor) as total FROM colaboradores WHERE setor IS NOT NULL AND setor != ''");
            $stats['total_setores'] = $stmt->fetchColumn();
            
            // Total de contatos (baseado nos campos de contato existentes)
            $stmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM colaboradores 
                WHERE (email IS NOT NULL AND email != '') 
                   OR (telefone IS NOT NULL AND telefone != '') 
                   OR (teams IS NOT NULL AND teams != '')
            ");
            $stats['total_contatos'] = $stmt->fetchColumn();
            
            // Distribuição por empresa
            $stmt = $pdo->query("
                SELECT empresa, COUNT(*) as total
                FROM colaboradores 
                WHERE empresa IS NOT NULL AND empresa != ''
                GROUP BY empresa 
                ORDER BY total DESC
                LIMIT 5
            ");
            $stats['top_empresas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Distribuição por setor
            $stmt = $pdo->query("
                SELECT setor, COUNT(*) as total
                FROM colaboradores 
                WHERE setor IS NOT NULL AND setor != ''
                GROUP BY setor 
                ORDER BY total DESC
                LIMIT 5
            ");
            $stats['top_setores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tipos de contato mais usados (baseado nos campos existentes)
            $tipos_contato = [];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE email IS NOT NULL AND email != ''");
            $email_count = $stmt->fetchColumn();
            if ($email_count > 0) {
                $tipos_contato[] = ['tipo_contato' => 'email', 'total' => $email_count];
            }
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE telefone IS NOT NULL AND telefone != ''");
            $telefone_count = $stmt->fetchColumn();
            if ($telefone_count > 0) {
                $tipos_contato[] = ['tipo_contato' => 'telefone', 'total' => $telefone_count];
            }
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE teams IS NOT NULL AND teams != ''");
            $teams_count = $stmt->fetchColumn();
            if ($teams_count > 0) {
                $tipos_contato[] = ['tipo_contato' => 'teams', 'total' => $teams_count];
            }
            
            // Ordenar por total decrescente
            usort($tipos_contato, function($a, $b) {
                return $b['total'] - $a['total'];
            });
            
            $stats['tipos_contato_uso'] = $tipos_contato;
            
            // Últimas atualizações
            $stmt = $pdo->query("
                SELECT c.nome, c.empresa, c.updated_at
                FROM colaboradores c
                ORDER BY c.updated_at DESC
                LIMIT 5
            ");
            $stats['ultimas_atualizacoes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($stats);
            break;
            
        case 'historico':
            // Histórico de ações (últimas 50)
            $colaborador_id = $_GET['colaborador_id'] ?? null;
            
            // Verificar se a tabela de histórico existe
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'colaboradores_historico'");
                $table_exists = $stmt->rowCount() > 0;
                
                if ($table_exists) {
                    $where = '1=1';
                    $params = [];
                    
                    if ($colaborador_id) {
                        $where = 'colaborador_id = ?';
                        $params[] = (int)$colaborador_id;
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT h.*, c.nome as colaborador_nome
                        FROM colaboradores_historico h
                        LEFT JOIN colaboradores c ON h.colaborador_id = c.id
                        WHERE $where
                        ORDER BY h.created_at DESC
                        LIMIT 50
                    ");
                    
                    $stmt->execute($params);
                    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    // Se a tabela não existe, retornar array vazio
                    $historico = [];
                }
                
                echo json_encode([
                    'data' => $historico,
                    'total' => count($historico)
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'data' => [],
                    'total' => 0,
                    'message' => 'Histórico não disponível'
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Tipo não especificado ou inválido',
                'tipos_disponiveis' => [
                    'empresas',
                    'setores', 
                    'tipos_contato',
                    'estatisticas',
                    'historico'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}
?>