<?php
/* template/topbar.php
 * – barra superior (Tabler)
 * – tema claro/escuro + recolher/expandir sidebar (persistentes)
 */
if (session_status() === PHP_SESSION_NONE)  session_start();

/* Caminho do config — ajuste se necessário */
require_once 'config.php';

$nomeUser  = $_SESSION['nome']  ?? 'Usuário';
$cargoUser = $_SESSION['cargo'] ?? (($_SESSION['acesso'] ?? 3) <= 2 ? 'Admin' : 'User');
?>
<header class="navbar navbar-expand-md d-print-none">
  <div class="container-fluid">

    <!-- ① – toggler mobile (mostra/esconde sidebar em telas < lg) -->
    <button class="navbar-toggler d-lg-none me-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sidebar-menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- ② – ícone hambúrguer desktop (grava estado em localStorage) -->
    <a href="#" id="sidebarToggle"
       class="navbar-brand d-none d-lg-inline-block pe-0 me-3">
      <i class="ti ti-menu-2"></i>
    </a>

    <!-- links opcionais do top-bar
    <ul class="navbar-nav d-none d-md-flex me-auto">
      <li class="nav-item"><a class="nav-link" href="#"><i class="ti ti-chart-bar me-1"></i>Relatórios</a></li>
      <li class="nav-item"><a class="nav-link" href="#"><i class="ti ti-calendar me-1"></i>Agenda</a></li>
    </ul>-->

    <!-- ③ – lado direito -->
    <div class="navbar-nav flex-row order-md-last align-items-center">

      <!-- commutador de tema -->
      <button class="nav-link px-0 me-2 border-0 bg-transparent"
              id="themeToggle"  title="Alternar tema">
        <i id="themeIcon" class="ti"></i>
      </button>

      <!-- avatar + nome -->
      <div class="nav-item dropdown">
        <a href="#" class="nav-link d-flex align-items-center p-0" data-bs-toggle="dropdown">
          <span  class="avatar avatar-sm me-2"
                 style="background-image:url(https://tabler.io/demo/avatars/avatar.jpg)"></span>
          <span  class="d-none d-md-block lh-1">
            <div><?= htmlspecialchars($nomeUser) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($cargoUser) ?></div>
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
          <a class="dropdown-item" href="#"><i class="ti ti-user-circle me-2"></i>Perfil</a>
          <a class="dropdown-item" href="logout.php"><i class="ti ti-logout me-2"></i>Sair</a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ───── JS tema + sidebar ───── -->
<script>
(() => {
  /* ---------- Tema (claro/escuro) ---------- */
  const html   = document.documentElement;
  const icon   = document.getElementById('themeIcon');
  const toggle = document.getElementById('themeToggle');
  const key    = 'theme';                              // ‘light’ | ‘dark’

  const apply  = mode => {
    html.setAttribute('data-bs-theme', mode);
    icon.className = 'ti ' + (mode === 'dark' ? 'ti-moon' : 'ti-sun');
  };

  apply(localStorage.getItem(key) || 'light');

  toggle.addEventListener('click', () => {
    const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem(key, next);
    apply(next);
  });

  /* ---------- Sidebar (colapsa/expande) ---------- */
  const body     = document.body;
  const sbKey    = 'sidebarCollapsed';                 // ‘1’ | ‘0’
  const sbToggle = document.getElementById('sidebarToggle');

  if (localStorage.getItem(sbKey) === '1') body.classList.add('sidebar-collapsed');

  sbToggle.addEventListener('click', e => {
    e.preventDefault();
    body.classList.toggle('sidebar-collapsed');
    localStorage.setItem(sbKey, body.classList.contains('sidebar-collapsed') ? '1' : '0');
  });
})();
</script>
