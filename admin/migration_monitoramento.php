<?php
/**
 * admin/migration_monitoramento.php
 * Criação da tabela para monitoramento de servidores e links.
 */

require_once 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS monitoramento_servidores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        ip_ou_url VARCHAR(255) NOT NULL,
        tipo ENUM('interno', 'externo') DEFAULT 'externo',
        status ENUM('online', 'lento', 'offline', 'pendente') DEFAULT 'pendente',
        tempo_resposta_ms INT DEFAULT 0,
        ultima_verificacao DATETIME NULL,
        verificar_estabilidade TINYINT(1) DEFAULT 1,
        exibir_dashboard TINYINT(1) DEFAULT 0,
        status_registro ENUM('ativo', 'inativo') DEFAULT 'ativo',
        tempo_bom_ms INT DEFAULT 1500,
        tempo_lento_ms INT DEFAULT 3500,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Tabela 'monitoramento_servidores' criada ou já existente com sucesso!\n";

    // Adiciona uma coluna de acesso se ela não existir (opcional, mas bom ter para controle futuro)
    // $pdo->exec("INSERT IGNORE INTO accesses (access_name) VALUES ('Monitoramento')");

} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage());
}
