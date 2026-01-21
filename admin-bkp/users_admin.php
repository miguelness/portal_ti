<?php
$requiredAccess = 'Gestão de Usuários';
require_once 'check_access.php';

$pageTitle = 'Gerenciar Usuários';

// Consulta todos os usuários do banco (ordenado por username)
$sql = "SELECT * FROM users ORDER BY username ASC";
$stmt = $pdo->query($sql);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <i class="ti ti-users me-2"></i>
                        Gerenciar Usuários
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="user_adicionar.php" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus"></i>
                            Novo Usuário
                        </a>
                        <a href="user_adicionar.php" class="btn btn-primary d-sm-none btn-icon">
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
                            <h3 class="card-title">Lista de Usuários</h3>
                        </div>
                        <div class="table-responsive">
                            <table id="tabelaUsers" class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">ID</th>
                                        <th>Username</th>
                                        <th>Senha</th>
                                        <th class="w-1">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach ($usuarios as $usr): ?>
                                    <tr>
                                        <td class="text-muted"><?= $usr['id'] ?></td>
                                        <td>
                                            <div class="d-flex py-1 align-items-center">
                                                <span class="avatar me-2" style="background-image: url('./static/avatars/000m.jpg')"></span>
                                                <div class="flex-fill">
                                                    <div class="font-weight-medium"><?= htmlspecialchars($usr['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted">
                                            <span class="badge bg-secondary">Protegida</span>
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="user_editar.php?id=<?= $usr['id'] ?>" 
                                                   class="btn btn-white btn-sm" 
                                                   title="Editar usuário">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <a href="user_excluir.php?id=<?= $usr['id'] ?>" 
                                                   class="btn btn-white btn-sm text-danger" 
                                                   title="Excluir usuário"
                                                   onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                                    <i class="ti ti-trash"></i>
                                                </a>
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
</div>

<?php 
$content = ob_get_clean();

// JavaScript adicional para DataTables
$extraJS = '
<script>
$(document).ready(function() {
    $("#tabelaUsers").DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        order: [[0, "asc"]],
        columnDefs: [
            { orderable: false, targets: [3] }
        ]
    });
});
</script>
';

include 'admin_layout.php';
