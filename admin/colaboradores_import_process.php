<?php
session_start();
include_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar permissões
$user_id = $_SESSION['user_id'];
$sql = "SELECT a.access_name FROM user_access ua JOIN accesses a ON ua.access_id = a.id WHERE ua.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);

$hasAccess = in_array('Super Administrador', $user_accesses) || in_array('Gestão de Colaboradores', $user_accesses);

if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se foi enviado um arquivo
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo']);
    exit;
}

$action = $_POST['action'] ?? '';
$uploadedFile = $_FILES['excel_file'];

// Validar tipo de arquivo
$fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
if ($fileExtension !== 'xlsx') {
    echo json_encode(['success' => false, 'message' => 'Apenas arquivos .xlsx são aceitos']);
    exit;
}

// Função para extrair dados de múltiplas abas do arquivo XLSX
function extractDataFromMultipleSheets($filename) {
    $zip = new ZipArchive();
    
    if ($zip->open($filename) !== TRUE) {
        throw new Exception('Erro ao abrir arquivo XLSX');
    }
    
    // Ler workbook.xml para obter informações das abas
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    if ($workbookXml === false) {
        $zip->close();
        throw new Exception('Arquivo XLSX inválido - workbook.xml não encontrado');
    }

    $workbook = simplexml_load_string($workbookXml);
    $sheets = $workbook->sheets->sheet;
    
    // Ler strings compartilhadas
    $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
    $sharedStrings = [];
    
    if ($sharedStringsXML) {
        $xml = simplexml_load_string($sharedStringsXML);
        if ($xml) {
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
    }
    
    $allData = [];
    $sheetIndex = 1;
    $sheetsInfo = [];
    
    foreach ($sheets as $sheet) {
        $sheetName = (string)$sheet['name'];
        $sheetFile = "xl/worksheets/sheet$sheetIndex.xml";
        
        $sheetXML = $zip->getFromName($sheetFile);
        if ($sheetXML === false) {
            $sheetIndex++;
            continue;
        }
        
        $xml = simplexml_load_string($sheetXML);
        if (!$xml) {
            $sheetIndex++;
            continue;
        }
        
        $sheetData = [];
        
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $hasData = false;
            
            foreach ($row->c as $cell) {
                $value = '';
                
                if (isset($cell->v)) {
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        // É uma string compartilhada
                        $index = (int)$cell->v;
                        $value = isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
                    } else {
                        // É um valor direto
                        $value = (string)$cell->v;
                    }
                    
                    if (!empty(trim($value))) {
                        $hasData = true;
                    }
                }
                
                $rowData[] = trim($value);
            }
            
            // Só adiciona linhas que têm pelo menos um dado
            if ($hasData) {
                $sheetData[] = $rowData;
            }
        }
        
        // Adicionar dados da aba se ela tiver conteúdo válido
        if (!empty($sheetData)) {
            // Verificar se a primeira linha parece ser um cabeçalho válido
            $firstRow = $sheetData[0];
            $hasValidHeaders = false;
            
            // Procurar por padrões de cabeçalho conhecidos
            foreach ($firstRow as $cell) {
                $cellUpper = strtoupper(trim($cell));
                if (in_array($cellUpper, ['NOME', 'RAMAIL', 'EMAIL', 'TELEFONE', 'LINK TEAMS', 'TEAMS'])) {
                    $hasValidHeaders = true;
                    break;
                }
            }
            
            // Se não encontrou cabeçalhos na primeira linha, procurar nas próximas
            if (!$hasValidHeaders && count($sheetData) > 1) {
                for ($i = 1; $i < min(3, count($sheetData)); $i++) {
                    foreach ($sheetData[$i] as $cell) {
                        $cellUpper = strtoupper(trim($cell));
                        if (in_array($cellUpper, ['NOME', 'RAMAIL', 'EMAIL', 'TELEFONE', 'LINK TEAMS', 'TEAMS'])) {
                            // Remover linhas antes do cabeçalho
                            $sheetData = array_slice($sheetData, $i);
                            $hasValidHeaders = true;
                            break 2;
                        }
                    }
                }
            }
            
            if ($hasValidHeaders) {
                $dataRowCount = count($sheetData) - 1; // Excluindo cabeçalho
                $sheetsInfo[] = [
                    'name' => $sheetName,
                    'rows' => $dataRowCount
                ];
                
                // Adicionar dados desta aba ao resultado final
                if (empty($allData)) {
                    // Primeira aba com dados válidos - incluir cabeçalho
                    $allData = $sheetData;
                } else {
                    // Abas subsequentes - pular cabeçalho e adicionar apenas dados
                    $dataRows = array_slice($sheetData, 1);
                    $allData = array_merge($allData, $dataRows);
                }
            }
        }
        
        $sheetIndex++;
    }
    
    $zip->close();
    return [
        'data' => $allData,
        'sheets' => $sheetsInfo
    ];
}

