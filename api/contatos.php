<?php
/**
 * API para gerenciamento de contatos de colaboradores
 * Grupo Barão - Portal TI
 * 
 * Endpoints:
 * GET /api/contatos.php?colaborador_id=X - Lista contatos de um colaborador
 * POST /api/contatos.php - Adiciona novo contato
 * PUT /api/contatos.php - Atualiza contato
 * DELETE /api/contatos.php?id=X - Remove contato
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

// Função para validar dados do contato
function validarContato($dados) {
    $erros = [];
    
    if (empty($dados['colaborador_id'])) {
        $erros[] = 'ID do colaborador é obrigatório';
    }
    
    if (empty($dados['tipo_contato'])) {
        $erros[] = 'Tipo de contato é obrigatório';
    }
    
    if (empty($dados['valor'])) {
        $erros[] = 'Valor do contato é obrigatório';
    }
    
    // Validações específicas por tipo
    if (!empty($dados['tipo_contato']) && !empty($dados['valor'])) {
        switch ($dados['tipo_contato']) {
            case 'email':
                if (!filter_var($dados['valor'], FILTER_VALIDATE_EMAIL)) {
                    $erros[] = 'E-mail inválido';
                }
                break;
            case 'telefone':
            case 'celular':
            case 'whatsapp':
                // Validação básica de telefone (apenas números, espaços, parênteses e hífens)
                if (!preg_match('/^[\d\s\(\)\-\+]+$/', $dados['valor'])) {
                    $erros[] = 'Formato de telefone inválido';
                }
                break;
        }
    }
    
    return $erros;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['colaborador_id'])) {
                // Listar contatos de um colaborador
                $colaborador_id = (int)$_GET['colaborador_id'];
                
                $stmt = $pdo->prepare("
                    SELECT cc.*, c.nome as colaborador_nome
                    FROM colaborador_contatos cc
                    JOIN colaboradores c ON cc.colaborador_id = c.id
                    WHERE cc.colaborador_id = ?
                    ORDER BY cc.principal DESC, cc.tipo_contato ASC
                ");
                
                $stmt->execute([$colaborador_id]);
                $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'data' => $contatos,
                    'total' => count($contatos)
                ]);
                
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID do colaborador é obrigatório']);
            }
            break;
            
        case 'POST':
            // Adicionar novo contato
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                exit;
            }
            
            $erros = validarContato($input);
            if (!empty($erros)) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos', 'details' => $erros]);
                exit;
            }
            
            // Verificar se colaborador existe
            $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE id = ?");
            $stmt->execute([$input['colaborador_id']]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Colaborador não encontrado']);
                exit;
            }
            
            // Se for contato principal, remover principal dos outros contatos do mesmo tipo
            if (!empty($input['principal'])) {
                $stmt = $pdo->prepare("
                    UPDATE colaborador_contatos 
                    SET principal = 0 
                    WHERE colaborador_id = ? AND tipo_contato = ?
                ");
                $stmt->execute([$input['colaborador_id'], $input['tipo_contato']]);
            }
            
            // Inserir contato
            $stmt = $pdo->prepare("
                INSERT INTO colaborador_contatos (colaborador_id, tipo_contato, valor, descricao, principal)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['colaborador_id'],
                $input['tipo_contato'],
                $input['valor'],
                $input['descricao'] ?? null,
                $input['principal'] ?? false
            ]);
            
            $contato_id = $pdo->lastInsertId();
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $contato_id,
                'message' => 'Contato adicionado com sucesso'
            ]);
            break;
            
        case 'PUT':
            // Atualizar contato
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID é obrigatório']);
                exit;
            }
            
            $id = (int)$input['id'];
            
            // Verificar se contato existe
            $stmt = $pdo->prepare("SELECT * FROM colaborador_contatos WHERE id = ?");
            $stmt->execute([$id]);
            $contato_atual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contato_atual) {
                http_response_code(404);
                echo json_encode(['error' => 'Contato não encontrado']);
                exit;
            }
            
            // Usar dados atuais como padrão
            $dados = array_merge($contato_atual, $input);
            
            $erros = validarContato($dados);
            if (!empty($erros)) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos', 'details' => $erros]);
                exit;
            }
            
            // Se for contato principal, remover principal dos outros contatos do mesmo tipo
            if (!empty($input['principal'])) {
                $stmt = $pdo->prepare("
                    UPDATE colaborador_contatos 
                    SET principal = 0 
                    WHERE colaborador_id = ? AND tipo_contato = ? AND id != ?
                ");
                $stmt->execute([$contato_atual['colaborador_id'], $dados['tipo_contato'], $id]);
            }
            
            // Atualizar contato
            $stmt = $pdo->prepare("
                UPDATE colaborador_contatos 
                SET tipo_contato = ?, valor = ?, descricao = ?, principal = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $dados['tipo_contato'],
                $dados['valor'],
                $dados['descricao'] ?? null,
                $dados['principal'] ?? false,
                $id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Contato atualizado com sucesso'
            ]);
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID é obrigatório']);
                exit;
            }
            
            $id = (int)$_GET['id'];
            
            // Verificar se contato existe
            $stmt = $pdo->prepare("SELECT * FROM colaborador_contatos WHERE id = ?");
            $stmt->execute([$id]);
            $contato = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contato) {
                http_response_code(404);
                echo json_encode(['error' => 'Contato não encontrado']);
                exit;
            }
            
            // Excluir contato
            $stmt = $pdo->prepare("DELETE FROM colaborador_contatos WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Contato excluído com sucesso'
            ]);
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
        'message' => $e->getMessage()
    ]);
}
?>