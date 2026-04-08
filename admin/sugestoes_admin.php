<?php
/*
 * admin/sugestoes_admin.php
 * Gestão de Sugestões / Ouvidoria
 */
$requiredAccess = 'Sugestões';
require_once 'check_access.php'; // Verificação de acesso unificada (caso tenha 'Sugestões', 'Super Administrador' será herdado)

$pageTitle = 'Caixa de Sugestões';

// Incluir o layout do admin
ob_start();

$msg = '';

// Processa ações (excluir ou marcar como lida)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($_GET['action'] == 'delete') {
        $pdo->prepare("DELETE FROM sugestoes WHERE id = ?")->execute([$id]);
        $msg = "Sugestão deletada com sucesso!";
    } elseif ($_GET['action'] == 'read') {
        $pdo->prepare("UPDATE sugestoes SET lida = 1 WHERE id = ?")->execute([$id]);
        $msg = "Sugestão marcada como lida.";
    } elseif ($_GET['action'] == 'unread') {
        $pdo->prepare("UPDATE sugestoes SET lida = 0 WHERE id = ?")->execute([$id]);
        $msg = "Sugestão marcada como não lida.";
    }
}

// Busca todas as sugestões
$stmt = $pdo->query("SELECT * FROM sugestoes ORDER BY criado_em DESC");
$sugestoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .bg-unread { background-color: rgba(32, 107, 196, 0.05); font-weight: 500;}
    .message-cell { max-width: 400px; white-space: normal; }
</style>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          <i class="ti ti-message-2-share me-2 text-primary"></i> Caixa de Sugestões
        </h2>
      </div>
    </div>
  </div>
</div>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
        
        <?php if($msg): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-check icon alert-icon"></i></div>
                    <div><?= htmlspecialchars($msg) ?></div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        <?php endif; ?>

        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sugestões Recebidas (<?= count($sugestoes) ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="tabelaSugestoes" class="table table-vcenter card-table text-nowrap">
                            <thead>
                                <tr>
                                    <th class="w-1 text-center">Status</th>
                                    <th>Data</th>
                                    <th>Nome</th>
                                    <th>Mensagem</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($sugestoes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Ainda não há nenhuma sugestão.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sugestoes as $s): ?>
                                        <tr class="<?= $s['lida'] ? '' : 'bg-unread' ?>">
                                            <td class="text-center">
                                                <?php if($s['lida']): ?>
                                                    <span class="badge bg-success-lt" title="Lida"><i class="ti ti-check"></i></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-lt" title="Não Lida"><i class="ti ti-mail"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-secondary"><?= date('d/m/Y H:i', strtotime($s['criado_em'])) ?></td>
                                            <td>
                                                <?= !empty($s['nome']) ? htmlspecialchars($s['nome']) : '<span class="text-muted fst-italic">Anônimo</span>' ?>
                                            </td>
                                            <td class="message-cell text-wrap text-break">
                                                <?= nl2br(htmlspecialchars($s['mensagem'])) ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-list flex-nowrap justify-content-center">
                                                    <?php if(!$s['lida']): ?>
                                                        <a href="?action=read&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="ti ti-eye me-1"></i> Lida
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=unread&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Marcar como não lida">
                                                            <i class="ti ti-eye-off"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja apagar esta sugestão?')">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
$(document).ready(function() {
  $('#tabelaSugestoes').DataTable({
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
    },
    pageLength: 25,
    order: [[ 1, 'desc' ]],
    columnDefs: [
      { orderable: false, targets: [4] }
    ]
  });
});
";

$content = ob_get_clean();
require_once 'admin_layout.php';
?>
