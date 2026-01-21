<?php
// register.php — cadastro com verificação por e-mail
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/utils/users_schema.php';
require_once __DIR__ . '/utils/smtp_mail.php';

ensureVerificationColumns($pdo);

$error = '';
$success = '';

function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    return $scheme . '://' . $host;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Informe usuário, e-mail e senha.';
    } else {
        try {
            // checa duplicidade
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Usuário ou e-mail já existe.';
            } else {
                $token = bin2hex(random_bytes(16));
                $hash  = password_hash($password, PASSWORD_DEFAULT);
                $stmtI = $pdo->prepare('INSERT INTO users (username, nome, email, password, email_verified, verification_token, approved) VALUES (?, ?, ?, ?, 0, ?, 0)');
                $stmtI->execute([$username, $nome ?: $username, $email, $hash, $token]);
                $userId = (int)$pdo->lastInsertId();

                $verifyLink = baseUrl() . '/verify_email.php?token=' . urlencode($token) . '&u=' . $userId;
                if (sendVerificationEmail($email, $nome ?: $username, $verifyLink)) {
                    $success = 'Cadastro realizado! Enviamos um e-mail para verificação. Confirme seu e-mail pelo link enviado.';
                } else {
                    $error = 'Não foi possível enviar o e-mail de verificação. Tente novamente mais tarde.';
                }
            }
        } catch (Exception $e) {
            $error = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Criar conta • Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ background:#f8f9fa; }
    .card{ max-width:520px; margin:40px auto; border-radius:12px; }
  </style>
</head>
<body>
<div class="container">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4 mb-3">Criar conta</h1>
      <p class="text-muted mb-4">Após o cadastro, você receberá um e-mail com um link para confirmar o endereço. Depois da confirmação, um administrador validará seu acesso.</p>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="mb-3">
          <label for="username" class="form-label">Usuário</label>
          <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
          <label for="nome" class="form-label">Nome</label>
          <input type="text" class="form-control" id="nome" name="nome" placeholder="Opcional">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">E-mail</label>
          <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Senha</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Cadastrar</button>
        </div>
      </form>
      <div class="text-center mt-3">
        <a href="login.php" class="text-decoration-none">Voltar ao login</a>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
