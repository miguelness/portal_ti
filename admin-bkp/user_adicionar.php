<?php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Gestão de Usuários';
include 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso
include 'menu.php'; // Caso queira manter o menu superior do seu painel

// Se enviar o form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Gera o hash
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password) VALUES (:u, :p)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $username, ':p' => $hashed]);

    header('Location: users_admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Novo Usuário</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
  <h2 class="mb-4">Adicionar Novo Usuário</h2>
  <form method="post">
    <div class="mb-3">
      <label for="username" class="form-label">Usuário</label>
      <input type="text" name="username" id="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Senha</label>
      <input type="password" name="password" id="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Salvar</button>
    <a href="users_admin.php" class="btn btn-secondary">Voltar</a>
  </form>
</div>
</body>
</html>
