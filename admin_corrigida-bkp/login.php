<?php
require_once 'config.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, nome, cargo FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nome'] = $user['nome'] ?? $user['username'];
                $_SESSION['cargo'] = $user['cargo'] ?? 'Usuário';
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro interno do sistema. Tente novamente.';
        }
    }
}

// Verifica se há mensagem de erro na URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'access_denied':
            $error = 'Acesso negado. Você não tem permissão para acessar esta página.';
            break;
        case 'session_expired':
            $error = 'Sua sessão expirou. Faça login novamente.';
            break;
    }
}
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Login - Portal Administrativo Grupo Barão</title>
    
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .card {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        .logo {
            max-height: 60px;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="../index.php" class="navbar-brand navbar-brand-autodark">
                    <img src="../assets/logo/logo-branco.png" alt="Grupo Barão" class="logo">
                </a>
            </div>
            
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">Portal Administrativo</h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="d-flex">
                            <div>
                                <i class="ti ti-alert-circle"></i>
                            </div>
                            <div>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" autocomplete="off" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Usuário</label>
                            <input type="text" name="username" class="form-control" placeholder="Digite seu usuário" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label">
                                Senha
                                <span class="form-label-description">
                                    <a href="#" tabindex="-1">Esqueci minha senha</a>
                                </span>
                            </label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control" placeholder="Digite sua senha" autocomplete="off" required>
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary" title="Mostrar senha" data-bs-toggle="tooltip">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input"/>
                                <span class="form-check-label">Lembrar de mim neste dispositivo</span>
                            </label>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-login me-2"></i>
                                Entrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center text-muted mt-3">
                <small>
                    © <?= date('Y') ?> Grupo Barão. Todos os direitos reservados.<br>
                    Sistema Administrativo v<?= SYSTEM_VERSION ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.querySelector('[data-bs-toggle="tooltip"]').addEventListener('click', function(e) {
            e.preventDefault();
            const passwordInput = document.querySelector('input[name="password"]');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'ti ti-eye-off';
            } else {
                passwordInput.type = 'password';
                icon.className = 'ti ti-eye';
            }
        });
    </script>
</body>
</html>