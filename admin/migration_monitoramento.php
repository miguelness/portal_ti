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

    // Tabela de histórico (Logs)
    $sqlLogs = "CREATE TABLE IF NOT EXISTS monitoramento_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        servidor_id INT NOT NULL,
        status ENUM('online', 'lento', 'offline') NOT NULL,
        tempo_ms INT DEFAULT 0,
        verificado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (servidor_id),
        INDEX (verificado_em),
        FOREIGN KEY (servidor_id) REFERENCES monitoramento_servidores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sqlLogs);
    echo "Tabela 'monitoramento_logs' criada com sucesso!\n";

} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage());
}
