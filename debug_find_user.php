<?php
require_once __DIR__ . '/admin/config.php';
$u = $argv[1] ?? 'miguel.ness';
echo "Buscando usuário por: $u\n";
$stmt = $pdo->prepare('SELECT id, username, email, nome, password FROM users WHERE username = :u OR email = :u LIMIT 1');
$stmt->execute([':u' => $u]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "Nenhum usuário encontrado.\n";
    exit(0);
}
print_r($row);
// Verifica senha se passada como segundo argumento
if (isset($argv[2])) {
    $pwd = $argv[2];
    echo "Verificando senha...\n";
    echo password_verify($pwd, $row['password']) ? "Senha confere.\n" : "Senha NÃO confere.\n";
}
?>
