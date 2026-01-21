<?php
/* admin/comments_moderation.php – Moderação de Comentários SPA */
$requiredAccesses = ['Moderação de Comentarios'];
require_once 'check_access.php';
require_once 'config.php';

// --- API Handler ---
if (isset($_GET['api']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $_REQUEST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'list') {
                $stmt = $pdo->query("
                    SELECT
                      c.id,
                      c.noticia_id,
                      n.titulo AS noticia,
                      r.nome  AS leitor,
                      c.comment,
                      c.status,
                      c.created_at
                    FROM article_comments c
                    JOIN noticias n ON n.id = c.noticia_id
                    JOIN readers  r ON r.id = c.reader_id
                    ORDER BY FIELD(c.status,'pendente','aprovado','rejeitado'), c.created_at DESC
                ");
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $comments]);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0 && in_array($action, ['aprovar', 'inativar'], true)) {
                $newStatus = $action === 'aprovar' ? 'aprovado' : 'rejeitado';
                $stmt = $pdo->prepare("UPDATE article_comments SET status = :st WHERE id = :id");
                $stmt->execute([':st' => $newStatus, ':id' => $id]);
                $response = ['success' => true, 'message' => ($action === 'aprovar' ? 'Comentário aprovado!' : 'Comentário rejeitado!')];
            } else {
                $response['message'] = 'Ação ou ID inválido.';
            }
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
// --- End API ---

$pageTitle = 'Moderação de Comentários';
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-message-dots me-2"></i>Moderação de Comentários
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button type="button" class="btn btn-secondary" onclick="loadComments()">
                    <i class="ti ti-refresh me-1"></i> Atualizar
                </button>
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
                        <h3 class="card-title">Lista de Comentários</h3>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tblComments" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Artigo</th>
                                    <th>Leitor</th>
                                    <th>Comentário</th>
                                    <th>Data</th>
                                    <th class="text-center">Status</th>
                                    <th class="w-1 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="comments-container">
                                <!-- Preenchido via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

$extraJS = <<<'JS'
<script>
let datatable = null;

$(document).ready(function() {
    loadComments();
});

function loadComments() {
    fetch('comments_moderation.php?api=1&action=list')
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            renderTable(res.data);
        } else {
            showAlert("Erro ao carregar comentários: " + res.message, "danger");
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

    const container = $("#comments-container");
    let html = "";
    
    data.forEach(item => {
        let badge = "";
        switch (item.status) {
            case 'aprovado':
                badge = '<span class="badge bg-success">Aprovado</span>'; break;
            case 'rejeitado':
                badge = '<span class="badge bg-danger">Rejeitado</span>'; break;
            default:
                badge = '<span class="badge bg-warning text-dark">Pendente</span>'; break;
        }

        html += `
            <tr>
                <td class="text-center text-muted">#${item.id}</td>
                <td>
                    <a href="https://ti.grupobarao.com.br/portal/blog_post.php?id=${item.noticia_id}" target="_blank" class="text-reset">
                        ${item.noticia}
                    </a>
                </td>
                <td>${item.leitor}</td>
                <td><div class="text-muted" style="max-width: 300px;">${item.comment.replace(/\n/g, '<br>')}</div></td>
                <td>${new Date(item.created_at).toLocaleString('pt-BR')}</td>
                <td class="text-center">${badge}</td>
                <td class="text-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Ações
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            ${item.status !== 'aprovado' ? `
                                <li>
                                    <a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateStatus(${item.id}, 'aprovar')">
                                        <i class="ti ti-check me-2"></i> Aprovar
                                    </a>
                                </li>
                            ` : ''}
                            ${item.status !== 'rejeitado' ? `
                                <li>
                                    <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="updateStatus(${item.id}, 'inativar')">
                                        <i class="ti ti-x me-2"></i> Rejeitar
                                    </a>
                                </li>
                            ` : ''}
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    });
    
    container.html(html);

    datatable = $("#tblComments").DataTable({
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

function updateStatus(id, action) {
    const formData = new FormData();
    formData.append("action", action);
    formData.append("id", id);

    fetch("comments_moderation.php?api=1", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, "success");
            loadComments();
        } else {
            showAlert(data.message, "danger");
        }
    })
    .catch(err => {
        console.error(err);
        showAlert("Erro ao processar ação.", "danger");
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
