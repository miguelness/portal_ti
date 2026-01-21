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
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Login - Administração Grupo Barão</title>
    <!-- Tabler Core -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
        --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
        font-feature-settings: "cv03", "cv04", "cv11";
        background: radial-gradient(circle at center, #f0f2f5 0%, #e4e7eb 100%);
      }
      .login-logo {
        max-height: 60px;
        margin-bottom: 2rem;
      }
    </style>
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="." class="navbar-brand navbar-brand-autodark">
            <img src="../assets/logo/logo-cores.png" alt="Grupo Barão" class="login-logo">
          </a>
        </div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="h2 text-center mb-4">Acesso Administrativo</h2>
            
            <?php if (!empty($login_error)): ?>
              <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                  <div>
                    <i class="ti ti-alert-circle icon alert-icon"></i>
                  </div>
                  <div>
                    <?php echo htmlspecialchars($login_error); ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off" novalidate>
              <div class="mb-3">
                <label class="form-label">Usuário</label>
                <input type="text" class="form-control" name="username" placeholder="Digite seu usuário" required autofocus>
              </div>
              <div class="mb-2">
                <label class="form-label">
                  Senha
                </label>
                <div class="input-group input-group-flat">
                  <input type="password" class="form-control" name="password" placeholder="Sua senha" autocomplete="off" required>
                  <span class="input-group-text">
                    <a href="#" class="link-secondary" title="Mostrar senha" data-bs-toggle="tooltip">
                      <i class="ti ti-eye"></i>
                    </a>
                  </span>
                </div>
              </div>
              <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
              </div>
            </form>
          </div>
        </div>
        <div class="text-center text-muted mt-3">
          Portal TI Grupo Barão &copy; <?php echo date('Y'); ?>
        </div>
      </div>
    </div>
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    <script>
      // Toggle password visibility
      document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('.input-group-text a');
        const passwordInput = document.querySelector('input[name="password"]');
        
        if(togglePassword && passwordInput) {
          togglePassword.addEventListener('click', function(e) {
            e.preventDefault();
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('ti-eye');
            this.querySelector('i').classList.toggle('ti-eye-off');
          });
        }
      });
    </script>
  </body>
</html>
