<?php
require_once 'admin/config.php';

try {
    echo "=== VERIFICAÇÃO DETALHADA DAS TABELAS ===\n\n";
    
    // Verificar estrutura da tabela colaboradores
    echo "1. ESTRUTURA DA TABELA 'colaboradores':\n";
    $stmt = $pdo->query("DESCRIBE colaboradores");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ";
        echo ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL');
        echo ($column['Key'] ? " ({$column['Key']})" : '');
        echo ($column['Default'] !== null ? " DEFAULT '{$column['Default']}'" : '');
        echo ($column['Extra'] ? " {$column['Extra']}" : '');
        echo "\n";
    }
    
    // Verificar se há dados na tabela
    echo "\n2. CONTAGEM DE REGISTROS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de colaboradores: {$count['total']}\n";
    
    // Verificar últimos registros
    if ($count['total'] > 0) {
        echo "\n3. ÚLTIMOS 3 REGISTROS:\n";
        $stmt = $pdo->query("SELECT id, nome, email, created_at FROM colaboradores ORDER BY id DESC LIMIT 3");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $record) {
            echo "ID: {$record['id']}, Nome: {$record['nome']}, Email: {$record['email']}, Criado: {$record['created_at']}\n";
        }
    }
    
    // Verificar se há problemas com AUTO_INCREMENT
    echo "\n4. INFORMAÇÕES DA TABELA:\n";
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'colaboradores'");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Auto_increment: {$status['Auto_increment']}\n";
    echo "Engine: {$status['Engine']}\n";
    echo "Collation: {$status['Collation']}\n";
    
    // Testar inserção simples
    echo "\n5. TESTE DE INSERÇÃO SIMPLES:\n";
    
    $testData = [
        'nome' => 'Teste Estrutura',
        'empresa' => 'Empresa Teste',
        'setor' => 'TI',
        'email' => 'teste.estrutura@teste.com',
        'ramal' => '7777',
        'status' => 'ativo'
    ];
    
    $sql = "INSERT INTO colaboradores (nome, empresa, setor, email, ramal, status, created_at) 
            VALUES (:nome, :empresa, :setor, :email, :ramal, :status, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($testData)) {
        $newId = $pdo->lastInsertId();
        echo "✓ Inserção bem-sucedida! Novo ID: $newId\n";
        
        // Remover o registro de teste
        $pdo->prepare("DELETE FROM colaboradores WHERE id = ?")->execute([$newId]);
        echo "✓ Registro de teste removido\n";
    } else {
        echo "✗ Erro na inserção: " . implode(', ', $stmt->errorInfo()) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>