<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once realpath(__DIR__ . '/../admin/config.php');

if (!function_exists('hasAccess')) {
    function hasAccess($access, $ua){ return in_array('Super Administrador',$ua)||in_array($access,$ua);}
}

$user_accesses = [];
try{
  $uid = $_SESSION['user_id']??0;
  $stmt=$pdo->prepare("SELECT a.access_name FROM user_access ua JOIN accesses a ON a.id=ua.access_id WHERE ua.user_id=:uid");
  $stmt->execute([':uid'=>$uid]);
  $user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);
}catch(Throwable $e){}

?>
<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <div class="sidebar-header text-center">
      <img src="../assets/img/logo-branco.png" alt="Logo" style="width:120px">
    </div>

    <button class="navbar-toggler d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="sidebar-menu">
      <ul class="navbar-nav pt-lg-3">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="ti ti-home me-2"></i>Home</a></li>

        <?php if (hasAccess('Gestão de Menu',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="links_admin.php"><i class="ti ti-list-details me-2"></i>Menu</a></li>
        <?php endif; ?>

        <?php if (hasAccess('Feeds',$user_accesses)||hasAccess('Feeds RH',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="admin_postagem.php"><i class="ti ti-rss me-2"></i>Feeds</a></li>
        <?php endif; ?>
        
        <?php if (hasAccess('Moderar Comentários',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="comments_moderation.php"><i class="ti ti-rss me-2"></i>Moderar Comentários</a></li>
        <?php endif; ?>

        <?php if (hasAccess('Estatisticas de Artigos',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="article_stats.php"><i class="ti ti-rss me-2"></i>Estatisticas de Artigos</a></li>
        <?php endif; ?>

        <?php if (hasAccess('Reports',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="reports_admin.php"><i class="ti ti-chart-bar me-2"></i>Reports</a></li>
        <?php endif; ?>

        <?php if (hasAccess('Documentos RH',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="rh_documents_admin.php"><i class="ti ti-file-description me-2"></i>RH Documentos</a></li>
        <?php endif; ?>

        <?php if (hasAccess('Gestão de Usuários',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="users_admin.php"><i class="ti ti-users me-2"></i>Usuários</a></li>
        <?php endif; ?>

        <?php if (hasAccess('Acessos',$user_accesses)): ?>
        <li class="nav-item"><a class="nav-link" href="alerts_admin.php"><i class="ti ti-alert-circle me-2"></i>Alertas</a></li>
        <li class="nav-item"><a class="nav-link" href="accesses_admin.php"><i class="ti ti-lock-access me-2"></i>Usuários&nbsp;Acessos</a></li>
        <?php endif; ?>

        <li class="nav-item mt-3"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-2"></i>Sair</a></li>
      </ul>
    </div>
  </div>
</aside>
