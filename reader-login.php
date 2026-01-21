<?php
// reader-login.php

require_once 'reader-session.php';
require_once 'conexao.php';

if ($readerId) {
    header('Location: index.php');
    exit;
}

$destinoGet = isset($_GET['from']) ? trim($_GET['from']) : null;
$error      = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $destinoPost = $_POST['from'] ?? null;

    $stmt = $conn->prepare(
        'SELECT id, senha_hash FROM readers WHERE email = ? AND status = "ativo" LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && password_verify($senha, $row['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['reader_id'] = (int)$row['id'];

        $destino = $_SESSION['after_login']
                 ?? $destinoPost
                 ?? $destinoGet
                 ?? 'index.php';
        unset($_SESSION['after_login']);
        header("Location: $destino");
        exit;
    }

    $error = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Entrar • Grupo Barão</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center py-5" style="background:#f8f9fa;min-height:100vh">
<main class="container" style="max-width:440px">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3 text-center">Entrar</h1>
      <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <?php if ($destinoGet): ?>
          <input type="hidden" name="from" value="<?= htmlspecialchars($destinoGet) ?>">
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" required>
        </div>
        <div class="d-grid">
          <button class="btn btn-primary">Entrar</button>
        </div>
      </form>
      <p class="text-center small mt-3 mb-0">
        Ainda não tem conta? 
        <a href="reader-register.php<?= $destinoGet ? '?from='.urlencode($destinoGet) : '' ?>">Criar gratuitamente</a>
      </p>
    </div>
  </div>
</main>
</body>
</html>
