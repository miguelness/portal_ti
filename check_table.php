<?php
require_once 'admin/config.php';

try {
    $stmt = $pdo->query('DESCRIBE colaboradores');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Estrutura da tabela colaboradores:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} ({$col['Null']}, {$col['Key']}, {$col['Default']})\n";
    }
    
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM colaboradores');
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal de registros: {$count['total']}\n";
    
    // Verificar alguns registros de exemplo
    $stmt = $pdo->query('SELECT * FROM colaboradores LIMIT 3');
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nExemplos de registros:\n";
    foreach ($samples as $i => $sample) {
        echo "Registro " . ($i + 1) . ":\n";
        echo "  - ID: {$sample['id']}\n";
        echo "  - Nome: {$sample['nome']}\n";
        echo "  - Ramal: {$sample['ramal']}\n";
        echo "  - Empresa: {$sample['empresa']}\n";
        echo "  - Email: {$sample['email']}\n";
        echo "\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>