<?php
require_once 'config.php';

// Verifica se está logado e tem acesso
requireLogin();
if (!hasAccess('Acessos', $user_accesses)) {
    header('Location: index.php');
    exit;
}

// Título da página
$pageTitle = 'Gestão de Alertas';

// Processa ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $stmt = $pdo->prepare("
                    INSERT INTO alerts (
                        titulo, conteudo, tipo, data_inicio, data_fim, 
                        status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, 'ativo', NOW(), NOW())
                ");
                $stmt->execute([
                    $_POST['titulo'],
                    $_POST['conteudo'],
                    $_POST['tipo'],
                    $_POST['data_inicio'] ?: null,
                    $_POST['data_fim'] ?: null
                ]);
                $message = 'Alerta adicionado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("
                    UPDATE alerts SET 
                        titulo = ?, conteudo = ?, tipo = ?, 
                        data_inicio = ?, data_fim = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['titulo'],
                    $_POST['conteudo'],
                    $_POST['tipo'],
                    $_POST['data_inicio'] ?: null,
                    $_POST['data_fim'] ?: null,
                    $_POST['id']
                ]);
                $message = 'Alerta atualizado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("UPDATE alerts SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = 'Alerta removido com sucesso!';
                $messageType = 'success';
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE alerts SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['id']]);
                $message = 'Status atualizado com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (PDOException $e) {
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Busca alertas
$tipo = $_GET['tipo'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM alerts WHERE 1=1";
$params = [];

if ($tipo) {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}

if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alertas = $stmt->fetchAll();

// Header da página
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <div class="page-pretitle">Gestão</div>
        <h2 class="page-title">Alertas do Portal</h2>
    </div>
    <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
            <a href="../index.php" class="btn btn-outline-primary" target="_blank">
                <i class="ti ti-external-link me-1"></i>
                Ver Portal
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAlerta">
                <i class="ti ti-plus me-1"></i>
                Novo Alerta
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
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo">
                    <option value="">Todos</option>
                    <option value="info" <?= $tipo === 'info' ? 'selected' : '' ?>>Informativo</option>
                    <option value="warning" <?= $tipo === 'warning' ? 'selected' : '' ?>>Aviso</option>
                    <option value="danger" <?= $tipo === 'danger' ? 'selected' : '' ?>>Urgente</option>
                    <option value="success" <?= $tipo === 'success' ? 'selected' : '' ?>>Sucesso</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
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

<!-- Lista de Alertas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Alertas Cadastrados (<?= count($alertas) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="tabelaAlertas">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Período</th>
                    <th>Status</th>
                    <th>Criado</th>
                    <th class="w-1">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas as $alerta): ?>
                <tr>
                    <td>
                        <div class="d-flex py-1 align-items-center">
                            <div class="flex-fill">
                                <div class="font-weight-medium"><?= htmlspecialchars($alerta['titulo']) ?></div>
                                <div class="text-muted text-truncate" style="max-width: 300px;">
                                    <?= htmlspecialchars(substr($alerta['conteudo'], 0, 100)) ?>...
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                        $tipoClass = [
                            'info' => 'bg-blue-lt',
                            'warning' => 'bg-yellow-lt',
                            'danger' => 'bg-red-lt',
                            'success' => 'bg-green-lt'
                        ];
                        $tipoNome = [
                            'info' => 'Informativo',
                            'warning' => 'Aviso',
                            'danger' => 'Urgente',
                            'success' => 'Sucesso'
                        ];
                        ?>
                        <span class="badge <?= $tipoClass[$alerta['tipo']] ?? 'bg-secondary-lt' ?>">
                            <?= $tipoNome[$alerta['tipo']] ?? $alerta['tipo'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($alerta['data_inicio'] || $alerta['data_fim']): ?>
                            <div class="text-muted">
                                <?php if ($alerta['data_inicio']): ?>
                                    De: <?= date('d/m/Y', strtotime($alerta['data_inicio'])) ?><br>
                                <?php endif; ?>
                                <?php if ($alerta['data_fim']): ?>
                                    Até: <?= date('d/m/Y', strtotime($alerta['data_fim'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Permanente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $alerta['id'] ?>">
                            <input type="hidden" name="status" value="<?= $alerta['status'] === 'ativo' ? 'inativo' : 'ativo' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $alerta['status'] === 'ativo' ? 'success' : 'secondary' ?>">
                                <?= $alerta['status'] === 'ativo' ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($alerta['created_at'])) ?></td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editarAlerta(<?= htmlspecialchars(json_encode($alerta)) ?>)">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="excluirAlerta(<?= $alerta['id'] ?>, '<?= htmlspecialchars($alerta['titulo']) ?>')">
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

<!-- Modal Alerta -->
<div class="modal modal-blur fade" id="modalAlerta" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Alerta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAlerta" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="alertaId">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label required">Título</label>
                                <input type="text" class="form-control" name="titulo" id="titulo" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label required">Tipo</label>
                                <select class="form-select" name="tipo" id="tipo" required>
                                    <option value="">Selecione...</option>
                                    <option value="info">Informativo</option>
                                    <option value="warning">Aviso</option>
                                    <option value="danger">Urgente</option>
                                    <option value="success">Sucesso</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Conteúdo</label>
                        <textarea class="form-control" name="conteudo" id="conteudo" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data de Início</label>
                                <input type="date" class="form-control" name="data_inicio" id="data_inicio">
                                <div class="form-hint">Deixe em branco para exibir imediatamente</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data de Fim</label>
                                <input type="date" class="form-control" name="data_fim" id="data_fim">
                                <div class="form-hint">Deixe em branco para exibir permanentemente</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Preview</label>
                        <div class="border rounded p-3">
                            <div id="alertPreview" class="alert alert-info mb-0">
                                <h4 id="previewTitulo" class="alert-title">Título do Alerta</h4>
                                <div id="previewConteudo" class="text-muted">Conteúdo do alerta aparecerá aqui...</div>
                            </div>
                        </div>
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
                <div>Deseja realmente excluir o alerta <strong id="tituloExcluir"></strong>?</div>
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
    $("#tabelaAlertas").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "pageLength": 25,
        "order": [[4, "desc"]]
    });
    
    // Preview do alerta
    function updatePreview() {
        var titulo = $("#titulo").val() || "Título do Alerta";
        var conteudo = $("#conteudo").val() || "Conteúdo do alerta aparecerá aqui...";
        var tipo = $("#tipo").val() || "info";
        
        $("#previewTitulo").text(titulo);
        $("#previewConteudo").text(conteudo);
        
        var alertClass = "alert-" + tipo;
        $("#alertPreview").removeClass("alert-info alert-warning alert-danger alert-success")
                         .addClass(alertClass);
    }
    
    $("#titulo, #conteudo, #tipo").on("input change", updatePreview);
});

