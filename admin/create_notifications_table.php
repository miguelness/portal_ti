<?php
require_once 'config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS persistent_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        required_access VARCHAR(100) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_is_read (is_read),
        INDEX idx_required_access (required_access)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "✓ Tabela 'persistent_notifications' criada com sucesso.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
