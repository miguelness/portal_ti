<?php
session_start();

// Mensagem de sucesso do report
$incidentMessage = $_SESSION['incident_success'] ?? null;
unset($_SESSION['incident_success']);

// Processa envio do formulário (simplificado)
if (isset($_POST['incident_submit'])) {
    // ... inserir no banco
    $_SESSION['incident_success'] = "Relatório enviado com sucesso!";
    header("Location: index.php");
    exit;
}

include_once 'admin/config.php';

// Pega links do menu e monta árvore
$links = $pdo->query("SELECT * FROM menu_links WHERE status='ativo' ORDER BY ordem")->fetchAll(PDO::FETCH_ASSOC);
function buildTree(array $elements, $parentId = 0) {
    $branch = [];
    foreach ($elements as $el) {
        if (($el['parent_id'] ?: 0) == $parentId) {
            $kids = buildTree($elements, $el['id']);
            if ($kids) $el['children'] = $kids;
            $branch[] = $el;
        }
    }
    return $branch;
}
$menuTree = buildTree($links);

// Notícias e alertas
$noticias    = $pdo->query("SELECT * FROM noticias WHERE categoria='Portal' AND status='ativo' ORDER BY data_publicacao DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
$alerts      = $pdo->query("SELECT * FROM alerts WHERE status='ativo' ORDER BY display_order, created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Portal TI • Grupo Barão</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Corporate font -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bs-body-bg: #f1f3f5;
      --bs-body-color: #212529;
      --sidebar-bg: #ffffff;
      --sidebar-border: #dee2e6;
    }
    body {
      font-family: 'Roboto', sans-serif;
      background: var(--bs-body-bg);
      color: var(--bs-body-color);
    }
    .sidebar {
      min-height: 100vh;
      background: var(--sidebar-bg);
      border-right: 1px solid var(--sidebar-border);
      padding-top: 1rem;
    }
    .sidebar .nav-link {
      color: #495057;
    }
    .sidebar .nav-link:hover {
      background: #e9ecef;
    }
    .card-dashboard {
      border: none;
      border-radius: .5rem;
      box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
      transition: transform .15s;
    }
    .card-dashboard:hover {
      transform: translateY(-2px);
    }
    .toast-container {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 1100;
    }
  </style>
</head>
<body>

  <!-- Sidebar + Conteúdo -->
  <div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar col-auto px-0">
      <a class="navbar-brand d-block text-center py-3 mb-3 border-bottom" href="#">
        <img src="assets/img/avatars/logo-cores.png" alt="Barão" style="height:40px;">
      </a>
      <ul class="nav nav-pills flex-column">
        <?php foreach ($menuTree as $item): ?>
          <?php if (empty($item['children'])): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= $item['url'] ?: '#' ?>" target="<?= $item['target_blank'] ? '_blank':'' ?>">
                <i class="<?= $item['icone'] ?>"></i> <?= htmlspecialchars($item['titulo']) ?>
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#menu<?= $item['id'] ?>" role="button" aria-expanded="false">
                <i class="<?= $item['icone'] ?>"></i> <?= htmlspecialchars($item['titulo']) ?>
                <i class="bi bi-chevron-down float-end"></i>
              </a>
              <div class="collapse" id="menu<?= $item['id'] ?>">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                  <?php foreach ($item['children'] as $ch): ?>
                    <li>
                      <a class="nav-link ps-4" href="<?= $ch['url'] ?: '#' ?>" target="<?= $ch['target_blank'] ? '_blank':'' ?>">
                        <i class="<?= $ch['icone'] ?>"></i> <?= htmlspecialchars($ch['titulo']) ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </nav>

    <!-- Main -->
    <main class="flex-grow-1 p-4">
      <!-- Toast de sucesso de incident -->
      <div class="toast-container">
        <?php if ($incidentMessage): ?>
          <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
            <div class="d-flex">
              <div class="toast-body">
                <?= htmlspecialchars($incidentMessage) ?>
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Alertas (Toasts) -->
      <div class="toast-container top-0 end-0 p-3">
        <?php foreach ($alerts as $a): ?>
          <div class="toast show mb-2" role="alert">
            <div class="toast-header">
              <strong class="me-auto"><?= htmlspecialchars($a['title']) ?></strong>
              <small><?= date('d/m/Y', strtotime($a['created_at'])) ?></small>
              <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
              <?= htmlspecialchars($a['message']) ?>
              <?php if (!empty($a['file_path'])): ?>
                <hr>
                <a href="uploads_alertas/<?= htmlspecialchars($a['file_path']) ?>" class="link-primary">
                  <i class="bi bi-download"></i> Baixar anexo
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Feed de Notícias -->
      <h2 class="mb-4">Últimas Notícias</h2>
      <div class="row g-4">
        <?php if ($noticias): foreach ($noticias as $n): ?>
          <div class="col-12 col-md-6 col-lg-3">
            <div class="card card-dashboard h-100">
              <?php if ($n['imagem']): ?>
                <img src="<?= htmlspecialchars($n['imagem']) ?>" class="card-img-top" alt="">
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= htmlspecialchars($n['titulo']) ?></h5>
                <p class="text-muted small mb-2"><?= date('d/m/Y', strtotime($n['data_publicacao'])) ?></p>
                <p class="card-text flex-grow-1"><?= mb_strimwidth(strip_tags($n['conteudo']),0,100,'...') ?></p>
                <a href="blog_post.php?id=<?= $n['id'] ?>&from=index" target="_blank" class="btn btn-primary btn-sm mt-2">Ler Mais</a>
              </div>
            </div>
          </div>
        <?php endforeach; else: ?>
          <p>Nenhuma notícia disponível.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Modal de Report -->
  <?php include 'report_modal.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
