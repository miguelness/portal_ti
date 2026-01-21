<?php
/**
 * API para gerenciamento de colaboradores
 * Grupo Barão - Portal TI
 * 
 * Endpoints:
 * GET /api/colaboradores.php - Lista todos os colaboradores
 * GET /api/colaboradores.php?id=X - Busca colaborador específico
 * POST /api/colaboradores.php - Cria novo colaborador
 * PUT /api/colaboradores.php - Atualiza colaborador
 * DELETE /api/colaboradores.php?id=X - Remove colaborador
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../admin/config.php';

// Função para registrar histórico
function registrarHistorico($colaborador_id, $acao, $dados_anteriores = null, $dados_novos = null) {
    global $pdo;
    
    $usuario_id = $_SESSION['user_id'] ?? null;
    $usuario_nome = $_SESSION['nome'] ?? 'Sistema';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO colaboradores_historico 
        (colaborador_id, acao, dados_anteriores, dados_novos, usuario_id, usuario_nome, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $colaborador_id,
        $acao,
        $dados_anteriores ? json_encode($dados_anteriores) : null,
        $dados_novos ? json_encode($dados_novos) : null,
        $usuario_id,
        $usuario_nome,
        $ip_address,
        $user_agent
    ]);
}

// Função para validar dados do colaborador
function validarColaborador($dados) {
    $erros = [];
    
    if (empty($dados['nome'])) {
        $erros[] = 'Nome é obrigatório';
    }
    
    if (empty($dados['empresa'])) {
        $erros[] = 'Empresa é obrigatória';
    }
    
    if (empty($dados['setor'])) {
        $erros[] = 'Setor é obrigatório';
    }
    
    if (empty($dados['email'])) {
        $erros[] = 'E-mail é obrigatório';
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido';
    }
    
    return $erros;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Buscar colaborador específico
                $id = (int)$_GET['id'];
                
                $stmt = $pdo->prepare("
                    SELECT * FROM colaboradores WHERE id = ?
                ");
                
                $stmt->execute([$id]);
                $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$colaborador) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Colaborador não encontrado']);
                    exit;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $colaborador
                ]);
                
            } else {
                // Listar todos os colaboradores
                $search = $_GET['search'] ?? '';
                $status = $_GET['status'] ?? '';
                $empresa = $_GET['empresa'] ?? '';
                $setor = $_GET['setor'] ?? '';
                
                $where = ['1=1'];
                $params = [];
                
                if ($search) {
                    $where[] = "(nome LIKE ? OR ramal LIKE ? OR email LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                if ($status) {
                    $where[] = "status = ?";
                    $params[] = $status;
                }
                
                if ($empresa) {
                    $where[] = "empresa = ?";
                    $params[] = $empresa;
                }
                
                if ($setor) {
                    $where[] = "setor = ?";
                    $params[] = $setor;
                }
                
                $sql = "
                    SELECT * FROM colaboradores
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY nome ASC
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'data' => $colaboradores,
                    'total' => count($colaboradores)
                ]);
            }
            break;
            
        case 'POST':
            // Criar novo colaborador
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                exit;
            }
            
            $erros = validarColaborador($input);
            if (!empty($erros)) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos', 'details' => $erros]);
                exit;
            }
            
            // Verificar se ramal já existe (apenas se ramal não estiver vazio)
            if (!empty($input['ramal'])) {
                $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE ramal = ?");
                $stmt->execute([$input['ramal']]);
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ramal já existe']);
                    exit;
                }
            }
            
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'E-mail já existe']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Inserir colaborador
                $stmt = $pdo->prepare("
                    INSERT INTO colaboradores (ramal, nome, empresa, setor, email, telefone, teams, status, observacoes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $input['ramal'],
                    $input['nome'],
                    $input['empresa'],
                    $input['setor'],
                    $input['email'],
                    $input['telefone'] ?? null,
                    $input['teams'] ?? null,
                    $input['status'] ?? 'ativo',
                    $input['observacoes'] ?? null
                ]);
                
                $colaborador_id = $pdo->lastInsertId();
                
                // Inserir contatos adicionais se fornecidos
                if (!empty($input['contatos'])) {
                    $stmt_contato = $pdo->prepare("
                        INSERT INTO colaborador_contatos (colaborador_id, tipo_contato, valor, descricao, principal)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($input['contatos'] as $contato) {
                        $stmt_contato->execute([
                            $colaborador_id,
                            $contato['tipo'],
                            $contato['valor'],
                            $contato['descricao'] ?? null,
                            $contato['principal'] ?? false
                        ]);
                    }
                }
                
                // Registrar histórico
                registrarHistorico($colaborador_id, 'criado', null, $input);
                
                $pdo->commit();
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'id' => $colaborador_id,
                    'message' => 'Colaborador criado com sucesso'
                ]);
                
            } catch (Exception $e) {
                // Verificar se há uma transação ativa antes de fazer rollback
                try {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                } catch (Exception $rollbackError) {
                    // Ignorar erro se não há transação ativa
                }
                throw $e;
            }
            break;
            
        case 'PUT':
            // Atualizar colaborador
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID é obrigatório']);
                exit;
            }
            
            $id = (int)$input['id'];
            
            // Buscar dados anteriores
            $stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
            $stmt->execute([$id]);
            $dados_anteriores = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dados_anteriores) {
                http_response_code(404);
                echo json_encode(['error' => 'Colaborador não encontrado']);
                exit;
            }
            
            $erros = validarColaborador($input);
            if (!empty($erros)) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos', 'details' => $erros]);
                exit;
            }
            
            // Verificar se ramal já existe (exceto para o próprio registro, apenas se ramal não estiver vazio)
            if (!empty($input['ramal'])) {
                $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE ramal = ? AND id != ?");
                $stmt->execute([$input['ramal'], $id]);
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ramal já existe']);
                    exit;
                }
            }
            
            // Verificar se email já existe (exceto para o próprio registro)
            $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE email = ? AND id != ?");
            $stmt->execute([$input['email'], $id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'E-mail já existe']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Atualizar colaborador
                $stmt = $pdo->prepare("
                    UPDATE colaboradores 
                    SET ramal = ?, nome = ?, empresa = ?, setor = ?, email = ?, 
                        telefone = ?, teams = ?, status = ?, observacoes = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $input['ramal'],
                    $input['nome'],
                    $input['empresa'],
                    $input['setor'],
                    $input['email'],
                    $input['telefone'] ?? null,
                    $input['teams'] ?? null,
                    $input['status'] ?? 'ativo',
                    $input['observacoes'] ?? null,
                    $id
                ]);
                
                // Atualizar contatos se fornecidos
                if (isset($input['contatos'])) {
                    // Remover contatos existentes
                    $stmt = $pdo->prepare("DELETE FROM colaborador_contatos WHERE colaborador_id = ?");
                    $stmt->execute([$id]);
                    
                    // Inserir novos contatos
                    if (!empty($input['contatos'])) {
                        $stmt_contato = $pdo->prepare("
                            INSERT INTO colaborador_contatos (colaborador_id, tipo_contato, valor, descricao, principal)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        
                        foreach ($input['contatos'] as $contato) {
                            $stmt_contato->execute([
                                $id,
                                $contato['tipo'],
                                $contato['valor'],
                                $contato['descricao'] ?? null,
                                $contato['principal'] ?? false
                            ]);
                        }
                    }
                }
                
                // Registrar histórico
                registrarHistorico($id, 'atualizado', $dados_anteriores, $input);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Colaborador atualizado com sucesso'
                ]);
                
            } catch (Exception $e) {
                // Verificar se há uma transação ativa antes de fazer rollback
                try {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                } catch (Exception $rollbackError) {
                    // Ignorar erro se não há transação ativa
                }
                throw $e;
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID é obrigatório']);
                exit;
            }
            
            $id = (int)$_GET['id'];
            
            // Buscar dados antes de excluir
            $stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
            $stmt->execute([$id]);
            $dados_anteriores = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dados_anteriores) {
                http_response_code(404);
                echo json_encode(['error' => 'Colaborador não encontrado']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Registrar histórico antes de excluir
                registrarHistorico($id, 'excluido', $dados_anteriores, null);
                
                // Excluir colaborador (contatos serão excluídos automaticamente por CASCADE)
                $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id = ?");
                $stmt->execute([$id]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Colaborador excluído com sucesso'
                ]);
                
            } catch (Exception $e) {
                // Verificar se há uma transação ativa antes de fazer rollback
                try {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                } catch (Exception $rollbackError) {
                    // Ignorar erro se não há transação ativa
                }
                throw $e;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>