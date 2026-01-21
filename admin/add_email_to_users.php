<?php
// admin/add_email_to_users.php
// Adiciona a coluna 'email' na tabela users caso não exista

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Verifica se a tabela users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        echo "✗ Tabela 'users' não existe.\n";
        exit;
    }

    // Verifica se a coluna email já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    $colExists = $stmt->fetchColumn();

    if ($colExists) {
        echo "✓ Coluna 'email' já existe na tabela users.\n";
    } else {
        // Adiciona a coluna email após username
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER username");
        echo "✓ Coluna 'email' adicionada à tabela users.\n";
    }

    // Opcional: cria índice para email (não único por ora)
    $idxCheck = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_email'");
    if (!$idxCheck->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("CREATE INDEX idx_users_email ON users (email)");
        echo "✓ Índice 'idx_users_email' criado.\n";
    } else {
        echo "✓ Índice 'idx_users_email' já existe.\n";
    }

    echo "\nConcluído.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
