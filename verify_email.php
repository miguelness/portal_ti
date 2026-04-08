<?php
// verify_email.php — confirma e-mail via token
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/utils/users_schema.php';

ensureVerificationColumns($pdo);

$status = 'invalid';

$token = $_GET['token'] ?? '';
$uid   = isset($_GET['u']) ? (int)$_GET['u'] : 0;

if ($token !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id, email, email_verified FROM users WHERE verification_token = ?' . ($uid ? ' AND id = ?' : '') . ' LIMIT 1');
        $stmt->execute($uid ? [$token, $uid] : [$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && (int)$user['email_verified'] === 0) {
            $upd = $pdo->prepare('UPDATE users SET email_verified = 1, verified_at = NOW(), verification_token = NULL WHERE id = ?');
            $upd->execute([(int)$user['id']]);
            $status = 'verified';

            // Notificar administradores
            try {
                $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmtUser->execute([(int)$user['id']]);
                $username = $stmtUser->fetchColumn();

                $stmtNotify = $pdo->prepare("INSERT INTO persistent_notifications (user_id, title, message, type, required_access) VALUES (?, ?, ?, ?, ?)");
                $stmtNotify->execute([
                    (int)$user['id'],
                    "Novo cadastro para aprovar",
                    "O usuário '$username' confirmou o e-mail e aguarda aprovação.",
                    "registration_approval",
                    "Gestão de Usuários"
                ]);
            } catch (Exception $eNotify) {
                // Silently fail notification if DB error
            }
        } elseif ($user && (int)$user['email_verified'] === 1) {
            $status = 'already';
        } else {
            $status = 'invalid';
        }
    } catch (Exception $e) {
        $status = 'error';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verificação de e-mail • Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow-sm mx-auto" style="max-width:560px">
    <div class="card-body">
      <h1 class="h4 mb-3">Verificação de e-mail</h1>
      <?php if ($status === 'verified'): ?>
        <div class="alert alert-success">E-mail confirmado com sucesso. Aguarde a aprovação do administrador.</div>
      <?php elseif ($status === 'already'): ?>
        <div class="alert alert-info">Este e-mail já foi confirmado anteriormente.</div>
      <?php elseif ($status === 'invalid'): ?>
        <div class="alert alert-danger">Link inválido ou expirado.</div>
      <?php elseif ($status === 'error'): ?>
        <div class="alert alert-danger">Ocorreu um erro ao confirmar seu e-mail.</div>
      <?php endif; ?>
      <div class="text-center mt-3">
        <a href="login.php" class="btn btn-primary">Ir para o login</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
