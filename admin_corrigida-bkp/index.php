<?php
require_once 'config.php';

// Verifica se está logado
requireLogin();

// Título da página
$pageTitle = 'Dashboard';

// Header da página
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <div class="page-pretitle">Portal Administrativo</div>
        <h2 class="page-title">Dashboard</h2>
    </div>
    <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
            <span class="d-none d-sm-inline">
                <a href="../index.php" class="btn" target="_blank">
                    <i class="ti ti-external-link me-1"></i>
                    Ver Portal
                </a>
            </span>
        </div>
    </div>
</div>
<?php
$pageHeader = ob_get_clean();

// Conteúdo da página
ob_start();

// Busca estatísticas do sistema
try {
    // Total de colaboradores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE status = 'ativo'");
    $totalColaboradores = $stmt->fetch()['total'] ?? 0;
    
    // Total de links do menu
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM menu_links WHERE status = 'ativo'");
    $totalLinks = $stmt->fetch()['total'] ?? 0;
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsuarios = $stmt->fetch()['total'] ?? 0;
    
    // Total de alertas ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alerts WHERE status = 'ativo'");
    $totalAlertas = $stmt->fetch()['total'] ?? 0;
    
    // Últimas atualizações de colaboradores
    $stmt = $pdo->query("
        SELECT nome, empresa, updated_at 
        FROM colaboradores 
        ORDER BY updated_at DESC 
        LIMIT 5
    ");
    $ultimasAtualizacoes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $totalColaboradores = $totalLinks = $totalUsuarios = $totalAlertas = 0;
    $ultimasAtualizacoes = [];
}
?>

<!-- Estatísticas -->
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Colaboradores Ativos</div>
                    <div class="ms-auto lh-1">
                        <div class="dropdown">
                            <a class="dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Últimos 30 dias</a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item active" href="#">Últimos 30 dias</a>
                                <a class="dropdown-item" href="#">Últimos 7 dias</a>
                                <a class="dropdown-item" href="#">Hoje</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h1 mb-3"><?= number_format($totalColaboradores) ?></div>
                <div class="d-flex mb-2">
                    <div>Total de colaboradores cadastrados</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-primary" style="width: 100%" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" aria-label="100% Complete">
                        <span class="visually-hidden">100% Complete</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Links do Menu</div>
                </div>
                <div class="h1 mb-3"><?= number_format($totalLinks) ?></div>
                <div class="d-flex mb-2">
                    <div>Itens ativos no menu principal</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-success" style="width: 100%" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                        <span class="visually-hidden">100% Complete</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Usuários do Sistema</div>
                </div>
                <div class="h1 mb-3"><?= number_format($totalUsuarios) ?></div>
                <div class="d-flex mb-2">
                    <div>Total de usuários cadastrados</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-warning" style="width: 100%" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                        <span class="visually-hidden">100% Complete</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Alertas Ativos</div>
                </div>
                <div class="h1 mb-3"><?= number_format($totalAlertas) ?></div>
                <div class="d-flex mb-2">
                    <div>Alertas publicados no portal</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-info" style="width: 100%" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                        <span class="visually-hidden">100% Complete</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="row row-deck row-cards mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ações Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (hasAccess('Gestão de Colaboradores', $user_accesses)): ?>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="colaboradores.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="ti ti-users fs-1 mb-2"></i>
                            <span>Gerenciar Colaboradores</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Gestão de Menu', $user_accesses)): ?>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="links.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="ti ti-link fs-1 mb-2"></i>
                            <span>Gerenciar Menu</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="usuarios.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="ti ti-user fs-1 mb-2"></i>
                            <span>Gerenciar Usuários</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Acessos', $user_accesses)): ?>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="alertas.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="ti ti-alert-circle fs-1 mb-2"></i>
                            <span>Gerenciar Alertas</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Atualizações -->
<?php if (!empty($ultimasAtualizacoes)): ?>
<div class="row row-deck row-cards mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Últimas Atualizações de Colaboradores</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($ultimasAtualizacoes as $colaborador): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar">
                                    <?= strtoupper(substr($colaborador['nome'], 0, 2)) ?>
                                </span>
                            </div>
                            <div class="col text-truncate">
                                <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
                                <div class="text-muted"><?= htmlspecialchars($colaborador['empresa']) ?></div>
                            </div>
                            <div class="col-auto">
                                <span class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($colaborador['updated_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// JavaScript adicional
$extraJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Atualiza estatísticas a cada 30 segundos
    setInterval(function() {
        // Aqui você pode implementar atualização automática das estatísticas via AJAX
        console.log("Atualizando estatísticas...");
    }, 30000);
});
</script>
';

// Inclui o layout
require_once 'layout.php';
?>