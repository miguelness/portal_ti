<?php
require_once 'admin/config.php';
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Lista de tabelas para remover
    $tables = ['monitoramento_logs', 'monitoramento_servidores', 'phinxlog'];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Tabela $table removida.\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "LIMPEZA_CONCLUIDA_COM_SUCESSO";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
