<?php
$requiredAccess = 'Acessos';
require_once 'check_access.php';

$pageTitle = 'Gerenciar Acessos de Usuários';

// Consulta todos os usuários
$sql = "SELECT * FROM users ORDER BY username ASC";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <i class="ti ti-shield-lock me-2"></i>
                        Gerenciar Acessos de Usuários
                    </h2>
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
                            <h3 class="card-title">Controle de Acessos</h3>
                        </div>
                        <div class="table-responsive">
                            <table id="tabelaUsers" class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">ID</th>
                                        <th>Username</th>
                                        <th>Acessos</th>
                                        <th class="w-1">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): 
                                        // Consulta os acessos do usuário
                                        $sqlAccess = "SELECT a.access_name FROM user_access ua
                                                      JOIN accesses a ON ua.access_id = a.id
                                                      WHERE ua.user_id = :user_id";
                                        $stmtAccess = $pdo->prepare($sqlAccess);
                                        $stmtAccess->execute([':user_id' => $user['id']]);
                                        $accesses = $stmtAccess->fetchAll(PDO::FETCH_COLUMN);
                                        $accessList = implode(", ", $accesses);
                                    ?>
                                    <tr>
                                        <td class="text-muted"><?= $user['id'] ?></td>
                                        <td>
                                            <div class="d-flex py-1 align-items-center">
                                                <span class="avatar me-2" style="background-image: url('./static/avatars/000m.jpg')"></span>
                                                <div class="flex-fill">
                                                    <div class="font-weight-medium"><?= htmlspecialchars($user['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($accesses)): ?>
                                                <?php foreach ($accesses as $access): ?>
                                                    <span class="badge bg-azure me-1"><?= htmlspecialchars($access) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nenhum acesso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="user_access_edit.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Editar Acessos">
                                                <i class="ti ti-edit"></i>
                                                Editar
                                            </a>
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
$(document).ready(function(){
    $("#tabelaUsers").DataTable({
        language: { 
            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" 
        },
        pageLength: 10,
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [2, 3] }
        ]
    });
});
</script>
';

include 'admin_layout.php';
