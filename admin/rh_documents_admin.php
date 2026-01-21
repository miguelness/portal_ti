<?php
// admin/rh_documents_admin.php – Gerenciar Documentos RH SPA
$requiredAccess = 'Documentos RH';
require_once 'check_access.php';
require_once 'config.php';

// Diretório de upload
$uploadDir = '../uploads_rh/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- API Handler ---
if (isset($_GET['api']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $_REQUEST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'list') {
                $stmt = $pdo->query("SELECT * FROM rh_documents ORDER BY upload_date DESC");
                $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $documents]);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'create' || $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $leadership_only = isset($_POST['leadership_only']) ? 1 : 0;
                
                $filePath = null;
                if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION));
                    $new_name = uniqid('rh_doc_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['file_path']['tmp_name'], $uploadDir . $new_name)) {
                        $filePath = $new_name;
                    }
                }

                if ($action === 'create') {
                    if (!$filePath) {
                        throw new Exception("O arquivo é obrigatório para novos documentos.");
                    }
                    $sql = "INSERT INTO rh_documents (title, description, file_path, leadership_only, upload_date) 
                            VALUES (:title, :description, :file_path, :leadership_only, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description,
                        ':file_path' => $filePath,
                        ':leadership_only' => $leadership_only
                    ]);
                    $response = ['success' => true, 'message' => 'Documento adicionado com sucesso!'];
                } elseif ($action === 'update') {
                    if (!$filePath) {
                        $stmtC = $pdo->prepare("SELECT file_path FROM rh_documents WHERE id = :id");
                        $stmtC->execute([':id' => $id]);
                        $current = $stmtC->fetch(PDO::FETCH_ASSOC);
                        $filePath = $current['file_path'];
                    }
                    $sql = "UPDATE rh_documents SET 
                            title = :title, 
                            description = :description, 
                            file_path = :file_path, 
                            leadership_only = :leadership_only 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description,
                        ':file_path' => $filePath,
                        ':leadership_only' => $leadership_only,
                        ':id' => $id
                    ]);
                    $response = ['success' => true, 'message' => 'Documento atualizado com sucesso!'];
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmtGet = $pdo->prepare("SELECT file_path FROM rh_documents WHERE id = :id");
                    $stmtGet->execute([':id' => $id]);
                    if ($doc = $stmtGet->fetch(PDO::FETCH_ASSOC)) {
                        if (file_exists($uploadDir . $doc['file_path'])) {
                            unlink($uploadDir . $doc['file_path']);
                        }
                    }
                    $stmt = $pdo->prepare("DELETE FROM rh_documents WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $response = ['success' => true, 'message' => 'Documento removido com sucesso!'];
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

$pageTitle = 'Gerenciar Documentos RH';
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-file-text me-2"></i> Gerenciar Documentos RH
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDoc" onclick="resetModal()">
                        <i class="ti ti-plus me-1"></i> Novo Documento
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="loadDocuments()">
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

        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title">Lista de Documentos</h3>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tabelaDocuments" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Título</th>
                                    <th>Descrição</th>
                                    <th>Arquivo</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Upload</th>
                                    <th class="w-1 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="documents-container">
                                <!-- Preenchido via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal modal-blur fade" id="modalDoc" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form class="modal-content" id="formDoc">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="docId" value="">

                <div class="mb-3">
                    <label class="form-label required">Título</label>
                    <input type="text" class="form-control" name="title" id="docTitle" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="description" id="docDesc" rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label" id="labelFile">Arquivo</label>
                            <input type="file" class="form-control" name="file_path" id="docFile">
                            <small class="form-hint">Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX.</small>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label class="form-label">Visibilidade</label>
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="leadership_only" id="docLeadership">
                                <span class="form-check-label">Apenas Liderança?</span>
                            </label>
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
    loadDocuments();

    $("#formDoc").on("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = $("#btnSave");
        
        btn.prop("disabled", true).addClass("btn-loading");

        fetch("rh_documents_admin.php?api=1", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, "success");
                bootstrap.Modal.getInstance(document.getElementById("modalDoc")).hide();
                loadDocuments();
            } else {
                showAlert(data.message, "danger");
            }
        })
        .catch(err => {
            console.error(err);
            showAlert("Erro ao processar requisição.", "danger");
        })
        .finally(() => {
            btn.prop("disabled", false).removeClass("btn-loading");
        });
    });
});

