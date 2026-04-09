<?php
/*
 * admin/menuTabler.php
 * Sidebar Tabler que respeita os acessos do usuário.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once  'config.php';   // ajusta caminho se necessário

/* -------------------------------------------------
 * helpers
 * -------------------------------------------------*/
if (!function_exists('hasAccess')) {
    function hasAccess(string $access, array $user_accesses): bool
    {
        return in_array('Super Administrador', $user_accesses) || in_array($access, $user_accesses);
    }
}

/* carrega acessos do usuário ------------------------------------------- */
$user_id = $_SESSION['user_id'] ?? 0;
$user_accesses = [];

try {
    $stmt = $pdo->prepare(
        "SELECT a.access_name
           FROM user_access ua
           JOIN accesses a ON ua.access_id = a.id
          WHERE ua.user_id = :uid"
    );
    $stmt->execute([':uid' => $user_id]);
    $user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    // se der erro, deixa o array vazio (menu mínimo)
}
?>

<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <div class="sidebar-header text-center">
      <img src="../assets/img/logo-branco.png" alt="Logo" style="width:120px">
    </div>

    <!-- toggler mobile -->
    <button class="navbar-toggler d-lg-none mb-2" type="button" data-toggle="sidebar">
      <i class="ti ti-menu-2"></i>
    </button>

    <div class="collapse navbar-collapse" id="sidebar-menu">
      <ul class="navbar-nav pt-lg-3">

        <!-- Home -->
        <li class="nav-item">
          <a class="nav-link" href="index.php">
            <i class="ti ti-home me-2"></i>Home
          </a>
        </li>

        <!-- Gestão de Menu -->
        <?php if (hasAccess('Gestão de Menu', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="links_admin.php">
            <i class="ti ti-list-details me-2"></i>Menu
          </a>
        </li>
        <?php endif; ?>

        <!-- Feeds -->
        <?php if (hasAccess('Feeds', $user_accesses) || hasAccess('Feeds RH', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="admin_postagem.php">
            <i class="ti ti-rss me-2"></i>Feeds
          </a>
        </li>
        <?php endif; ?>

        <!-- Moderar -->
        <?php if (hasAccess('Feeds', $user_accesses) || hasAccess('Feeds RH', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="comments_moderation.php">
            <i class="ti ti-rss me-2"></i>Moderar Comentários
          </a>
        </li>
        <?php endif; ?>

        <!-- Reports -->
        <?php if (hasAccess('Reports', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="reports_admin.php">
            <i class="ti ti-chart-bar me-2"></i>Reports
          </a>
        </li>
        <?php endif; ?>

        <!-- Menu RH -->
        <?php if (hasAccess('Documentos RH', $user_accesses) || hasAccess('Colaboradores', $user_accesses) || hasAccess('Gestão de Colaboradores', $user_accesses) || hasAccess('Super Administrador', $user_accesses)): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="rhDropdownTabler" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-users me-2"></i>RH
          </a>
          <ul class="dropdown-menu" aria-labelledby="rhDropdownTabler">
            <?php if (hasAccess('Documentos RH', $user_accesses)): ?>
            <li><a class="dropdown-item" href="rh_documents_admin.php"><i class="ti ti-file-description me-2"></i>Documentos</a></li>
            <?php endif; ?>
            <?php if (hasAccess('Colaboradores', $user_accesses) || hasAccess('Gestão de Colaboradores', $user_accesses) || hasAccess('Super Administrador', $user_accesses)): ?>
            <?php if (hasAccess('Documentos RH', $user_accesses)): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
            <li><h6 class="dropdown-header">Colaboradores</h6></li>
            <li><a class="dropdown-item" href="colaboradores.php"><i class="ti ti-users me-2"></i>Gerenciar</a></li>
            <li><a class="dropdown-item" href="organograma_admin.php"><i class="ti ti-hierarchy-2 me-2"></i>Organograma</a></li>
            <li><a class="dropdown-item" href="colaboradores_import.php"><i class="ti ti-file-import me-2"></i>Importar Planilha</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Usuários -->
        <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="users_admin.php">
            <i class="ti ti-users me-2"></i>Usuários
          </a>
        </li>
        <?php endif; ?>

        <!-- Alertas -->
        <?php if (hasAccess('Acessos', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="alerts_admin.php">
            <i class="ti ti-alert-circle me-2"></i>Alertas
          </a>
        </li>
        <?php endif; ?>

        <!-- Usuários Acessos -->
        <?php if (hasAccess('Acessos', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="accesses_admin.php">
            <i class="ti ti-lock-access me-2"></i>Usuários&nbsp;Acessos
          </a>
        </li>
        <?php endif; ?>

        <!-- Monitoramento -->
        <?php if (hasAccess('Super Administrador', $user_accesses)): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="monitorDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-activity me-2"></i>Monitoramento
          </a>
          <ul class="dropdown-menu" aria-labelledby="monitorDropdown">
            <li><a class="dropdown-item" href="servidores_admin.php"><i class="ti ti-settings me-2"></i>Gestão de Links</a></li>
            <li><a class="dropdown-item" href="status_servidores_admin.php" target="_blank"><i class="ti ti-device-heart-monitor me-2"></i>Status Completo (TI)</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="agendamentos_admin.php">
            <i class="ti ti-clock-play me-2"></i>Agendamentos (Cron)
          </a>
        </li>
        <?php endif; ?>

        <!-- Sair -->

        <li class="nav-item mt-3">
          <a class="nav-link" href="logout.php">
            <i class="ti ti-logout me-2"></i>Sair
          </a>
        </li>

      </ul>
    </div>
  </div>
</aside>
