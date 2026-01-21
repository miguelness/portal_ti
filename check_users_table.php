<?php
require_once 'admin/config.php';

try {
    echo "=== VERIFICAÇÃO DA TABELA USERS ===\n\n";
    
    // Verificar se a tabela users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✓ Tabela 'users' existe\n\n";
        
        // Mostrar estrutura da tabela
        echo "ESTRUTURA DA TABELA 'users':\n";
        $stmt = $pdo->query("DESCRIBE users");
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
        echo "\nCONTAGEM DE REGISTROS:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de usuários: {$count['total']}\n";
        
        // Mostrar alguns registros se existirem
        if ($count['total'] > 0) {
            echo "\nPRIMEIROS 3 USUÁRIOS:\n";
            $stmt = $pdo->query("SELECT * FROM users LIMIT 3");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                echo "ID: {$user['id']}, ";
                // Tentar diferentes nomes de colunas
                $nameField = isset($user['nome']) ? $user['nome'] : (isset($user['name']) ? $user['name'] : 'N/A');
                $emailField = isset($user['email']) ? $user['email'] : (isset($user['username']) ? $user['username'] : 'N/A');
                echo "Nome: $nameField, Email/Username: $emailField\n";
            }
        }
        
    } else {
        echo "✗ Tabela 'users' NÃO existe\n";
        
        // Verificar outras possíveis tabelas de usuários
        echo "\nVerificando outras tabelas possíveis:\n";
        $possibleTables = ['user', 'usuarios', 'admin_users', 'admins'];
        
        foreach ($possibleTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch();
            if ($exists) {
                echo "✓ Encontrada tabela: $table\n";
                
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "  Colunas: ";
                foreach ($columns as $column) {
                    echo $column['Field'] . " ";
                }
                echo "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>