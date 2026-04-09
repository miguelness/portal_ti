<?php
require_once 'admin/config.php';

try {
    $pdo->exec("ALTER TABLE monitoramento_servidores ADD COLUMN is_public TINYINT(1) DEFAULT 0 AFTER exibir_dashboard");
    $pdo->exec("ALTER TABLE monitoramento_servidores ADD COLUMN exibir_topo TINYINT(1) DEFAULT 0 AFTER is_public");
    echo "Colunas adicionadas com sucesso!";
} catch (Exception $e) {
    echo "Erro ou colunas já existentes: " . $e->getMessage();
}
