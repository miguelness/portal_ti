<?php
// utils/users_schema.php
// Garante colunas de verificação e aprovação na tabela users

function ensureVerificationColumns(PDO $pdo): void {
    // Verifica se tabela users existe
    $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if (!$exists) {
        return; // não força criação de tabela aqui
    }

    // email_verified TINYINT(1) DEFAULT 0
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'")->fetchColumn();
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email");
    }

    // verification_token VARCHAR(255) NULL
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_token'")->fetchColumn();
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL AFTER email_verified");
    }

    // verified_at DATETIME NULL
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'verified_at'")->fetchColumn();
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verified_at DATETIME NULL AFTER verification_token");
    }

    // approved TINYINT(1) DEFAULT 0 — aprovação do administrador
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved'")->fetchColumn();
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN approved TINYINT(1) NOT NULL DEFAULT 0 AFTER verified_at");
    }
}

?>
