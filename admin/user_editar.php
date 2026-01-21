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

// Verifica se o ID do usuário foi passado
if (!isset($_GET['id'])) {
    header('Location: users_admin.php');
    exit;
}

$id = $_GET['id'];

// Busca os dados do usuário a ser editado
$sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users_admin.php');
    exit;
}

// Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';

    // Se o campo de senha estiver preenchido, gera um novo hash; caso contrário, mantém a senha atual
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sqlUpdate = "UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id";
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':id' => $id
        ];
    } else {
        $sqlUpdate = "UPDATE users SET username = :username, email = :email WHERE id = :id";
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':id' => $id
        ];
    }

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute($params);
    header('Location: users_admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Editar Usuário</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body {
      background: #f5f5f5;
      padding-top: 40px;
    }
    .container {
      max-width: 600px;
    }
    .page-title {
      text-align: center;
      margin-bottom: 30px;
      font-weight: bold;
    }
    .card {
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
<div class="container">
  <h1 class="page-title">Editar Usuário</h1>

  <div class="card">
    <div class="card-header bg-primary text-white">
      <i class="bi bi-pencil-square"></i> Editar Usuário
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label for="username" class="form-label">Usuário</label>
          <input type="text" name="username" id="username" class="form-control" required
                 value="<?php echo htmlspecialchars($user['username']); ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" name="email" id="email" class="form-control"
                 value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Nova Senha</label>
          <input type="password" name="password" id="password" class="form-control">
          <small class="text-muted">Deixe em branco para manter a senha atual.</small>
        </div>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check-lg"></i> Salvar Alterações
        </button>
        <a href="users_admin.php" class="btn btn-secondary">Voltar</a>
      </form>
    </div>
  </div>
</div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Ícones do Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>
