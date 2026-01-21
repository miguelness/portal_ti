<?php
session_start();
include_once 'config.php';

// Se o usuário já estiver logado e estiver acessando login.php diretamente, redireciona para index.php.
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Consulta o usuário no banco de dados
    $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // print_r($user);
    // exit();

    if ($user && password_verify($password, $user['password'])) {
        // Autenticação realizada com sucesso
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['cargo'] = $user['role'];
        header('Location: index.php');
        exit();
    } else {
        $login_error = 'Usuário ou senha incorretos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Administração</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" />
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: linear-gradient(135deg, #6e8efb, #a777e3);
    }
    .login-container {
      background: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
      animation: fadeIn 1s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .login-title {
      font-weight: bold;
      text-align: center;
      color: #333;
      margin-bottom: 1.5rem;
    }
    .form-control:focus {
      box-shadow: 0 0 5px rgba(110,142,251,0.5);
      border-color: #6e8efb;
    }
    .btn-primary {
      background-color: #6e8efb;
      border: none;
    }
    .btn-primary:hover {
      background-color: #5a78d4;
    }
    .alert {
      font-size: 0.875rem;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2 class="login-title">Painel<br>TI Grupo Barão</h2>
    <?php if (!empty($login_error)): ?>
      <div class="alert alert-danger text-center"><?php echo $login_error; ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">Usuário</label>
        <input type="text" class="form-control" id="username" name="username" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Senha</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php exit(); ?>
