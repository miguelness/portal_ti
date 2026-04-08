<?php
require_once 'config.php';

try {
    // Adiciona colunas de monitoramento na tabela menu_links
    $cols = [
        "verificar_estabilidade TINYINT(1) NOT NULL DEFAULT 0 AFTER is_interno",
        "tempo_bom_ms INT NOT NULL DEFAULT 2000 AFTER verificar_estabilidade",
        "tempo_lento_ms INT NOT NULL DEFAULT 5000 AFTER tempo_bom_ms",
        "ultimo_check DATETIME DEFAULT NULL AFTER tempo_lento_ms",
        "tempo_resposta_ms INT DEFAULT NULL AFTER ultimo_check",
        "link_status ENUM('online','lento','offline') DEFAULT NULL AFTER tempo_resposta_ms"
    ];

    foreach ($cols as $colDef) {
        $colName = explode(' ', trim($colDef))[0];
        $check = $pdo->query("SHOW COLUMNS FROM `menu_links` LIKE '$colName'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `menu_links` ADD $colDef");
            echo "Coluna '$colName' adicionada.<br>";
        } else {
            echo "Coluna '$colName' ja existe.<br>";
        }
    }

    echo "<br>Migracao executada com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