// Função para extrair dados do arquivo XLSX (versão original - mantida para compatibilidade)
function extractDataFromXLSX($filename) {
    if (!extension_loaded('zip')) {
        throw new Exception('Extensão ZIP não está disponível');
    }
    
    $zip = new ZipArchive();
    
    if ($zip->open($filename) !== TRUE) {
        throw new Exception('Erro ao abrir arquivo XLSX');
    }
    
    // Ler strings compartilhadas
    $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
    $sharedStrings = [];
    
    if ($sharedStringsXML) {
        $xml = simplexml_load_string($sharedStringsXML);
        if ($xml) {
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
    }
    
    // Ler dados da primeira planilha
    $sheet1XML = $zip->getFromName('xl/worksheets/sheet1.xml');
    
    if (!$sheet1XML) {
        throw new Exception('Não foi possível ler a planilha');
    }
    
    $xml = simplexml_load_string($sheet1XML);
    if (!$xml) {
        throw new Exception('Erro ao processar XML da planilha');
    }
    
    $data = [];
    
    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        $hasData = false;
        
        foreach ($row->c as $cell) {
            $value = '';
            
            if (isset($cell->v)) {
                if (isset($cell['t']) && $cell['t'] == 's') {
                    // É uma string compartilhada
                    $index = (int)$cell->v;
                    $value = isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
                } else {
                    // É um valor direto
                    $value = (string)$cell->v;
                }
                
                if (!empty(trim($value))) {
                    $hasData = true;
                }
            }
            
            $rowData[] = trim($value);
        }
        
        // Só adiciona linhas que têm pelo menos um dado
        if ($hasData) {
            $data[] = $rowData;
        }
    }
    
    $zip->close();
    return $data;
}

// Função para validar estrutura da planilha
function validateSpreadsheetStructure($data) {
    if (empty($data)) {
        throw new Exception('Planilha está vazia');
    }
    
    if (count($data) < 2) {
        throw new Exception('Planilha deve ter pelo menos uma linha de dados além do cabeçalho');
    }
    
    $expectedHeaders = ['NOME', 'RAMAIL', 'EMAIL', 'TELEFONE', 'LINK TEAMS'];
    $headers = $data[0];
    
    // Verificar se tem pelo menos 5 colunas
    if (count($headers) < 5) {
        throw new Exception('Planilha deve ter pelo menos 5 colunas: ' . implode(', ', $expectedHeaders));
    }
    
    // Verificar se os cabeçalhos estão corretos (mais flexível)
    $headerErrors = [];
    for ($i = 0; $i < 5; $i++) {
        $actualHeader = strtoupper(trim($headers[$i]));
        $expectedHeader = $expectedHeaders[$i];
        
        if ($actualHeader !== $expectedHeader) {
            $headerErrors[] = "Coluna " . ($i + 1) . ": esperado '{$expectedHeader}', encontrado '{$actualHeader}'";
        }
    }
    
    if (!empty($headerErrors)) {
        throw new Exception("Cabeçalhos incorretos:\n" . implode("\n", $headerErrors));
    }
    
    return true;
}

// Função para validar dados de uma linha
function validateRowData($rowData, $rowNumber) {
    $errors = [];
    
    $nome = trim($rowData[0] ?? '');
    $ramal = trim($rowData[1] ?? '');
    $email = trim($rowData[2] ?? '');
    $telefone = trim($rowData[3] ?? '');
    $teamsLink = trim($rowData[4] ?? '');
    
    // Nome é obrigatório
    if (empty($nome)) {
        $errors[] = "Nome é obrigatório";
    } elseif (strlen($nome) > 255) {
        $errors[] = "Nome muito longo (máximo 255 caracteres)";
    }
    
    // Validar ramal se fornecido (aceita números e barras)
    if (!empty($ramal) && !preg_match('/^[0-9\/]+$/', $ramal)) {
        $errors[] = "Ramal deve conter apenas números e barras (/)";
    }
    
    // Validar email se fornecido (mais tolerante)
    if (!empty($email)) {
        // Verificar se contém @ e pelo menos um ponto após o @
        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            $errors[] = "Email inválido: $email";
        } elseif (strlen($email) > 255) {
            $errors[] = "Email muito longo (máximo 255 caracteres)";
        }
    }
    
    // Validar telefone se fornecido
    if (!empty($telefone) && strlen($telefone) > 255) {
        $errors[] = "Telefone muito longo (máximo 20 caracteres)";
    }
    
    // Validar Teams link se fornecido (muito flexível)
    if (!empty($teamsLink)) {
        // Aceitar praticamente qualquer coisa que não seja obviamente inválida
        if (strlen($teamsLink) < 5 || strlen($teamsLink) > 500) {
            $errors[] = "Link do Teams deve ter entre 5 e 500 caracteres";
        }
        // Não validar formato específico - aceitar qualquer string razoável
    }
    
    return $errors;
}

