<?php
// login.php (raiz do portal) – usa tabela `users`
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usa a conexão/configuração do admin para $pdo e helpers
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/utils/users_schema.php';
ensureVerificationColumns($pdo);

// Se já logado, redireciona para destino ou home
$next = isset($_GET['next']) ? trim($_GET['next']) : null;
if (!empty($_SESSION['logged_in'])) {
    header('Location: ' . ($next ?: 'index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userOrEmail = trim($_POST['user'] ?? '');
    $password    = $_POST['password'] ?? '';
    $nextPost    = $_POST['next'] ?? null;

    if ($userOrEmail === '' || $password === '') {
        $error = 'Informe usuário/e-mail e senha.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, username, nome, email, password, cargo FROM users WHERE username = :ue OR email = :ue LIMIT 1'
            );
            $stmt->execute([':ue' => $userOrEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['nome']      = $user['nome'] ?? $user['username'];
                if (isset($user['cargo'])) {
                    $_SESSION['cargo'] = $user['cargo'];
                }

                $dest = $nextPost ?: $next ?: 'index.php';
                header('Location: ' . $dest);
                exit;
            } else {
                $error = 'Usuário ou senha inválidos.';
            }
        } catch (Exception $e) {
            $error = 'Erro ao autenticar. Tente novamente.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login • Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --bg-dark: #0f172a;
      --bg-darker: #0b1324;
      --card: rgba(255,255,255,.28);
      --border: rgba(15,23,42,.12);
      --accent: #0ea5e9;
      --accent-2: #3b82f6;
      --text: #0f172a;
      --muted: #334155;
    }
    html,body{ height:100%; }
    body{
      background: radial-gradient(1200px 520px at 10% 10%, rgba(14,165,233,.18), transparent 60%),
                  radial-gradient(800px 400px at 90% 20%, rgba(99,102,241,.15), transparent 60%),
                  linear-gradient(160deg, var(--bg-darker), var(--bg-dark));
      color:#fff;
    }
    .wrap{ min-height:100vh; display:flex; align-items:center; justify-content:center; padding: 4vh 16px; }
    .brand{ display:flex; flex-direction:column; align-items:center; gap:12px; justify-content:center; margin-bottom:22px; }
    .brand-title{ font-weight:700; color:#ffffff; letter-spacing:.3px; text-shadow: 0 1px 2px rgba(0,0,0,.25); }
    .logo-circle{ width: 92px; height: 92px; background: #ffffff; border-radius: 50%; display:flex; align-items:center; justify-content:center; box-shadow: 0 6px 18px rgba(0,0,0,0.10); border: 1px solid rgba(0,0,0,0.06); }
    .logo-circle img{ max-width:64px; height:auto; }
    .glass{ width:100%; max-width:520px; background: var(--card); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border:1px solid var(--border); border-radius:16px; box-shadow: 0 24px 50px rgba(15,23,42,.35); overflow:hidden; }
    .glass-header{ padding:20px 24px; border-bottom:1px solid var(--border); background: rgba(255,255,255,.50); }
    .glass-header h5{ margin:0; color:#0f172a; display:flex; align-items:center; gap:8px; justify-content:center; font-weight:700; }
    .glass-body{ padding:24px; }
    .glass-footer{ padding:12px 18px; border-top:1px solid var(--border); background: rgba(255,255,255,.42); color:#e2e8f0; font-size:.9rem; text-align:center; text-shadow: 0 1px 2px rgba(0,0,0,.25); }
    .btn-primary{ background: linear-gradient(90deg, var(--accent-2), var(--accent)); border-color: var(--accent-2); }
    .btn-primary:hover{ filter: brightness(1.07); }
    .form-label{ color: #e2e8f0; font-weight:600; margin-bottom:6px; }
    .form-control{ height:44px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.92); }
    .form-control::placeholder{ color:#9ca3af; }
    .form-control:focus{ border-color: var(--accent); box-shadow: 0 0 0 .25rem rgba(14,165,233,.25); }
    .btn{ border-radius:10px; height:44px; }
    .auth-links{ text-align:center; }
    .auth-links a{ color: var(--accent-2); }
    .footer-link{ color:#ffffff; }
    .footer-link:hover{ color:#e2e8f0; }
  </style>
  </head>
<body>
<div class="wrap">
  <div class="w-100" style="max-width:520px;">
    <div class="brand">
      <div class="logo-circle">
        <img src="assets/logo/logo-cores.png" alt="Logo">
      </div>
      <div class="brand-title">Sistemas do Portal</div>
    </div>
    <div class="glass">
      <div class="glass-header"><h5>Acessar</h5></div>
      <div class="glass-body">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?php if ($next): ?>
            <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Usuário ou e-mail</label>
            <input type="text" name="user" class="form-control" required value="<?php echo htmlspecialchars($_POST['user'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Senha</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="d-grid"><button class="btn btn-primary">Entrar</button></div>
        </form>
        <div class="auth-links mt-3"><a href="register.php" class="text-decoration-none">Criar conta</a></div>
      </div>
      <div class="glass-footer"><span>Acesso restrito • Portal</span></div>
    </div>
    <div class="text-center mt-3">
            <a href="../index.php" class="text-decoration-none footer-link">Voltar ao Portal</a>
        </div>
  </div>
</div>
</body>
</html>