function loadDocuments() {
    fetch('rh_documents_admin.php?api=1&action=list')
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            renderTable(res.data);
        } else {
            showAlert("Erro ao carregar documentos: " + res.message, "danger");
        }
    })
    .catch(err => {
        console.error(err);
        showAlert("Erro de conexão com o servidor.", "danger");
    });
}

function renderTable(data) {
    if (datatable) {
        datatable.destroy();
    }

    const container = $("#documents-container");
    let html = "";
    
    data.forEach(item => {
        const typeBadge = item.leadership_only == 1 
            ? '<span class="badge bg-warning">Liderança</span>' 
            : '<span class="badge bg-blue">RH</span>';

        const fileLink = item.file_path 
            ? `<a href="../uploads_rh/${item.file_path}" target="_blank" class="badge bg-green-lt"><i class="ti ti-download me-1"></i> Baixar</a>`
            : '<span class="text-muted">-</span>';

        const safeData = JSON.stringify(item).replace(/'/g, "&apos;");

        html += `
            <tr>
                <td class="text-muted">#${item.id}</td>
                <td><div class="font-weight-medium">${item.title}</div></td>
                <td class="text-muted text-truncate" style="max-width: 250px;" title="${item.description || ''}">
                    ${item.description || '-'}
                </td>
                <td>${fileLink}</td>
                <td class="text-center">${typeBadge}</td>
                <td class="text-center text-muted">
                    ${new Date(item.upload_date).toLocaleString('pt-BR')}
                </td>
                <td class="text-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Ações
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick='editDoc(${safeData})'>
                                    <i class="ti ti-edit me-2"></i> Editar
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteDoc(${item.id})">
                                    <i class="ti ti-trash me-2"></i> Excluir
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    });
    
    container.html(html);

    datatable = $("#tabelaDocuments").DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        },
        pageLength: 10,
        order: [[0, "desc"]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        drawCallback: function() {
            $(".dataTables_paginate > .pagination").addClass("pagination-sm");
        }
    });
}

function resetModal() {
    document.getElementById("modalTitle").textContent = "Novo Documento";
    document.getElementById("formAction").value = "create";
    document.getElementById("docId").value = "";
    document.getElementById("formDoc").reset();
    document.getElementById("docFile").required = true;
    document.getElementById("labelFile").classList.add("required");
}

function editDoc(data) {
    document.getElementById("modalTitle").textContent = "Editar Documento";
    document.getElementById("formAction").value = "update";
    document.getElementById("docId").value = data.id;
    document.getElementById("docTitle").value = data.title;
    document.getElementById("docDesc").value = data.description || "";
    document.getElementById("docLeadership").checked = (data.leadership_only == 1);
    
    document.getElementById("docFile").required = false;
    document.getElementById("labelFile").classList.remove("required");
    
    var myModal = new bootstrap.Modal(document.getElementById("modalDoc"));
    myModal.show();
}

function deleteDoc(id) {
    if (!confirm("Tem certeza que deseja excluir este documento?")) return;

    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    fetch("rh_documents_admin.php?api=1", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, "success");
            loadDocuments();
        } else {
            showAlert(data.message, "danger");
        }
    });
}

function showAlert(msg, type = "success") {
    const icon = type === "success" ? "check" : "alert-circle";
    const html = `
        <div class="alert alert-${type} alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-${icon} me-2"></i></div>
                <div>${msg}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $("#api-alerts").html(html);
    setTimeout(() => {
        $("#api-alerts .alert").fadeOut(500, function() { $(this).remove(); });
    }, 5000);
}
</script>
JS;

require_once 'admin_layout.php';
?>
