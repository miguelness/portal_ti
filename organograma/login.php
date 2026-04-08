<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suporte ao redirecionamento pós-login preservando a URL de destino
$next = isset($_GET['next']) ? trim($_GET['next']) : null;

// Se já estiver logado no organograma e acessar o login diretamente, retorna ao destino ou índice
if (!empty($_SESSION['org_logged_in'])) {
    header('Location: ' . ($next ?: 'index.php'));
    exit;
}

// Mantém identidade do Organograma (injeção de modal e checagem)
require_once __DIR__ . '/config.php';
// Usa conexão e configuração do admin para autenticação
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../utils/users_schema.php';
ensureVerificationColumns($pdo);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nextPost = $_POST['next'] ?? null;

    if ($username === '' || $password === '') {
        $error = 'Informe usuário e senha.';
    } else {
        try {
            // Corrige coluna 'nome' e permite login por usuário ou e-mail
            $stmt = $pdo->prepare("SELECT id, username, nome, email, password, email_verified, approved FROM users WHERE username = :u OR email = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ((int)($user['email_verified'] ?? 0) !== 1) {
                    $error = 'Por favor, confirme seu e-mail antes de acessar.';
                } elseif ((int)($user['approved'] ?? 0) !== 1) {
                    $error = 'Sua conta está aguardando aprovação administrativa.';
                } else {
                    $_SESSION['org_logged_in'] = true;
                    $_SESSION['org_user_id'] = (int)$user['id'];
                    $_SESSION['org_username'] = $user['username'];
                    $_SESSION['org_nome'] = $user['nome'] ?? $user['username'];

                    // Acesso obrigatório Organograma é verificado nos arquivos de destino (via check_access)

                    $dest = $nextPost ?: $next ?: 'index.php';
                    header('Location: ' . $dest);
                    exit;
                }
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
  <title>Acesso Seguro • Organograma</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #020617;
      --bg-darker: #0f172a;
      --glass-bg: rgba(255, 255, 255, 0.03);
      --glass-border: rgba(255, 255, 255, 0.08);
      --glass-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      --accent: #38bdf8;
      --accent-glow: rgba(56, 189, 248, 0.4);
      --accent-2: #818cf8;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --input-bg: rgba(15, 23, 42, 0.4);
      --font-family: 'Inter', sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-family); }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--bg-dark);
      color: var(--text-main);
      overflow: hidden;
      position: relative;
    }

    /* Ambient Animated Orbs */
    .ambient-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.5;
      animation: float 20s infinite ease-in-out alternate;
      z-index: 0;
    }
    .orb-1 {
      width: 50vw; height: 50vw;
      background: radial-gradient(circle, var(--accent-2) 0%, transparent 70%);
      top: -20vh; left: -10vw;
      animation-delay: -5s;
    }
    .orb-2 {
      width: 40vw; height: 40vw;
      background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
      bottom: -15vh; right: -5vw;
      animation-duration: 25s;
    }

    @keyframes float {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(10vw, 5vh) scale(1.2); }
    }

    /* Wrapper and Staggered Animations */
    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 24px;
      perspective: 1000px;
    }

    .stagger-in {
      opacity: 0;
      transform: translateY(20px);
      animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .stagger-in:nth-child(1) { animation-delay: 0.1s; }
    .stagger-in:nth-child(2) { animation-delay: 0.2s; }
    .stagger-in:nth-child(3) { animation-delay: 0.3s; }
    .stagger-in:nth-child(4) { animation-delay: 0.4s; }

    @keyframes fadeUp {
      to { opacity: 1; transform: translateY(0); }
    }

    /* Glass Card */
    .glass-card {
      background: var(--glass-bg);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      box-shadow: var(--glass-shadow), inset 0 1px 0 rgba(255,255,255,0.1);
      padding: 40px 32px;
      position: relative;
      overflow: hidden;
    }

    /* Brand Header */
    .brand-header {
      text-align: center;
      margin-bottom: 32px;
    }
    .logo-container {
      width: 80px; height: 80px;
      margin: 0 auto 16px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }
    .logo-container img { width: 48px; height: auto; }
    .brand-header h1 {
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      margin-bottom: 6px;
    }
    .brand-header p {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    /* Form Styles & Floating Labels */
    .form-group {
      position: relative;
      margin-bottom: 20px;
    }
    .form-control {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 16px 16px 16px 48px; /* Extra padding for icon */
      font-size: 1rem;
      color: var(--text-main);
      transition: all 0.3s ease;
      outline: none;
    }
    .form-control:focus {
      border-color: var(--accent);
      background: rgba(15, 23, 42, 0.6);
      box-shadow: 0 0 0 4px var(--accent-glow);
    }
    
    /* Input Icons */
    .input-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      width: 20px; height: 20px;
      transition: color 0.3s ease;
      pointer-events: none;
    }
    .form-control:focus ~ .input-icon,
    .form-control:not(:placeholder-shown) ~ .input-icon {
      color: var(--accent);
    }

    /* Floating Label Magic */
    .floating-label {
      position: absolute;
      left: 48px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1rem;
      pointer-events: none;
      transition: all 0.2s ease-out;
    }
    .form-control:focus ~ .floating-label,
    .form-control:not(:placeholder-shown) ~ .floating-label {
      top: 0;
      transform: translateY(-50%) scale(0.85);
      background: #0b1325; 
      border-radius: 4px;
      padding: 0 6px;
      color: var(--accent);
      left: 40px;
    }

    /* Password Toggle */
    .toggle-password {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 4px;
      transition: color 0.2s;
    }
    .toggle-password:hover { color: var(--text-main); }
    .toggle-password svg { width: 20px; height: 20px; }

    /* Submit Button (Premium Shimmer) */
    .btn-submit {
      width: 100%;
      padding: 16px;
      margin-top: 10px;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 1.05rem;
      font-weight: 600;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 25px rgba(56, 189, 248, 0.3);
    }
    .btn-submit:active { transform: translateY(0); }
    
    /* Shimmer Effect */
    .btn-submit::after {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 50%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      animation: shimmer 3s infinite;
    }
    @keyframes shimmer {
      100% { left: 200%; }
    }

    /* Alerts */
    .alert {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.9rem;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .alert svg { width: 20px; height: 20px; flex-shrink: 0; }

    /* Footer Links */
    .footer-links {
      margin-top: 32px;
      text-align: center;
      font-size: 0.9rem;
    }
    .footer-links a {
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .footer-links a:hover { color: var(--text-main); }
    .footer-links svg { width: 16px; height: 16px; }

  </style>
</head>
<body>

  <!-- Ambient Background -->
  <div class="ambient-orb orb-1"></div>
  <div class="ambient-orb orb-2"></div>

  <div class="login-wrapper">
    <div class="glass-card stagger-in">
      
      <div class="brand-header stagger-in">
        <div class="logo-container">
          <img src="../assets/logo/logo-cores.png" alt="Logo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%230f172a\'><path d=\'M12 2L2 22h20L12 2zm0 3.8l7.2 14.2H4.8L12 5.8z\'/></svg>'">
        </div>
        <h1>Sistemas do Portal</h1>
        <p>Acesso Seguro ao Organograma</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert stagger-in">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="" novalidate class="stagger-in">
        <?php if ($next): ?>
          <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
        <?php endif; ?>

        <!-- Campo Usuário -->
        <div class="form-group">
          <input type="text" name="username" id="username" class="form-control" placeholder=" " required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <label for="username" class="floating-label">E-mail ou Usuário</label>
        </div>

        <!-- Campo Senha -->
        <div class="form-group">
          <input type="password" name="password" id="password" class="form-control" placeholder=" " required>
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <label for="password" class="floating-label">Senha</label>
          
          <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar senha">
            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>

        <button type="submit" class="btn-submit">Acessar Organograma</button>
      </form>
      
      <!-- Criar Conta -->
      <div class="footer-links" style="margin-top:20px;">
        <a href="../register.php">Novo por aqui? Criar uma conta</a>
      </div>

    </div>

    <!-- Voltar ao Início -->
    <div class="footer-links stagger-in">
      <a href="../index.php">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Voltar para a Página Inicial
      </a>
    </div>
  </div>

  <script>
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');

    togglePassword.addEventListener('click', function () {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      if (type === 'text') {
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        `;
      } else {
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
    });

    window.addEventListener('load', () => {
      const u = document.getElementById('username');
      if(u && u.value === '') {
        setTimeout(() => u.focus(), 600);
      }
    });
  </script>
</body>
</html>
