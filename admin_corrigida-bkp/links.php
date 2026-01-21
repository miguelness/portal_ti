<?php
require_once 'config.php';

// Verifica se está logado e tem acesso
requireLogin();
if (!hasAccess('Gestão de Menu', $user_accesses)) {
    header('Location: index.php');
    exit;
}

// Título da página
$pageTitle = 'Gestão de Menu';

// Processa ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $stmt = $pdo->prepare("
                    INSERT INTO menu_links (
                        titulo, url, icone, categoria, ordem, status, 
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, 'ativo', NOW(), NOW())
                ");
                $stmt->execute([
                    $_POST['titulo'],
                    $_POST['url'],
                    $_POST['icone'],
                    $_POST['categoria'],
                    $_POST['ordem']
                ]);
                $message = 'Link adicionado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("
                    UPDATE menu_links SET 
                        titulo = ?, url = ?, icone = ?, categoria = ?, 
                        ordem = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['titulo'],
                    $_POST['url'],
                    $_POST['icone'],
                    $_POST['categoria'],
                    $_POST['ordem'],
                    $_POST['id']
                ]);
                $message = 'Link atualizado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("UPDATE menu_links SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = 'Link removido com sucesso!';
                $messageType = 'success';
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE menu_links SET status = ?, updated_at = NOW() WHERE id = ?");
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

// Busca links
$categoria = $_GET['categoria'] ?? '';
$status = $_GET['status'] ?? 'ativo';

$sql = "SELECT * FROM menu_links WHERE 1=1";
$params = [];

if ($categoria) {
    $sql .= " AND categoria = ?";
    $params[] = $categoria;
}

if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY categoria, ordem, titulo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$links = $stmt->fetchAll();

// Busca categorias disponíveis
$categorias = $pdo->query("SELECT DISTINCT categoria FROM menu_links ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

// Header da página
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <div class="page-pretitle">Gestão</div>
        <h2 class="page-title">Menu do Portal</h2>
    </div>
    <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
            <a href="../index.php" class="btn btn-outline-primary" target="_blank">
                <i class="ti ti-external-link me-1"></i>
                Ver Portal
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLink">
                <i class="ti ti-plus me-1"></i>
                Novo Link
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
                <label class="form-label">Categoria</label>
                <select class="form-select" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                    <?php endforeach; ?>
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

<!-- Lista de Links -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Links do Menu (<?= count($links) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="tabelaLinks">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>URL</th>
                    <th>Categoria</th>
                    <th>Ordem</th>
                    <th>Status</th>
                    <th>Atualizado</th>
                    <th class="w-1">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td>
                        <div class="d-flex py-1 align-items-center">
                            <?php if ($link['icone']): ?>
                            <i class="<?= htmlspecialchars($link['icone']) ?> me-2"></i>
                            <?php endif; ?>
                            <div class="flex-fill">
                                <div class="font-weight-medium"><?= htmlspecialchars($link['titulo']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" class="text-muted">
                            <?= htmlspecialchars($link['url']) ?>
                            <i class="ti ti-external-link ms-1"></i>
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-blue-lt"><?= htmlspecialchars($link['categoria']) ?></span>
                    </td>
                    <td><?= $link['ordem'] ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                            <input type="hidden" name="status" value="<?= $link['status'] === 'ativo' ? 'inativo' : 'ativo' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $link['status'] === 'ativo' ? 'success' : 'secondary' ?>">
                                <?= $link['status'] === 'ativo' ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </td>
                    <td><?= date('d/m/Y', strtotime($link['updated_at'])) ?></td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editarLink(<?= htmlspecialchars(json_encode($link)) ?>)">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="excluirLink(<?= $link['id'] ?>, '<?= htmlspecialchars($link['titulo']) ?>')">
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

<!-- Modal Link -->
<div class="modal modal-blur fade" id="modalLink" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formLink" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="linkId">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label required">Título</label>
                                <input type="text" class="form-control" name="titulo" id="titulo" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Ordem</label>
                                <input type="number" class="form-control" name="ordem" id="ordem" value="1" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">URL</label>
                        <input type="url" class="form-control" name="url" id="url" required placeholder="https://...">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Categoria</label>
                                <select class="form-select" name="categoria" id="categoria" required>
                                    <option value="">Selecione...</option>
                                    <option value="Maxtrade">Maxtrade</option>
                                    <option value="Portal">Portal</option>
                                    <option value="RH">RH</option>
                                    <option value="TI">TI</option>
                                    <option value="Geral">Geral</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ícone</label>
                                <input type="text" class="form-control" name="icone" id="icone" placeholder="ti ti-link">
                                <div class="form-hint">
                                    Use classes do Tabler Icons. Ex: ti ti-link, ti ti-file, ti ti-users
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Preview do Ícone</label>
                        <div class="border rounded p-3 text-center">
                            <i id="iconePreview" class="ti ti-link" style="font-size: 2rem;"></i>
                            <div class="mt-2 text-muted">Preview do ícone</div>
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
                <div>Deseja realmente excluir o link <strong id="tituloExcluir"></strong>?</div>
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
    $("#tabelaLinks").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "pageLength": 25,
        "order": [[2, "asc"], [3, "asc"]]
    });
    
    // Preview do ícone
    $("#icone").on("input", function() {
        var icone = $(this).val() || "ti ti-link";
        $("#iconePreview").attr("class", icone);
    });
});

function editarLink(link) {
    $("#modalTitle").text("Editar Link");
    $("#action").val("edit");
    $("#linkId").val(link.id);
    $("#titulo").val(link.titulo);
    $("#url").val(link.url);
    $("#icone").val(link.icone);
    $("#categoria").val(link.categoria);
    $("#ordem").val(link.ordem);
    
    // Atualiza preview do ícone
    $("#iconePreview").attr("class", link.icone || "ti ti-link");
    
    $("#modalLink").modal("show");
}

function excluirLink(id, titulo) {
    $("#idExcluir").val(id);
    $("#tituloExcluir").text(titulo);
    $("#modalExcluir").modal("show");
}

// Reset form when modal is closed
$("#modalLink").on("hidden.bs.modal", function() {
    $("#formLink")[0].reset();
    $("#modalTitle").text("Novo Link");
    $("#action").val("add");
    $("#linkId").val("");
    $("#iconePreview").attr("class", "ti ti-link");
});
</script>
';

// Inclui o layout
require_once 'layout.php';
?>