// Função para processar link do Teams
function processTeamsLink($teamsLink) {
    if (empty($teamsLink)) {
        return '';
    }
    
    // Se já é um ID (15-25 caracteres alfanuméricos), retorna como está
    if (preg_match('/^[A-Za-z0-9]{15,25}$/', $teamsLink)) {
        return $teamsLink;
    }
    
    // Se é um link completo, extrai o ID
    if (preg_match('/teams\.live\.com\/l\/invite\/([A-Za-z0-9]{15,25})/', $teamsLink, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/teams\.microsoft\.com\/l\/invite\/([A-Za-z0-9]{15,25})/', $teamsLink, $matches)) {
        return $matches[1];
    }
    
    // Se não conseguiu extrair, retorna vazio
    return '';
}

try {
    // Extrair dados da planilha
    $result = extractDataFromMultipleSheets($uploadedFile['tmp_name']);
    $data = $result['data'];
    $sheetsInfo = $result['sheets'];
    
    // Validar estrutura
    validateSpreadsheetStructure($data);
    
    if ($action === 'preview') {
        // Retornar preview dos dados
        echo json_encode([
            'success' => true,
            'data' => array_slice($data, 0, 11), // Primeiras 11 linhas (cabeçalho + 10 registros)
            'total' => count($data) - 1, // Total excluindo cabeçalho
            'sheets' => $sheetsInfo
        ]);
        exit;
    }
    
    if ($action === 'import') {
        // Iniciar transação
        $pdo->beginTransaction();
        
        try {
            // Remover todos os colaboradores existentes
            $stmt = $pdo->prepare("DELETE FROM colaboradores");
            $stmt->execute();
            
            // Preparar statement para inserção
            $insertStmt = $pdo->prepare("
                INSERT INTO colaboradores (nome, ramal, email, telefone, teams) 
                VALUES (:nome, :ramal, :email, :telefone, :teams)
            ");
            
            $imported = 0;
            $errors = [];
            
            // Primeiro passo: validar todas as linhas
            $validationErrors = [];
            $validRows = [];
            
            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                $rowNumber = $i + 1;
                
                // Verificar se a linha tem dados suficientes
                if (count($row) < 5) {
                    $validationErrors[] = "Linha $rowNumber: Dados insuficientes (esperado 5 colunas)";
                    continue;
                }
                
                // Verificar se a linha não está completamente vazia
                $hasData = false;
                foreach ($row as $cell) {
                    if (!empty(trim($cell))) {
                        $hasData = true;
                        break;
                    }
                }
                
                if (!$hasData) {
                    continue; // Pular linhas vazias
                }
                
                // Validar dados da linha
                $rowErrors = validateRowData($row, $rowNumber);
                if (!empty($rowErrors)) {
                    foreach ($rowErrors as $error) {
                        $validationErrors[] = "Linha $rowNumber: $error";
                    }
                    continue;
                }
                
                $validRows[] = [
                    'row' => $row,
                    'number' => $rowNumber
                ];
            }
            
            // Se há muitos erros de validação (mais de 30% dos registros), abortar
            $totalRows = count($data) - 1; // Excluindo cabeçalho
            $errorThreshold = max(50, $totalRows * 0.3); // Pelo menos 50 erros ou 30% dos registros
            
            if (count($validationErrors) > $errorThreshold) {
                throw new Exception("Muitos erros de validação encontrados (" . count($validationErrors) . " de $totalRows registros). Verifique o formato da planilha.");
            }
            
            // Segundo passo: inserir linhas válidas
            foreach ($validRows as $validRow) {
                $row = $validRow['row'];
                $rowNumber = $validRow['number'];
                
                $nome = trim($row[0]);
                $ramal = trim($row[1]);
                $email = trim($row[2]);
                $telefone = trim($row[3]);
                $teamsLink = trim($row[4]);
                
                // Processar link do Teams
                $teamsId = processTeamsLink($teamsLink);
                
                try {
                    $insertStmt->execute([
                        ':nome' => $nome,
                        ':ramal' => $ramal ?: null,
                        ':email' => $email ?: null,
                        ':telefone' => $telefone ?: null,
                        ':teams' => $teamsId ?: null
                    ]);
                    $imported++;
                } catch (PDOException $e) {
                    $validationErrors[] = "Linha $rowNumber: Erro ao inserir no banco - " . $e->getMessage();
                }
            }
            
            // Commit da transação
            $pdo->commit();
            
            $message = "Importação concluída com sucesso! $imported colaboradores importados.";
            if (!empty($validationErrors)) {
                $message .= " " . count($validationErrors) . " linhas com problemas foram ignoradas.";
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $validationErrors
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>