<?php
/* ------------------------------------------------------------------
 * admin/link_form.php
 *  – devolve (echo) somente o <form> pronto para ser usado dentro
 *    de um modal  •  se NÃO receber id   ->  “Novo item”
 *                           id numérico ->  “Editar item”
 * -----------------------------------------------------------------*/
require_once 'config.php';
require_once 'check_access.php';

$id   = $_GET['id'] ?? null;          // se vier, estamos editando
$data = [
  'titulo'       => '',
  'descricao'    => '',
  'url'          => '',
  'target_blank' => 0,
  'cor'          => '#2590cf',
  'tamanho'      => 'col-lg-3 col-xl-3',
  'icone'        => '',
  'ordem'        => 0,
  'status'       => 'ativo',
  'parent_id'    => '',
  'modal_class'  => 'modal-85'
];

$possibleParents = $pdo->query(
  "SELECT id, titulo FROM menu_links
    WHERE parent_id IS NULL OR parent_id = 0
 ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- se for edição carrega dados ------------------------ */
if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM menu_links WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) { http_response_code(404); exit('Item não encontrado'); }
  $data = $row;
}

/* ---------- form ------------------------------------------------ */
function e($v){ return htmlspecialchars($v,ENT_QUOTES,'UTF-8'); }

?>
<form id="frmLink" method="post">
  <?php if($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif;?>

  <!-- título / descrição -->
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label">Título</label>
      <input name="titulo" class="form-control" required value="<?= e($data['titulo'])?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Descrição</label>
      <input name="descricao" class="form-control" required value="<?= e($data['descricao'])?>">
    </div>
  </div>

  <!-- url / nova aba -->
  <div class="row g-2 mt-2">
    <div class="col">
      <label class="form-label">URL</label>
      <input name="url" class="form-control" value="<?= e($data['url'])?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" name="target_blank"
               <?= $data['target_blank']?'checked':''?> id="cbblank">
        <label class="form-check-label" for="cbblank">Nova aba</label>
      </div>
    </div>
  </div>

  <!-- cor -->
  <div class="mt-3">
    <label class="form-label">Cor</label>
    <?php
      $cores = ['#2590cf'=>'Azul','#ffcb18'=>'Amarelo','rgb(96,54,119)'=>'Roxo',
                'orangered'=>'Laranja','black'=>'Preto'];
    ?>
    <select name="cor" class="form-select">
      <?php foreach($cores as $c=>$n): ?>
        <option value="<?= $c ?>" <?= $c==$data['cor']?'selected':''?> style="background:<?= $c?>;color:#fff">
          <?= $n ?>
        </option>
      <?php endforeach;?>
      <?php if(!in_array($data['cor'],array_keys($cores))):?>
        <option value="<?= e($data['cor'])?>" selected>Outro: <?= e($data['cor'])?></option>
      <?php endif;?>
    </select>
  </div>

  <!-- tamanho botão / modal -->
  <div class="row g-2 mt-2">
    <div class="col">
      <label class="form-label">Tamanho botão</label>
      <?php $tam=$data['tamanho']; ?>
      <select name="tamanho" class="form-select">
        <option value="col-lg-2 col-xl-2" <?= $tam=='col-lg-2 col-xl-2'?'selected':''?>>Pequeno</option>
        <option value="col-lg-3 col-xl-3" <?= $tam=='col-lg-3 col-xl-3'?'selected':''?>>Médio</option>
        <option value="col-lg-4 col-xl-4" <?= $tam=='col-lg-4 col-xl-4'?'selected':''?>>Grande</option>
      </select>
    </div>
    <div class="col">
      <label class="form-label">Tamanho modal</label>
      <?php $mc=$data['modal_class']; ?>
      <select name="modal_class" class="form-select">
        <option value=""         <?= $mc==''?'selected':''?>>Default</option>
        <option value="modal-lg" <?= $mc=='modal-lg'?'selected':''?>>modal-lg</option>
        <option value="modal-xl" <?= $mc=='modal-xl'?'selected':''?>>modal-xl</option>
        <option value="modal-85" <?= $mc=='modal-85'?'selected':''?>>85%</option>
      </select>
    </div>
  </div>

  <!-- ícone / ordem -->
  <div class="row g-2 mt-2">
    <div class="col">
      <label class="form-label">Ícone (classe FA)</label>
      <input name="icone" class="form-control" value="<?= e($data['icone'])?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Ordem</label>
      <input name="ordem" type="number" class="form-control" value="<?= e($data['ordem'])?>">
    </div>
  </div>

  <!-- pai / status -->
  <div class="row g-2 mt-2">
    <div class="col">
      <label class="form-label">Sub-menu de</label>
      <select name="parent_id" class="form-select">
        <option value="">Raiz</option>
        <?php foreach($possibleParents as $p):?>
          <option value="<?= $p['id']?>" <?= $p['id']==$data['parent_id']?'selected':''?>>
            <?= e($p['titulo'])?>
          </option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="ativo"   <?= $data['status']=='ativo'?'selected':''?>>Ativo</option>
        <option value="inativo" <?= $data['status']=='inativo'?'selected':''?>>Inativo</option>
      </select>
    </div>
  </div>
</form>
