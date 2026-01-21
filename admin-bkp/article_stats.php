<?php
/* admin/article_stats.php – Estatísticas de Artigos */
$requiredAccesses = ['Visualizar Estatísticas'];
require_once 'check_access.php';

// Busca estatísticas por artigo
$sql = "
  SELECT
    n.id,
    n.titulo,
    COALESCE(v.total_views, 0)         AS total_views,
    COALESCE(l.total_likes, 0)         AS total_likes,
    COALESCE(c.approved_comments, 0)   AS approved_comments,
    COALESCE(c.pending_comments, 0)    AS pending_comments
  FROM noticias n

  LEFT JOIN (
    SELECT noticia_id, COUNT(*) AS total_views
      FROM article_views
     GROUP BY noticia_id
  ) v ON v.noticia_id = n.id

  LEFT JOIN (
    SELECT noticia_id, COUNT(*) AS total_likes
      FROM article_likes
     GROUP BY noticia_id
  ) l ON l.noticia_id = n.id

  LEFT JOIN (
    SELECT noticia_id,
           SUM(status = 'aprovado') AS approved_comments,
           SUM(status = 'pendente') AS pending_comments
      FROM article_comments
     GROUP BY noticia_id
  ) c ON c.noticia_id = n.id

  ORDER BY total_views DESC
";

$stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Estatísticas de Artigos';
ob_start();
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title"><?= $pageTitle ?></h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <button class="btn btn-secondary d-none d-sm-inline-block" onclick="location.reload()">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
              <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
            </svg>
            Atualizar
          </button>
          <button class="btn btn-secondary d-sm-none btn-icon" onclick="location.reload()">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
              <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Estatísticas dos Artigos</h3>
      </div>
      <div class="table-responsive">
        <table id="tblStats" class="table table-vcenter card-table">
      <thead>
        <tr>
          <th class="text-center w-1">ID</th>
          <th>Título</th>
          <th class="text-end">Visualizações</th>
          <th class="text-end">Curtidas</th>
          <th class="text-end">Coment. Aprov.</th>
          <th class="text-end">Coment. Pend.</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stats as $row): ?>
        <tr>
          <td class="text-center"><?= $row['id'] ?></td>
          <td>
            <a href="https://ti.grupobarao.com.br/portal/blog_post.php?id=<?= $row['id'] ?>"
               target="_blank">
              <?= e($row['titulo']) ?>
            </a>
          </td>
          <td class="text-end"><?= $row['total_views'] ?></td>
          <td class="text-end"><?= $row['total_likes'] ?></td>
          <td class="text-end"><?= $row['approved_comments'] ?></td>
          <td class="text-end"><?= $row['pending_comments'] ?></td>
        </tr>
      <?php endforeach; ?>
        </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$extraJS = "
$(function(){
  $('#tblStats').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
    pageLength: 10,
    lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']],
    order: [[2,'desc']] // ordena por visualizações
  });
});
";

$content = ob_get_clean();
require_once 'admin_layout.php';
