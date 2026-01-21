<?php
/**
 * Painel Administrativo - Gerenciar Alertas SPA
 * Grupo Barão - Portal TI
 */

$requiredAccess = 'Alertas';
require_once 'check_access.php';
require_once 'config.php';

// --- API Handler ---
if (isset($_GET['api']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) || (isset($_POST['action']) && isset($_GET['api']))) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => ''];

    try {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_REQUEST['action'] ?? $_POST['action'] ?? '';

        if ($method === 'GET') {
            if ($action === 'list') {
                $stmt = $pdo->query("SELECT * FROM alerts ORDER BY display_order ASC, created_at DESC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                exit;
            } elseif ($action === 'stats') {
                $total = $pdo->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
                $ativos = $pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'ativo'")->fetchColumn();
                $inativos = $total - $ativos;
                echo json_encode(['success' => true, 'data' => [
                    'total' => $total,
                    'ativos' => $ativos,
                    'inativos' => $inativos
                ]]);
                exit;
            }
        }

        if ($method === 'POST') {
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $title = $_POST['title'] ?? '';
                $message = $_POST['message'] ?? '';
                $display_order = (int)($_POST['display_order'] ?? 0);
                $status = $_POST['status'] ?? 'ativo';

                if (empty($title) || empty($message)) {
                    throw new Exception("Título e mensagem são obrigatórios.");
                }

                $dir = '../uploads_alertas/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);

                $imageName = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $imageName = uniqid('img_') . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], $dir . $imageName);
                }

                $fileName = null;
                if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION));
                    $fileName = uniqid('doc_') . '.' . $ext;
                    move_uploaded_file($_FILES['file_path']['tmp_name'], $dir . $fileName);
                }

                if ($id > 0) {
                    // Update
                    $stmtPrev = $pdo->prepare("SELECT image, file_path FROM alerts WHERE id = ?");
                    $stmtPrev->execute([$id]);
                    $current = $stmtPrev->fetch(PDO::FETCH_ASSOC);

                    if (!$imageName) $imageName = $current['image'];
                    if (!$fileName) $fileName = $current['file_path'];

                    $sql = "UPDATE alerts SET title=?, message=?, image=?, file_path=?, display_order=?, status=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$title, $message, $imageName, $fileName, $display_order, $status, $id]);
                    $response = ['success' => true, 'message' => 'Alerta atualizado!'];
                } else {
                    // Create
                    $sql = "INSERT INTO alerts (title, message, image, file_path, display_order, status, created_at) VALUES (?,?,?,?,?,?, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$title, $message, $imageName, $fileName, $display_order, $status]);
                    $response = ['success' => true, 'message' => 'Alerta criado!'];
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $pdo->prepare("DELETE FROM alerts WHERE id = ?")->execute([$id]);
                    $response = ['success' => true, 'message' => 'Alerta excluído!'];
                }
            }
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
// --- End API ---

$pageTitle = 'Gerenciar Alertas';
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-bell me-2"></i>Gerenciar Alertas
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-alert" onclick="resetForm()">
                        <i class="ti ti-plus me-1"></i> Novo Alerta
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="refreshAll()">
                        <i class="ti ti-refresh me-1"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div id="api-alerts"></div>

        <!-- Estatísticas Rápidas -->
        <div class="row row-cards mb-3">
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar"><i class="ti ti-bell"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium" id="stat-total">0</div>
                                <div class="text-muted">Total</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar"><i class="ti ti-check"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-green" id="stat-ativos">0</div>
                                <div class="text-muted">Ativos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-red text-white avatar"><i class="ti ti-x"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-red" id="stat-inativos">0</div>
                                <div class="text-muted">Inativos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title">Lista de Alertas</h3>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tblAlerts" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Mensagem</th>
                                    <th class="text-center">Mídia</th>
                                    <th class="w-1 text-center">Ordem</th>
                                    <th class="w-1 text-center">Status</th>
                                    <th class="w-1 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="alerts-container">
                                <!-- AJAX content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal modal-blur fade" id="modal-alert" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form class="modal-content" id="form-alert" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Novo Alerta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="alert-id">
                
                <div class="mb-3">
                    <label class="form-label required">Título</label>
                    <input type="text" class="form-control" name="title" id="alert-title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Mensagem</label>
                    <textarea class="form-control" name="message" id="alert-message" rows="4" required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Imagem (opcional)</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Arquivo (opcional)</label>
                            <input type="file" class="form-control" name="file_path">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Ordem de Exibição</label>
                            <input type="number" class="form-control" name="display_order" id="alert-order" value="0">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="alert-status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary ms-auto" id="btnSave">
                    <i class="ti ti-check me-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();

