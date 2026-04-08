<?php
// admin/create_sugestoes_table.php
require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS sugestoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NULL,
        mensagem TEXT NOT NULL,
        lida TINYINT(1) DEFAULT 0,
        criado_em DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Tabela 'sugestoes' criada/verificada com sucesso.";
} catch (PDOException $e) {
    die("Erro ao criar a tabela 'sugestoes': " . $e->getMessage());
}
?>
