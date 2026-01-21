<?php
// Script para verificar a estrutura da tabela organograma
require_once 'admin/config.php';

try {
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'organograma'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "Tabela 'organograma' existe.\n\n";
        
        // Mostrar estrutura da tabela
        echo "Estrutura da tabela:\n";
        echo "====================\n";
        $stmt = $pdo->query("DESCRIBE organograma");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo sprintf("%-20s %-20s %-10s %-10s %-10s %s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'], 
                $column['Extra']
            );
        }
        
        echo "\n";
        
        // Contar registros existentes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM organograma");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de registros existentes: " . $count['total'] . "\n\n";
        
        // Mostrar alguns registros de exemplo
        if ($count['total'] > 0) {
            echo "Primeiros 5 registros:\n";
            echo "======================\n";
            $stmt = $pdo->query("SELECT id, nome, cargo, departamento, parent_id FROM organograma LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($records as $record) {
                echo sprintf("ID: %d | Nome: %s | Cargo: %s | Depto: %s | Parent: %s\n", 
                    $record['id'], 
                    $record['nome'], 
                    $record['cargo'], 
                    $record['departamento'], 
                    $record['parent_id'] ?? 'NULL'
                );
            }
        }
        
    } else {
        echo "Tabela 'organograma' NÃO existe.\n";
        echo "Será necessário criar a tabela.\n";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>