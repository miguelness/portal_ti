<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'config.php';

// Evita redeclarar a função se ela já existir
if (!function_exists('hasAccess')) {
    function hasAccess($access, $user_accesses) {
        if (in_array('Super Administrador', $user_accesses)) {
            return true;
        }
        return in_array($access, $user_accesses);
    }
}

// Exemplo: obtendo os acessos do usuário
$user_id = $_SESSION['user_id'] ?? 0;
$sql = "SELECT a.access_name FROM user_access ua JOIN accesses a ON ua.access_id = a.id WHERE ua.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">TI Grupo Barão</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                 <!-- Cada link é relativo à pasta admin -->
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <?php if (hasAccess('Gestão de Menu', $user_accesses)): ?>
          <li class="nav-item">
            <a class="nav-link" href="links_admin.php">Menu</a>
          </li>
        <?php endif; ?>
        <?php if (hasAccess('Feeds', $user_accesses) || hasAccess('Feeds RH', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="admin_postagem.php">Feeds</a>
        </li>
      <?php endif; ?>
      <?php if (hasAccess('Feeds', $user_accesses) || hasAccess('Feeds RH', $user_accesses)): ?>
        <li class="nav-item">
          <a class="nav-link" href="comments_moderation.php">Moderar Comentários</a>
        </li>
      <?php endif; ?>


      
        <?php if (hasAccess('Reports', $user_accesses)): ?>
          <li class="nav-item">
            <a class="nav-link" href="reports_admin.php">Reports</a>
          </li>
        <?php endif; ?>
        
        <?php if (hasAccess('Super Administrador', $user_accesses) || hasAccess('Sugestões', $user_accesses) || hasAccess('Gestão de Usuários', $user_accesses)): ?>
          <li class="nav-item">
            <a class="nav-link" href="sugestoes_admin.php">Sugestões</a>
          </li>
        <?php endif; ?>
        
        <!-- Menu RH -->
        <?php if (hasAccess('Documentos RH', $user_accesses) || hasAccess('Gestão de Colaboradores', $user_accesses) || hasAccess('Super Administrador', $user_accesses)): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="rhDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-users"></i> RH
            </a>
            <ul class="dropdown-menu" aria-labelledby="rhDropdown">
              <?php if (hasAccess('Documentos RH', $user_accesses)): ?>
                <li><a class="dropdown-item" href="rh_documents_admin.php"><i class="fas fa-file-alt"></i> Documentos</a></li>
              <?php endif; ?>
              <?php if (hasAccess('Gestão de Colaboradores', $user_accesses) || hasAccess('Super Administrador', $user_accesses)): ?>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Colaboradores</h6></li>
                <li><a class="dropdown-item" href="colaboradores.php"><i class="fas fa-users"></i> Gerenciar</a></li>
                <li><a class="dropdown-item" href="organograma_admin.php"><i class="fas fa-sitemap"></i> Organograma</a></li>
                <li><a class="dropdown-item" href="colaboradores_import.php"><i class="fas fa-file-import"></i> Importar Planilha</a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>
        <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
          <li class="nav-item">
            <a class="nav-link" href="users_admin.php">Usuários</a>
          </li>
        <?php endif; ?>
        <?php if (hasAccess('Acessos', $user_accesses)): ?>
          <li class="nav-item">
            <a class="nav-link" href="alerts_admin.php">Alertas</a>
          </li>
        <?php endif; ?>
        <?php if (hasAccess('Acessos', $user_accesses)): ?>
          <li class="nav-item">
            <a class="nav-link" href="accesses_admin.php">Usuários Acessos</a>
          </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Sair</a>
        </li>
            </ul>
        </div>
    </div>
</nav>
