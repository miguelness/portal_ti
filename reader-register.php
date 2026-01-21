<?php
// reader-register.php

require_once 'reader-session.php';
require_once 'conexao.php';

if ($readerId) {
    header('Location: index.php');
    exit;
}

$destinoGet = isset($_GET['from']) ? trim($_GET['from']) : null;
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']    ?? '');
    $email     = trim($_POST['email']   ?? '');
    $senha     = $_POST['senha']        ?? '';
    $confirma  = $_POST['confirma']     ?? '';
    $destinoPost = $_POST['from']       ?? null;

    if (mb_strlen($nome) < 3)          $errors[] = 'Nome inválido.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
    if (mb_strlen($senha) < 6)         $errors[] = 'Senha deve ter ao menos 6 caracteres.';
    if ($senha !== $confirma)          $errors[] = 'Confirmação de senha não confere.';

    if (!$errors) {
        $stm = $conn->prepare('SELECT 1 FROM readers WHERE email = ? LIMIT 1');
        $stm->bind_param('s', $email);
        $stm->execute();
        if ($stm->get_result()->fetch_column()) {
            $errors[] = 'E-mail já cadastrado.';
        }
    }

    if (!$errors) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stm  = $conn->prepare(
            'INSERT INTO readers (nome, email, senha_hash) VALUES (?, ?, ?)'
        );
        $stm->bind_param('sss', $nome, $email, $hash);
        if ($stm->execute()) {
            session_regenerate_id(true);
            $_SESSION['reader_id'] = $conn->insert_id;
            $destino = $_SESSION['after_login'] 
                     ?? $destinoPost 
                     ?? $destinoGet 
                     ?? 'index.php';
            unset($_SESSION['after_login']);
            header("Location: $destino");
            exit;
        }
        $errors[] = 'Falha ao gravar no banco.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Criar conta • Grupo Barão</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center py-5" style="background:#f8f9fa;min-height:100vh">
<main class="container" style="max-width:440px">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3 text-center">Criar conta</h1>
      <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <form method="post" novalidate>
        <?php if ($destinoGet): ?>
          <input type="hidden" name="from" value="<?= htmlspecialchars($destinoGet) ?>">
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label">Nome completo</label>
          <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" required minlength="6">
        </div>
        <div class="mb-4">
          <label class="form-label">Confirmar senha</label>
          <input type="password" name="confirma" class="form-control" required minlength="6">
        </div>
        <div class="d-grid">
          <button class="btn btn-primary">Criar conta</button>
        </div>
      </form>
      <p class="text-center small mt-3 mb-0">
        Já tem conta? 
        <a href="reader-login.php<?= $destinoGet ? '?from='.urlencode($destinoGet) : '' ?>">Entrar</a>
      </p>
    </div>
  </div>
</main>
</body>
</html>
