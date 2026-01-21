<?php
require_once 'config.php';

// Verifica se está logado e tem acesso
requireLogin();
if (!hasAccess('Gestão de Colaboradores', $user_accesses)) {
    header('Location: index.php');
    exit;
}

// Título da página
$pageTitle = 'Gestão de Colaboradores';

// Processa ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $stmt = $pdo->prepare("
                    INSERT INTO colaboradores (
                        nome, empresa, setor, email, telefone, teams, observacoes, 
                        status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', NOW(), NOW())
                ");
                $stmt->execute([
                    $_POST['nome'],
                    $_POST['empresa'],
                    $_POST['setor'],
                    $_POST['email'],
                    $_POST['telefone'],
                    $_POST['teams'],
                    $_POST['observacoes']
                ]);
                $message = 'Colaborador adicionado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("
                    UPDATE colaboradores SET 
                        nome = ?, empresa = ?, setor = ?, email = ?, 
                        telefone = ?, teams = ?, observacoes = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['nome'],
                    $_POST['empresa'],
                    $_POST['setor'],
                    $_POST['email'],
                    $_POST['telefone'],
                    $_POST['teams'],
                    $_POST['observacoes'],
                    $_POST['id']
                ]);
                $message = 'Colaborador atualizado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("UPDATE colaboradores SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = 'Colaborador removido com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (PDOException $e) {
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Busca colaboradores
$search = $_GET['search'] ?? '';
$empresa = $_GET['empresa'] ?? '';
$setor = $_GET['setor'] ?? '';

$sql = "SELECT * FROM colaboradores WHERE status = 'ativo'";
$params = [];

if ($search) {
    $sql .= " AND (nome LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($empresa) {
    $sql .= " AND empresa = ?";
    $params[] = $empresa;
}

if ($setor) {
    $sql .= " AND setor = ?";
    $params[] = $setor;
}

$sql .= " ORDER BY nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colaboradores = $stmt->fetchAll();

// Busca empresas e setores para filtros
$empresas = $pdo->query("SELECT DISTINCT empresa FROM colaboradores WHERE status = 'ativo' ORDER BY empresa")->fetchAll(PDO::FETCH_COLUMN);
$setores = $pdo->query("SELECT DISTINCT setor FROM colaboradores WHERE status = 'ativo' ORDER BY setor")->fetchAll(PDO::FETCH_COLUMN);

// Header da página
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <div class="page-pretitle">Gestão</div>
        <h2 class="page-title">Colaboradores</h2>
    </div>
    <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalColaborador">
                <i class="ti ti-plus me-1"></i>
                Novo Colaborador
            </button>
        </div>
    </div>
</div>
<?php
$pageHeader = ob_get_clean();

// Conteúdo da página
ob_start();
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible" role="alert">
    <div class="d-flex">
        <div>
            <i class="ti ti-check me-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nome ou email...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Empresa</label>
                <select class="form-select" name="empresa">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $emp): ?>
                    <option value="<?= htmlspecialchars($emp) ?>" <?= $empresa === $emp ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Setor</label>
                <select class="form-select" name="setor">
                    <option value="">Todos</option>
                    <?php foreach ($setores as $set): ?>
                    <option value="<?= htmlspecialchars($set) ?>" <?= $setor === $set ? 'selected' : '' ?>>
                        <?= htmlspecialchars($set) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Colaboradores -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Colaboradores Cadastrados (<?= count($colaboradores) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="tabelaColaboradores">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Empresa</th>
                    <th>Setor</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Atualizado</th>
                    <th class="w-1">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $colaborador): ?>
                <tr>
                    <td>
                        <div class="d-flex py-1 align-items-center">
                            <span class="avatar me-2">
                                <?= strtoupper(substr($colaborador['nome'], 0, 2)) ?>
                            </span>
                            <div class="flex-fill">
                                <div class="font-weight-medium"><?= htmlspecialchars($colaborador['nome']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($colaborador['empresa']) ?></td>
                    <td><?= htmlspecialchars($colaborador['setor']) ?></td>
                    <td>
                        <?php if ($colaborador['email']): ?>
                        <a href="mailto:<?= htmlspecialchars($colaborador['email']) ?>">
                            <?= htmlspecialchars($colaborador['email']) ?>
                        </a>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($colaborador['telefone']) ?></td>
                    <td><?= date('d/m/Y', strtotime($colaborador['updated_at'])) ?></td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editarColaborador(<?= htmlspecialchars(json_encode($colaborador)) ?>)">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="excluirColaborador(<?= $colaborador['id'] ?>, '<?= htmlspecialchars($colaborador['nome']) ?>')">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Colaborador -->
<div class="modal modal-blur fade" id="modalColaborador" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formColaborador" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="colaboradorId">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label required">Nome</label>
                                <input type="text" class="form-control" name="nome" id="nome" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label required">Empresa</label>
                                <select class="form-select" name="empresa" id="empresa" required>
                                    <option value="">Selecione...</option>
                                    <option value="Maxtrade">Maxtrade</option>
                                    <option value="Portal">Portal</option>
                                    <option value="RH">RH</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Setor</label>
                                <input type="text" class="form-control" name="setor" id="setor">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone" id="telefone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teams</label>
                                <input type="text" class="form-control" name="teams" id="teams" placeholder="Link do Teams">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" id="observacoes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmação Exclusão -->
<div class="modal modal-blur fade" id="modalExcluir" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Tem certeza?</div>
                <div>Deseja realmente excluir o colaborador <strong id="nomeExcluir"></strong>?</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="idExcluir">
                    <button type="submit" class="btn btn-danger">Sim, excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript adicional
$extraJS = '
<script>
$(document).ready(function() {
    $("#tabelaColaboradores").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "pageLength": 25,
        "order": [[0, "asc"]]
    });
});

function editarColaborador(colaborador) {
    $("#modalTitle").text("Editar Colaborador");
    $("#action").val("edit");
    $("#colaboradorId").val(colaborador.id);
    $("#nome").val(colaborador.nome);
    $("#empresa").val(colaborador.empresa);
    $("#setor").val(colaborador.setor);
    $("#email").val(colaborador.email);
    $("#telefone").val(colaborador.telefone);
    $("#teams").val(colaborador.teams);
    $("#observacoes").val(colaborador.observacoes);
    $("#modalColaborador").modal("show");
}

function excluirColaborador(id, nome) {
    $("#idExcluir").val(id);
    $("#nomeExcluir").text(nome);
    $("#modalExcluir").modal("show");
}

// Reset form when modal is closed
$("#modalColaborador").on("hidden.bs.modal", function() {
    $("#formColaborador")[0].reset();
    $("#modalTitle").text("Novo Colaborador");
    $("#action").val("add");
    $("#colaboradorId").val("");
});
</script>
';

// Inclui o layout
require_once 'layout.php';
?>