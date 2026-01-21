<?php
// Uso: php reset_user_password.php <username> <nova_senha>
require_once __DIR__ . '/admin/config.php';

if ($argc < 3) {
    echo "Uso: php reset_user_password.php <username> <nova_senha>\n";
    exit(1);
}

$username = $argv[1];
$newPassword = $argv[2];

try {
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo "Usuário '$username' não encontrado.\n";
        exit(2);
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
    $upd->execute([':p' => $hash, ':id' => $user['id']]);
    echo "Senha atualizada para usuário '{$user['username']}' (ID {$user['id']}).\n";
} catch (Throwable $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(3);
}
?>
