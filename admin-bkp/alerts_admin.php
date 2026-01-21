<?php
$requiredAccess = 'Alertas';
require_once 'check_access.php';

$pageTitle = 'Gerenciar Alertas';

// Consulta todos os alertas ordenando pelo display_order (ascendente) e depois pela data
$sql = "SELECT * FROM alerts ORDER BY display_order ASC, created_at DESC";
$stmt = $pdo->query($sql);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Administração
                    </div>
                    <h2 class="page-title">
                        <i class="ti ti-bell me-2"></i>
                        Gerenciar Alertas
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="alert_add.php" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus"></i>
                            Novo Alerta
                        </a>
                        <a href="alert_add.php" class="btn btn-primary d-sm-none btn-icon">
                            <i class="ti ti-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Lista de Alertas</h3>
                        </div>
                        <div class="table-responsive">
                            <table id="tabelaAlerts" class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">ID</th>
                                        <th>Título</th>
                                        <th>Mensagem</th>
                                        <th>Imagem</th>
                                        <th>Arquivo</th>
                                        <th class="w-1">Ordem</th>
                                        <th class="w-1">Status</th>
                                        <th class="w-1">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($alerts) > 0): ?>
                                        <?php foreach($alerts as $alert): ?>
                                        <tr>
                                            <td class="text-muted"><?= $alert['id'] ?></td>
                                            <td>
                                                <div class="font-weight-medium"><?= htmlspecialchars($alert['title']) ?></div>
                                            </td>
                                            <td class="text-muted">
                                                <?= htmlspecialchars(substr($alert['message'], 0, 50)) ?>
                                                <?= strlen($alert['message']) > 50 ? '...' : '' ?>
                                            </td>
                                            <td>
                                                <?php if($alert['image']): ?>
                                                    <span class="avatar" style="background-image: url('../uploads_alertas/<?= htmlspecialchars($alert['image']) ?>')"></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($alert['file_path']): ?>
                                                    <a href="../uploads_alertas/<?= htmlspecialchars($alert['file_path']) ?>" 
                                                       download class="btn btn-sm btn-outline-primary">
                                                        <i class="ti ti-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-blue"><?= $alert['display_order'] ?></span>
                                            </td>
                                            <td>
                                                <?php if($alert['status'] === 'ativo'): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <a href="alert_edit.php?id=<?= $alert['id'] ?>" 
                                                       class="btn btn-white btn-sm" 
                                                       title="Editar alerta">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <a href="alert_delete.php?id=<?= $alert['id'] ?>" 
                                                       class="btn btn-white btn-sm text-danger" 
                                                       title="Excluir alerta"
                                                       onclick="return confirm('Tem certeza que deseja excluir este alerta?');">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                <div class="empty">
                                                    <div class="empty-icon">
                                                        <i class="ti ti-bell-off"></i>
                                                    </div>
                                                    <p class="empty-title">Nenhum alerta cadastrado</p>
                                                    <p class="empty-subtitle text-muted">
                                                        Comece criando seu primeiro alerta
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

// JavaScript adicional para DataTables
$extraJS = '
<script>
$(document).ready(function(){
    $("#tabelaAlerts").DataTable({
        language: { 
            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" 
        },
        pageLength: 10,
        order: [[5, "asc"]],
        columnDefs: [
            { orderable: false, targets: [3, 4, 7] }
        ]
    });
});
</script>
';

include 'admin_layout.php';
