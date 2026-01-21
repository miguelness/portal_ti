<?php
/* Layout base Tabler.  Usado por todas as páginas.                */
/* Espera encontrar:                                               *
 *   $pageTitle  –  título da aba                                  *
 *   $content    –  HTML da página (ob_start → ob_get_clean)       */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once realpath(__DIR__ . '/../admin/config.php');
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($pageTitle ?? 'Painel') ?></title>

  <!-- Tabler core -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
  <!-- Tabler icons webfont -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <!-- DataTables (versão usada pelo Tabler) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/libs/datatables/datatables.min.css">
  <!-- Arquivo de estilos do projeto (opcional) -->
  <link rel="stylesheet" href="../assets/css/app.css">
  <!-- Tabler / Bootstrap JS já carregados acima -->
  <script src="../template/js/app.js"></script>   <!-- ajuste o caminho se preciso -->
</head>
<body class="layout-fluid">
  <div class="page">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="page-wrapper">
      <?php include __DIR__ . '/topbar.php'; ?>

      <div class="page-body">
        <div class="container-xl">
          <?= $content ?>
        </div>
      </div>

      <?php include __DIR__ . '/footer.php'; ?>
    </div>
  </div>

  <!-- JS Tabler -->
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
  <!-- DataTables -->
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/libs/jquery/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/libs/datatables/datatables.min.js"></script>

  <!-- Toggle tema -->
  <script>
    const html = document.documentElement,
          btn  = document.getElementById('themeToggle'),
          ico  = document.getElementById('themeIcon'),
          saved= localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', saved);
    if (saved==='dark') ico.classList.replace('ti-sun','ti-moon');
    btn?.addEventListener('click', () => {
      const next = html.getAttribute('data-bs-theme')==='dark' ? 'light':'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem('theme', next);
      ico.classList.replace(next==='dark' ? 'ti-sun':'ti-moon',
                            next==='dark' ? 'ti-moon':'ti-sun');
    });
  </script>
</body>
</html>