function editarAlerta(alerta) {
    $("#modalTitle").text("Editar Alerta");
    $("#action").val("edit");
    $("#alertaId").val(alerta.id);
    $("#titulo").val(alerta.titulo);
    $("#conteudo").val(alerta.conteudo);
    $("#tipo").val(alerta.tipo);
    $("#data_inicio").val(alerta.data_inicio);
    $("#data_fim").val(alerta.data_fim);
    
    // Atualiza preview
    setTimeout(function() {
        $("#titulo, #conteudo, #tipo").trigger("input");
    }, 100);
    
    $("#modalAlerta").modal("show");
}

function excluirAlerta(id, titulo) {
    $("#idExcluir").val(id);
    $("#tituloExcluir").text(titulo);
    $("#modalExcluir").modal("show");
}

// Reset form when modal is closed
$("#modalAlerta").on("hidden.bs.modal", function() {
    $("#formAlerta")[0].reset();
    $("#modalTitle").text("Novo Alerta");
    $("#action").val("add");
    $("#alertaId").val("");
    
    // Reset preview
    $("#previewTitulo").text("Título do Alerta");
    $("#previewConteudo").text("Conteúdo do alerta aparecerá aqui...");
    $("#alertPreview").removeClass("alert-warning alert-danger alert-success")
                     .addClass("alert-info");
});
</script>
';

// Inclui o layout
require_once 'layout.php';
?>