$extraJS = <<<'JS'
<script>
let datatable = null;

$(document).ready(function() {
    refreshAll();

    $("#form-alert").on("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        const btn = $("#btnSave");
        btn.prop("disabled", true).addClass("btn-loading");

        fetch("alerts_admin.php?api=1", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showAlert(res.message, "success");
                bootstrap.Modal.getInstance(document.getElementById("modal-alert")).hide();
                refreshAll();
            } else {
                showAlert(res.message, "danger");
            }
        })
        .catch(err => showAlert("Erro ao salvar", "danger"))
        .finally(() => btn.prop("disabled", false).removeClass("btn-loading"));
    });
});

function refreshAll() {
    loadStats();
    loadAlerts();
}

function loadStats() {
    fetch('alerts_admin.php?api=1&action=stats')
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            $("#stat-total").text(res.data.total);
            $("#stat-ativos").text(res.data.ativos);
            $("#stat-inativos").text(res.data.inativos);
        }
    });
}

function loadAlerts() {
    fetch('alerts_admin.php?api=1&action=list')
    .then(res => res.json())
    .then(res => {
        if (res.success) renderTable(res.data);
    });
}

function renderTable(data) {
    if (datatable) datatable.destroy();

    const container = $("#alerts-container");
    let html = "";
    
    data.forEach(item => {
        const media = [];
        if (item.image) media.push(`<a href="../uploads_alertas/${item.image}" target="_blank" class="badge bg-blue-lt">IMG</a>`);
        if (item.file_path) media.push(`<a href="../uploads_alertas/${item.file_path}" target="_blank" class="badge bg-green-lt">PDF</a>`);
        const mediaHtml = media.length > 0 ? media.join(' ') : '<span class="text-muted small">-</span>';

        html += `
            <tr>
                <td><div class="font-weight-medium">${item.title}</div></td>
                <td class="text-muted small" style="max-width: 300px;">
                    ${item.message.length > 60 ? item.message.substring(0, 60) + '...' : item.message}
                </td>
                <td class="text-center">${mediaHtml}</td>
                <td class="text-center"><span class="badge bg-secondary">${item.display_order}</span></td>
                <td class="text-center">
                    <span class="badge ${item.status === 'ativo' ? 'bg-green' : 'bg-red'}">
                        ${item.status === 'ativo' ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td class="text-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Ações</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick='editAlert(${JSON.stringify(item)})'><i class="ti ti-edit me-2"></i> Editar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteAlert(${item.id})"><i class="ti ti-trash me-2"></i> Excluir</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    });
    
    container.html(html);

    datatable = $("#tblAlerts").DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
        pageLength: 25,
        order: [[3, "asc"]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });
}

function resetForm() {
    $("#modal-title").text("Novo Alerta");
    $("#alert-id").val("");
    $("#form-alert")[0].reset();
}

function editAlert(data) {
    $("#modal-title").text("Editar Alerta");
    $("#alert-id").val(data.id);
    $("#alert-title").val(data.title);
    $("#alert-message").val(data.message);
    $("#alert-order").val(data.display_order);
    $("#alert-status").val(data.status);
    
    new bootstrap.Modal(document.getElementById("modal-alert")).show();
}

function deleteAlert(id) {
    if (!confirm("Excluir este alerta permanentemente?")) return;
    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    fetch("alerts_admin.php?api=1", { method: "POST", body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.success) { showAlert(res.message, "success"); refreshAll(); }
        else showAlert(res.message, "danger");
    });
}

function showAlert(msg, type = "success") {
    const icon = type === "success" ? "check" : "alert-circle";
    const html = `<div class="alert alert-${type} alert-dismissible" role="alert">
        <div class="d-flex">
            <div><i class="ti ti-${icon} me-2"></i></div>
            <div>${msg}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
    $("#api-alerts").html(html);
    setTimeout(() => { $("#api-alerts .alert").fadeOut(500, function() { $(this).remove(); }); }, 5000);
}
</script>
JS;

require_once 'admin_layout.php';
?>
