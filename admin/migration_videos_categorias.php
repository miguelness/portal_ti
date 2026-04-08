<?php
require_once 'config.php';

try {
    // Adiciona coluna is_treinamento em menu_links se não existir
    $checkSql1 = "SHOW COLUMNS FROM `menu_links` LIKE 'is_treinamento'";
    $stmt1 = $pdo->query($checkSql1);
    if ($stmt1->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `menu_links` ADD `is_treinamento` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_novidade`");
        echo "Coluna 'is_treinamento' adicionada na tabela 'menu_links'.<br>";
    } else {
        echo "Coluna 'is_treinamento' já existe na tabela 'menu_links'.<br>";
    }

    // Adiciona coluna menu_link_id em videos_treinamento se não existir
    $checkSql2 = "SHOW COLUMNS FROM `videos_treinamento` LIKE 'menu_link_id'";
    $stmt2 = $pdo->query($checkSql2);
    if ($stmt2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `videos_treinamento` ADD `menu_link_id` INT(11) NULL AFTER `id`");
        echo "Coluna 'menu_link_id' adicionada na tabela 'videos_treinamento'.<br>";
        
        // Adiciona a Foreign Key
        $pdo->exec("ALTER TABLE `videos_treinamento` ADD CONSTRAINT `fk_video_menu_link` FOREIGN KEY (`menu_link_id`) REFERENCES `menu_links`(`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        echo "Chave estrangeira 'fk_video_menu_link' adicionada.<br>";
    } else {
        echo "Coluna 'menu_link_id' já existe na tabela 'videos_treinamento'.<br>";
    }

    echo "Migração executada com sucesso.";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}
?>
