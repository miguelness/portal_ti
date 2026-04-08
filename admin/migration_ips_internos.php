<?php
require_once 'config.php';

try {
    // Cria tabela ips_internos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ips_internos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `descricao` VARCHAR(100) NOT NULL DEFAULT '',
        `ip_inicio` VARCHAR(45) NOT NULL,
        `ip_fim` VARCHAR(45) DEFAULT NULL,
        `status` ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
        `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabela 'ips_internos' criada/verificada.<br>";

    // Adiciona coluna is_interno em menu_links se não existir
    $check = $pdo->query("SHOW COLUMNS FROM `menu_links` LIKE 'is_interno'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `menu_links` ADD `is_interno` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_treinamento`");
        echo "Coluna 'is_interno' adicionada na tabela 'menu_links'.<br>";
    } else {
        echo "Coluna 'is_interno' já existe.<br>";
    }

    // Insere o IP 127.0.0.1 como padrão interno (localhost) para facilitar testes
    $checkIp = $pdo->query("SELECT COUNT(*) FROM ips_internos WHERE ip_inicio = '127.0.0.1'")->fetchColumn();
    if ($checkIp == 0) {
        $pdo->exec("INSERT INTO ips_internos (descricao, ip_inicio, ip_fim) VALUES ('Localhost (Desenvolvimento)', '127.0.0.1', NULL)");
        echo "IP 127.0.0.1 (localhost) adicionado como interno por padrão.<br>";
    }

    echo "Migração executada com sucesso.";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
