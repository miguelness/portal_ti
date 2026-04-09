<?php
require_once 'admin/config.php';

try {
    $pdo->exec("ALTER TABLE monitoramento_servidores ADD COLUMN ordem INT DEFAULT 0 AFTER categoria_propriedade");
    echo "Coluna 'ordem' adicionada com sucesso!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "A coluna já existe.\n";
    } else {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
