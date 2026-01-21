<?php
/**
 * Visualização de Colaborador do Organograma
 */

$requiredAccess = ['Organograma', 'Super Administrador'];
require_once 'check_access.php';
require_once 'config.php';

$id = $_GET['id'] ?? 0;

// Buscar colaborador
$stmt = $pdo->prepare("SELECT * FROM organograma WHERE id = ?");
$stmt->execute([$id]);
$colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colaborador) {
    header('Location: organograma_admin.php');
    exit;
}

// Buscar supervisor
$supervisor = null;
if ($colaborador['parent_id']) {
    $supervisor_stmt = $pdo->prepare("SELECT nome, cargo FROM organograma WHERE id = ?");
    $supervisor_stmt->execute([$colaborador['parent_id']]);
    $supervisor = $supervisor_stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar subordinados
$subordinados_stmt = $pdo->prepare("SELECT id, nome, cargo, tipo_contrato FROM organograma WHERE parent_id = ? AND ativo = 1 ORDER BY ordem_exibicao, nome");
$subordinados_stmt->execute([$id]);
$subordinados = $subordinados_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Visualizar Colaborador';

ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="organograma_admin.php">Organograma</a></li>
                        <li class="breadcrumb-item active">Visualizar Colaborador</li>
                    </ol>
                </nav>
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-eye me-2"></i>
                    <?= htmlspecialchars($colaborador['nome']) ?>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="organograma_edit.php?id=<?= $id ?>" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i>
                        Editar
                    </a>
                    <a href="organograma_admin.php" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-user me-2"></i>
                            Informações Pessoais
                        </h3>
                        <div class="card-actions">
                            <span class="badge <?= $colaborador['ativo'] ? 'bg-green' : 'bg-red' ?>">
                                <?= $colaborador['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-auto">
                                <div class="avatar avatar-xl" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 1.5rem;">
                                    <?= strtoupper(substr($colaborador['nome'], 0, 2)) ?>
                                </div>
                            </div>
                            <div class="col">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">Nome Completo</div>
                                        <div class="datagrid-content fw-bold"><?= htmlspecialchars($colaborador['nome']) ?></div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">Cargo</div>
                                        <div class="datagrid-content"><?= htmlspecialchars($colaborador['cargo']) ?></div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">Departamento</div>
                                        <div class="datagrid-content">
                                            <span class="badge bg-blue-lt"><?= htmlspecialchars($colaborador['departamento']) ?></span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">Tipo de Contrato</div>
                                        <div class="datagrid-content">
                                            <span class="badge bg-purple-lt"><?= htmlspecialchars($colaborador['tipo_contrato']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($colaborador['descricao']): ?>
                        <div class="mt-3">
                            <h4>Descrição</h4>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($colaborador['descricao'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Subordinados -->
                <?php if ($subordinados): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-users me-2"></i>
                            Equipe (<?= count($subordinados) ?> colaboradores)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($subordinados as $subordinado): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                                    <?= strtoupper(substr($subordinado['nome'], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="fw-bold"><?= htmlspecialchars($subordinado['nome']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($subordinado['cargo']) ?></div>
                                                <span class="badge bg-purple-lt small"><?= htmlspecialchars($subordinado['tipo_contrato']) ?></span>
                                            </div>
                                            <div class="col-auto">
                                                <a href="organograma_view.php?id=<?= $subordinado['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <!-- Informações de Contato -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-address-book me-2"></i>
                            Contato
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <?php if ($colaborador['email']): ?>
                            <div class="datagrid-item">
                                <div class="datagrid-title">E-mail</div>
                                <div class="datagrid-content">
                                    <a href="mailto:<?= htmlspecialchars($colaborador['email']) ?>" class="text-decoration-none">
                                        <i class="ti ti-mail me-1"></i>
                                        <?= htmlspecialchars($colaborador['email']) ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($colaborador['telefone']): ?>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Telefone</div>
                                <div class="datagrid-content">
                                    <a href="tel:<?= htmlspecialchars($colaborador['telefone']) ?>" class="text-decoration-none">
                                        <i class="ti ti-phone me-1"></i>
                                        <?= htmlspecialchars($colaborador['telefone']) ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Hierarquia -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-hierarchy me-2"></i>
                            Hierarquia
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Nível Hierárquico</div>
                                <div class="datagrid-content">
                                    <span class="badge bg-indigo"><?= $colaborador['nivel_hierarquico'] ?></span>
                                </div>
                            </div>
                            
                            <?php if ($supervisor): ?>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Supervisor</div>
                                <div class="datagrid-content">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                            <?= strtoupper(substr($supervisor['nome'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($supervisor['nome']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($supervisor['cargo']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Supervisor</div>
                                <div class="datagrid-content">
                                    <span class="text-muted">Nenhum (Diretor/Gerente)</span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="datagrid-item">
                                <div class="datagrid-title">Subordinados</div>
                                <div class="datagrid-content">
                                    <span class="badge bg-green"><?= count($subordinados) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações do Sistema -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-info-circle me-2"></i>
                            Sistema
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">ID</div>
                                <div class="datagrid-content"><?= $colaborador['id'] ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Ordem de Exibição</div>
                                <div class="datagrid-content"><?= $colaborador['ordem_exibicao'] ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Criado em</div>
                                <div class="datagrid-content"><?= date('d/m/Y H:i', strtotime($colaborador['created_at'])) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Atualizado em</div>
                                <div class="datagrid-content"><?= date('d/m/Y H:i', strtotime($colaborador['updated_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
?>