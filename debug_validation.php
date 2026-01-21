<?php
// Script para debugar erros de validação na planilha ramais.xlsx
include_once 'admin/config.php';

// Incluir as funções do arquivo de processamento
function extractDataFromMultipleSheets($filename) {
    $zip = new ZipArchive();
    
    if ($zip->open($filename) !== TRUE) {
        throw new Exception('Erro ao abrir arquivo XLSX');
    }
    
    // Ler workbook.xml para obter informações das abas
    $workbookXML = $zip->getFromName('xl/workbook.xml');
    if (!$workbookXML) {
        $zip->close();
        throw new Exception('Arquivo workbook.xml não encontrado');
    }
    
    $workbook = simplexml_load_string($workbookXML);
    if (!$workbook) {
        $zip->close();
        throw new Exception('Erro ao processar workbook.xml');
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
    
    $allData = [];
    $sheetIndex = 1;
    $sheetsInfo = [];
    
    foreach ($workbook->sheets->sheet as $sheet) {
        $sheetName = (string)$sheet['name'];
        
        // Tentar ler o arquivo XML da aba
        $sheetXML = $zip->getFromName("xl/worksheets/sheet$sheetIndex.xml");
        
        if (!$sheetXML) {
            $sheetIndex++;
            continue;
        }
        
        $xml = simplexml_load_string($sheetXML);
        if (!$xml) {
            $sheetIndex++;
            continue;
        }
        
        $sheetData = [];
        
        // Processar cada linha
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $hasData = false;
            
            // Determinar quantas colunas processar (até coluna E = 5 colunas)
            $maxCols = 5;
            for ($col = 0; $col < $maxCols; $col++) {
                $rowData[] = '';
            }
            
            // Processar células da linha
            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r'];
                $colIndex = ord(substr($cellRef, 0, 1)) - ord('A');
                
                if ($colIndex >= $maxCols) continue;
                
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
                
                $rowData[$colIndex] = trim($value);
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

function validateRowData($row, $headers) {
    $errors = [];
    
    // Mapear colunas
    $headerMap = [];
    foreach ($headers as $index => $header) {
        $headerMap[strtoupper(trim($header))] = $index;
    }
    
    // Verificar se NOME está presente e não vazio
    if (isset($headerMap['NOME'])) {
        $nome = trim($row[$headerMap['NOME']] ?? '');
        if (empty($nome)) {
            $errors[] = "Campo NOME é obrigatório";
        }
    }
    
    // Verificar RAMAIL (deve ser numérico)
    if (isset($headerMap['RAMAIL'])) {
        $ramail = trim($row[$headerMap['RAMAIL']] ?? '');
        if (!empty($ramail) && !is_numeric($ramail)) {
            $errors[] = "RAMAIL deve ser numérico (valor: '$ramail')";
        }
    }
    
    // Verificar EMAIL (formato válido se não estiver vazio)
    if (isset($headerMap['EMAIL'])) {
        $email = trim($row[$headerMap['EMAIL']] ?? '');
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "EMAIL inválido (valor: '$email')";
        }
    }
    
    // Verificar LINK TEAMS (deve ser URL válida se não estiver vazio)
    if (isset($headerMap['LINK TEAMS'])) {
        $linkTeams = trim($row[$headerMap['LINK TEAMS']] ?? '');
        if (!empty($linkTeams) && !filter_var($linkTeams, FILTER_VALIDATE_URL)) {
            $errors[] = "LINK TEAMS deve ser uma URL válida (valor: '$linkTeams')";
        }
    }
    
    return $errors;
}

try {
    echo "<h2>Debug de Validação - ramais.xlsx</h2>";
    
    $result = extractDataFromMultipleSheets('ramais.xlsx');
    $data = $result['data'];
    $sheetsInfo = $result['sheets'];
    
    echo "<h3>Informações das Abas:</h3>";
    foreach ($sheetsInfo as $sheet) {
        echo "• {$sheet['name']}: {$sheet['rows']} registros<br>";
    }
    
    if (empty($data)) {
        echo "<p style='color: red;'>Nenhum dado encontrado!</p>";
        exit;
    }
    
    $headers = $data[0];
    echo "<h3>Cabeçalhos encontrados:</h3>";
    echo "<pre>" . print_r($headers, true) . "</pre>";
    
    $validationErrors = [];
    $validRows = 0;
    $totalRows = count($data) - 1; // Excluindo cabeçalho
    
    echo "<h3>Validando registros...</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Linha</th><th>Nome</th><th>Ramail</th><th>Email</th><th>Telefone</th><th>Link Teams</th><th>Erros</th></tr>";
    
    for ($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        $rowErrors = validateRowData($row, $headers);
        
        if (empty($rowErrors)) {
            $validRows++;
        } else {
            $validationErrors = array_merge($validationErrors, $rowErrors);
        }
        
        // Mostrar apenas as primeiras 20 linhas para não sobrecarregar
        if ($i <= 20) {
            echo "<tr>";
            echo "<td>$i</td>";
            echo "<td>" . htmlspecialchars($row[0] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row[1] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row[2] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row[3] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row[4] ?? '') . "</td>";
            echo "<td style='color: " . (empty($rowErrors) ? 'green' : 'red') . ";'>";
            echo empty($rowErrors) ? 'OK' : implode('; ', $rowErrors);
            echo "</td>";
            echo "</tr>";
        }
    }
    
    if ($totalRows > 20) {
        echo "<tr><td colspan='7'>... e mais " . ($totalRows - 20) . " linhas</td></tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Resumo da Validação:</h3>";
    echo "<p><strong>Total de registros:</strong> $totalRows</p>";
    echo "<p><strong>Registros válidos:</strong> $validRows</p>";
    echo "<p><strong>Registros com erro:</strong> " . ($totalRows - $validRows) . "</p>";
    echo "<p><strong>Total de erros:</strong> " . count($validationErrors) . "</p>";
    
    if (!empty($validationErrors)) {
        echo "<h3>Tipos de Erros Encontrados:</h3>";
        $errorTypes = array_count_values($validationErrors);
        foreach ($errorTypes as $error => $count) {
            echo "<p>• $error: $count ocorrências</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>