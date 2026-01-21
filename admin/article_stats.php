<?php
/* admin/article_stats.php – Estatísticas de Artigos SPA */
$requiredAccesses = ['Visualizar Estatísticas'];
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
                $sql = "
                    SELECT
                      n.id,
                      n.titulo,
                      COALESCE(v.total_views, 0)         AS total_views,
                      COALESCE(l.total_likes, 0)         AS total_likes,
                      COALESCE(c.approved_comments, 0)   AS approved_comments,
                      COALESCE(c.pending_comments, 0)    AS pending_comments
                    FROM noticias n
                    LEFT JOIN (
                      SELECT noticia_id, COUNT(*) AS total_views
                        FROM article_views
                       GROUP BY noticia_id
                    ) v ON v.noticia_id = n.id
                    LEFT JOIN (
                      SELECT noticia_id, COUNT(*) AS total_likes
                        FROM article_likes
                       GROUP BY noticia_id
                    ) l ON l.noticia_id = n.id
                    LEFT JOIN (
                      SELECT noticia_id,
                             SUM(status = 'aprovado') AS approved_comments,
                             SUM(status = 'pendente') AS pending_comments
                        FROM article_comments
                       GROUP BY noticia_id
                    ) c ON c.noticia_id = n.id
                    ORDER BY total_views DESC
                ";
                $stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $stats]);
                exit;
            }
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
// --- End API ---

$pageTitle = 'Estatísticas de Artigos';
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-chart-bar me-2"></i>Estatísticas de Artigos
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button type="button" class="btn btn-secondary" onclick="loadStats()">
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
                        <h3 class="card-title">Estatísticas dos Artigos</h3>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tblStats" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Título</th>
                                    <th class="text-end">Visualizações</th>
                                    <th class="text-end">Curtidas</th>
                                    <th class="text-end">Coment. Aprov.</th>
                                    <th class="text-end">Coment. Pend.</th>
                                </tr>
                            </thead>
                            <tbody id="stats-container">
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
    loadStats();
});

function loadStats() {
    fetch('article_stats.php?api=1&action=list')
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            renderTable(res.data);
        } else {
            showAlert("Erro ao carregar estatísticas: " + res.message, "danger");
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

    const container = $("#stats-container");
    let html = "";
    
    data.forEach(item => {
        html += `
            <tr>
                <td class="text-center text-muted">#${item.id}</td>
                <td>
                    <a href="https://ti.grupobarao.com.br/portal/blog_post.php?id=${item.id}" target="_blank" class="text-reset">
                        ${item.titulo}
                    </a>
                </td>
                <td class="text-end">
                    <span class="badge bg-blue-lt">${item.total_views}</span>
                </td>
                <td class="text-end">
                    <span class="badge bg-red-lt">${item.total_likes}</span>
                </td>
                <td class="text-end">
                    <span class="badge bg-green-lt">${item.approved_comments}</span>
                </td>
                <td class="text-end">
                    <span class="badge bg-yellow-lt">${item.pending_comments}</span>
                </td>
            </tr>
        `;
    });
    
    container.html(html);

    datatable = $("#tblStats").DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        },
        pageLength: 10,
        order: [[2, "desc"]], // Ordena por Visualizações por padrão
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        drawCallback: function() {
            $(".dataTables_paginate > .pagination").addClass("pagination-sm");
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
