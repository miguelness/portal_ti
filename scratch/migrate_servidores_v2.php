<?php
require_once 'admin/config.php';

try {
    // Adiciona a coluna categoria_propriedade
    $pdo->exec("ALTER TABLE monitoramento_servidores ADD COLUMN categoria_propriedade ENUM('proprio', 'terceiro') DEFAULT 'proprio' AFTER tipo");
    echo "Coluna 'categoria_propriedade' adicionada com sucesso!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "A coluna já existe.\n";
    } else {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
