<?php
/**
 * Edição de Colaborador do Organograma
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

// Processar atualização
if ($_POST) {
    $stmt = $pdo->prepare("
        UPDATE organograma SET 
            nome = ?, cargo = ?, departamento = ?, tipo_contrato = ?, 
            parent_id = ?, nivel_hierarquico = ?, ordem_exibicao = ?, 
            email = ?, telefone = ?, descricao = ?, ativo = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['nome'],
        $_POST['cargo'],
        $_POST['departamento'],
        $_POST['tipo_contrato'],
        $_POST['parent_id'] ?: null,
        $_POST['nivel_hierarquico'],
        $_POST['ordem_exibicao'],
        $_POST['email'] ?: null,
        $_POST['telefone'] ?: null,
        $_POST['descricao'] ?: null,
        isset($_POST['ativo']) ? 1 : 0,
        $id
    ]);
    
    $success = "Colaborador atualizado com sucesso!";
    
    // Recarregar dados
    $stmt = $pdo->prepare("SELECT * FROM organograma WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar possíveis supervisores (excluindo o próprio colaborador e seus subordinados)
$supervisores_stmt = $pdo->prepare("
    SELECT id, nome, cargo, departamento 
    FROM organograma 
    WHERE ativo = 1 AND id != ? 
    ORDER BY departamento, nome
");
$supervisores_stmt->execute([$id]);
$supervisores = $supervisores_stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar departamentos para datalist
$dept_stmt = $pdo->query("SELECT DISTINCT departamento FROM organograma WHERE ativo = 1 ORDER BY departamento");
$departamentos = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Editar Colaborador';

ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="organograma_admin.php">Organograma</a></li>
                        <li class="breadcrumb-item active">Editar Colaborador</li>
                    </ol>
                </nav>
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-edit me-2"></i>
                    Editar Colaborador
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
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
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-check alert-icon"></i></div>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-user me-2"></i>
                            Informações do Colaborador
                        </h3>
                    </div>
                    <form method="POST">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Nome</label>
                                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($colaborador['nome']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Cargo</label>
                                        <input type="text" class="form-control" name="cargo" value="<?= htmlspecialchars($colaborador['cargo']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Departamento</label>
                                        <input type="text" class="form-control" name="departamento" value="<?= htmlspecialchars($colaborador['departamento']) ?>" required list="departamentos">
                                        <datalist id="departamentos">
                                            <?php foreach ($departamentos as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept) ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Tipo de Contrato</label>
                                        <select class="form-select" name="tipo_contrato" required>
                                            <option value="CLT" <?= $colaborador['tipo_contrato'] === 'CLT' ? 'selected' : '' ?>>CLT</option>
                                            <option value="PJ" <?= $colaborador['tipo_contrato'] === 'PJ' ? 'selected' : '' ?>>PJ</option>
                                            <option value="Aprendiz" <?= $colaborador['tipo_contrato'] === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                                            <option value="Terceirizado" <?= $colaborador['tipo_contrato'] === 'Terceirizado' ? 'selected' : '' ?>>Terceirizado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Supervisor</label>
                                        <select class="form-select" name="parent_id">
                                            <option value="">Nenhum (Diretor/Gerente)</option>
                                            <?php foreach ($supervisores as $supervisor): ?>
                                            <option value="<?= $supervisor['id'] ?>" <?= $colaborador['parent_id'] == $supervisor['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($supervisor['nome']) ?> - <?= htmlspecialchars($supervisor['cargo']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label required">Nível Hierárquico</label>
                                        <input type="number" class="form-control" name="nivel_hierarquico" min="1" max="10" value="<?= $colaborador['nivel_hierarquico'] ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Ordem de Exibição</label>
                                        <input type="number" class="form-control" name="ordem_exibicao" min="0" value="<?= $colaborador['ordem_exibicao'] ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($colaborador['email'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Telefone</label>
                                        <input type="text" class="form-control" name="telefone" value="<?= htmlspecialchars($colaborador['telefone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <textarea class="form-control" name="descricao" rows="3"><?= htmlspecialchars($colaborador['descricao'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input" name="ativo" <?= $colaborador['ativo'] ? 'checked' : '' ?>>
                                    <span class="form-check-label">Colaborador ativo</span>
                                </label>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <div class="d-flex">
                                <a href="organograma_admin.php" class="btn btn-link">Cancelar</a>
                                <button type="submit" class="btn btn-primary ms-auto">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-info-circle me-2"></i>
                            Informações
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">ID</div>
                                <div class="datagrid-content"><?= $colaborador['id'] ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Criado em</div>
                                <div class="datagrid-content"><?= date('d/m/Y H:i', strtotime($colaborador['created_at'])) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Atualizado em</div>
                                <div class="datagrid-content"><?= date('d/m/Y H:i', strtotime($colaborador['updated_at'])) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Status</div>
                                <div class="datagrid-content">
                                    <span class="badge <?= $colaborador['ativo'] ? 'bg-green' : 'bg-red' ?>">
                                        <?= $colaborador['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Subordinados -->
                <?php
                $subordinados_stmt = $pdo->prepare("SELECT nome, cargo FROM organograma WHERE parent_id = ? AND ativo = 1 ORDER BY nome");
                $subordinados_stmt->execute([$id]);
                $subordinados = $subordinados_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($subordinados):
                ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-users me-2"></i>
                            Subordinados (<?= count($subordinados) ?>)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($subordinados as $subordinado): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="avatar avatar-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                            <?= strtoupper(substr($subordinado['nome'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="fw-bold"><?= htmlspecialchars($subordinado['nome']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($subordinado['cargo']) ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
?>