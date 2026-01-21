<?php
/* admin/comments_moderation.php – Moderação de Comentários */
$requiredAccesses = ['Moderação de Comentarios'];
require_once 'check_access.php';

/* ——— PROCESSA AÇÕES DE APROVAR / INATIVAR ——— */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && in_array($action, ['aprovar', 'inativar'], true)) {
        $newStatus = $action === 'aprovar' ? 'aprovado' : 'rejeitado';
        $stmt = $pdo->prepare("UPDATE article_comments SET status = :st WHERE id = :id");
        $stmt->execute([':st' => $newStatus, ':id' => $id]);
    }
    header('Location: comments_moderation.php');
    exit;
}

/* ——— BUSCA TODOS OS COMENTÁRIOS ——— */
$comments = $pdo->query("
    SELECT
      c.id,
      c.noticia_id,
      n.titulo AS noticia,
      r.nome  AS leitor,
      c.comment,
      c.status,
      c.created_at
    FROM article_comments c
    JOIN noticias n ON n.id = c.noticia_id
    JOIN readers  r ON r.id = c.reader_id
    ORDER BY FIELD(c.status,'pendente','aprovado','rejeitado'), c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Moderação de Comentários';
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          Moderação de Comentários
        </h2>
      </div>
      <!-- Page title actions -->
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <button class="btn btn-secondary d-none d-sm-inline-block" onclick="location.reload()">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
            Atualizar
          </button>
          <button class="btn btn-secondary d-sm-none btn-icon" onclick="location.reload()">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <div class="row row-deck row-cards">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Lista de Comentários</h3>
          </div>
          <div class="table-responsive">
            <table id="tblComments" class="table table-vcenter card-table">
      <thead>
        <tr>
          <th class="text-center w-1">ID</th>
          <th>Artigo</th>
          <th>Leitor</th>
          <th>Comentário</th>
          <th>Data</th>
          <th class="text-center">Status</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($comments as $c):
          switch ($c['status']) {
            case 'aprovado':
              $badge = "<span class='badge bg-success'>Aprovado</span>"; break;
            case 'rejeitado':
              $badge = "<span class='badge bg-danger'>Rejeitado</span>"; break;
            default:
              $badge = "<span class='badge bg-warning text-dark'>Pendente</span>"; break;
          }
      ?>
        <tr>
          <td class="text-center"><?= $c['id'] ?></td>
          <td>
            <a href="https://ti.grupobarao.com.br/portal/blog_post.php?id=<?= $c['noticia_id'] ?>"
               target="_blank">
              <?= e($c['noticia']) ?>
            </a>
          </td>
          <td><?= e($c['leitor']) ?></td>
          <td><?= nl2br(e($c['comment'])) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
          <td class="text-center"><?= $badge ?></td>
          <td class="text-center">
            <div class="btn-list flex-nowrap">
              <?php if ($c['status'] === 'pendente'): ?>
                <form method="post" style="display:inline-block">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="aprovar">
                  <button class="btn btn-sm btn-outline-success" title="Aprovar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m5 12l5 5l10 -10" /></svg>
                  </button>
                </form>
                <form method="post" style="display:inline-block">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="inativar">
                  <button class="btn btn-sm btn-outline-danger" title="Rejeitar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m18 6l-12 12" /><path d="m6 6l12 12" /></svg>
                  </button>
                </form>
              <?php elseif ($c['status'] === 'aprovado'): ?>
                <form method="post" style="display:inline-block">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="inativar">
                  <button class="btn btn-sm btn-outline-danger" title="Rejeitar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m18 6l-12 12" /><path d="m6 6l12 12" /></svg>
                  </button>
                </form>
              <?php elseif ($c['status'] === 'rejeitado'): ?>
                <form method="post" style="display:inline-block">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="aprovar">
                  <button class="btn btn-sm btn-outline-success" title="Aprovar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m5 12l5 5l10 -10" /></svg>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJS = "
$(function(){
  $('#tblComments').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
    pageLength: 10,
    lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']],
    order: [[ 0, 'desc' ]],
    columnDefs: [
      { orderable: false, targets: [6] }
    ]
  });
});
";

$content = ob_get_clean();
require_once 'admin_layout.php';
