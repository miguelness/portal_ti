<?php
/* -------------------------------------------------
 *  admin/links_admin.php  –  Gestão de Menu
 * -------------------------------------------------*/
$requiredAccess = 'Gestão de Menu';
require_once 'check_access.php';
require_once 'config.php';

/* -------- carrega dados ------------------------ */
$sql = "SELECT ml.*, mp.titulo parent_titulo
          FROM menu_links ml
          LEFT JOIN menu_links mp ON mp.id = ml.parent_id
      ORDER BY ml.parent_id , ml.ordem";
$links = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function e($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }

$pageTitle = 'Gerenciar Links do Menu';
ob_start(); ?>
<!-- =======================  CONTEÚDO  ======================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="page-title m-0"><?= $pageTitle ?></h2>
  <a href="link_adicionar.php" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>Novo&nbsp;Item
  </a>
</div>

<div class="card">
  <div class="card-body p-0">
    <table id="tblLinks" class="table card-table table-vcenter mb-0 w-100">
      <thead>
        <tr>
          <th class="text-center w-1">ID</th>
          <th>Título</th>
          <th>Descrição</th>
          <th>Cor</th>
          <th>Pertence a</th>
          <th class="text-center">Alvo</th>
          <th class="text-center">Status</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($links as $l): ?>
          <?php
            $swatch = $l['cor'] ? "<span class='badge me-1' style='background:{$l['cor']}'>&nbsp;</span>" : '';
            $parent = $l['parent_titulo'] ?: "<span class='badge bg-secondary'>Raiz</span>";
            $target = $l['target_blank'] ? "<i class='ti ti-external-link'></i>" : "<i class='ti ti-link'></i>";
            $status = $l['status']==='ativo'
                      ? "<span class='badge bg-success'>Ativo</span>"
                      : "<span class='badge bg-danger'>Inativo</span>";
          ?>
          <tr>
            <td class="text-center"><?= $l['id'] ?></td>
            <td><strong><?= e($l['titulo']) ?></strong></td>
            <td><?= e($l['descricao']) ?></td>
            <td><?= $swatch ?><small><?= $l['cor'] ?></small></td>
            <td><?= $parent ?></td>
            <td class="text-center"><?= $target ?></td>
            <td class="text-center"><?= $status ?></td>
            <td class="text-center">
              <a href="link_editar.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i>
              </a>
              <a href="link_excluir.php?id=<?= $l['id'] ?>"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Excluir este item?')">
                 <i class="ti ti-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============  assets DataTables (CDN)  ============ -->
<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function(){
  $('#tblLinks').DataTable({
    language : {url:'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'},
    pageLength:10,
    lengthMenu:[[10,25,50,-1],[10,25,50,'Todos']],
    /* ————————— Dom Bootstrap / Tabler ————————— */
    dom:
      "<'row g-2 align-items-center px-3 pt-3'<'col-md-auto'l><'col-md-auto ms-auto'f>>" +
      "t" +
      "<'row g-2 align-items-center px-3 pb-3'<'col-md-auto'i><'col-md-auto ms-auto'p>>",
    drawCallback : function(){
      $('select[name="tblLinks_length"]').addClass('form-select form-select-sm');
      $('div.dataTables_filter input').addClass('form-control form-control-sm')
                                      .attr('placeholder','Buscar…');
    }
  });
});
</script>
<?php
$content = ob_get_clean();
include '../template/layout.php';
?>
