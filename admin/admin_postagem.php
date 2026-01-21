<?php
$requiredAccesses = ['Feeds TI', 'Feeds RH'];
require_once 'check_access.php';
require_once 'config.php';

$pageTitle = 'Gerenciar Posts';

$toastMsg = '';
$toastType = '';

// Diretório de Uploads
$uploadBase = '../uploads/';
if (!is_dir($uploadBase)) {
    mkdir($uploadBase, 0777, true);
}

// --- NOVO: Handler de API para SPA ---
if (isset($_GET['api']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $_REQUEST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'list') {
                $pagina = (int)($_GET['pagina'] ?? 1);
                $limite = (int)($_GET['limite'] ?? 1000); // Aumentado para o DataTables lidar com a lista completa se preferir
                $offset = ($pagina - 1) * $limite;

                $where = [];
                if (!in_array('Feeds TI', $user_accesses)) {
                    $where[] = "categoria = 'RH'";
                } elseif (!in_array('Feeds RH', $user_accesses)) {
                    $where[] = "categoria != 'RH'";
                }
                $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

                $stmt = $pdo->prepare("SELECT * FROM noticias $whereClause ORDER BY data_publicacao DESC LIMIT :limite OFFSET :offset");
                $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $total_stmt = $pdo->query("SELECT COUNT(*) FROM noticias $whereClause");
                $total_noticias = (int)$total_stmt->fetchColumn();
                $total_paginas = ceil($total_noticias / $limite);

                echo json_encode([
                    'success' => true,
                    'data' => $noticias,
                    'pagination' => [
                        'current' => $pagina,
                        'total' => $total_paginas,
                        'total_items' => $total_noticias
                    ]
                ]);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'create' || $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $titulo = $_POST['titulo'] ?? '';
                $conteudo = $_POST['conteudo'] ?? '';
                $categoria = $_POST['categoria'] ?? '';
                $status = $_POST['status'] ?? 'ativo';
                $data_publicacao = date('Y-m-d H:i:s');
                
                $imagem_url = null;
                if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                    $new_name = uniqid('post_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadBase . $new_name)) {
                        $imagem_url = 'uploads/' . $new_name;
                    }
                }
                
                if ($action === 'create') {
                    $sql = "INSERT INTO noticias (titulo, conteudo, categoria, imagem, status, data_publicacao) 
                            VALUES (:t, :c, :cat, :img, :s, :d)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':t' => $titulo,
                        ':c' => $conteudo,
                        ':cat' => $categoria,
                        ':img' => $imagem_url ?: '',
                        ':s' => $status,
                        ':d' => $data_publicacao
                    ]);
                    $response = ['success' => true, 'message' => 'Postagem criada com sucesso!'];
                } elseif ($action === 'update') {
                    if (!$imagem_url) {
                        $stmtGet = $pdo->prepare("SELECT imagem FROM noticias WHERE id = :id");
                        $stmtGet->execute([':id' => $id]);
                        $old = $stmtGet->fetch(PDO::FETCH_ASSOC);
                        $imagem_url = $old['imagem'];
                    }
                    
                    $sql = "UPDATE noticias SET titulo = :t, conteudo = :c, categoria = :cat, imagem = :img, status = :s WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':t' => $titulo,
                        ':c' => $conteudo,
                        ':cat' => $categoria,
                        ':img' => $imagem_url,
                        ':s' => $status,
                        ':id' => $id
                    ]);
                    $response = ['success' => true, 'message' => 'Postagem atualizada com sucesso!'];
                }
            } elseif ($action === 'delete') {
                $id = (int)$_POST['id'];
                $stmtGet = $pdo->prepare("SELECT imagem FROM noticias WHERE id = :id");
                $stmtGet->execute([':id' => $id]);
                $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
                
                if ($row && $row['imagem'] && file_exists('../' . $row['imagem'])) {
                    @unlink('../' . $row['imagem']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $response = ['success' => true, 'message' => 'Postagem removida com sucesso!'];
            } elseif ($action === 'toggle_status') {
                $id = (int)$_POST['id'];
                $newStatus = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE noticias SET status = :s WHERE id = :id");
                $stmt->execute([':s' => $newStatus, ':id' => $id]);
                $response = ['success' => true, 'message' => 'Status atualizado!'];
            }
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}
// --- FIM API ---

// Mantenha apenas a inicialização básica para o primeiro load da página (opcional, ou carregue tudo via JS)
$noticias = []; 
$total_paginas = 0;
$pagina = 1;

ob_start(); 
?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-rss me-2"></i>Gerenciar Posts
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPost" onclick="resetModal()">
                    <i class="ti ti-plus me-1"></i> Nova Postagem
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
                        <h3 class="card-title">Lista de Posts</h3>
                        <div class="card-actions">
                            <button class="btn btn-icon btn-ghost-secondary" onclick="loadPosts()" title="Atualizar">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tabelaNoticias" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Título</th>
                                    <th>Categoria</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th class="w-1">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="posts-container">
                                <!-- Preenchido dinamicamente pelo DataTables -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer d-none" id="pagination-container">
                        <!-- Removido para usar a paginação do DataTables -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar/Editar -->
<div class="modal modal-blur fade" id="modalPost" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form class="modal-content" id="formPost" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nova Postagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="postId" value="">
                
                <div class="mb-3">
                    <label class="form-label required">Título</label>
                    <input type="text" class="form-control" name="titulo" id="titulo" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Categoria</label>
                            <select class="form-select" name="categoria" id="categoria" required>
                                <?php foreach ($categorias_disponiveis as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imagem de Capa</label>
                    <input type="file" class="form-control" name="imagem" id="imagem">
                    <small class="form-hint">Deixe em branco para manter a atual (na edição).</small>
                    <div id="imgPreview" class="mt-2 d-none">
                        <img src="" style="max-height: 100px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Conteúdo</label>
                    <textarea class="form-control" name="conteudo" id="conteudo" rows="6"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="btnLoading"></span>
                    Salvar Postagem
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.tiny.cloud/1/vug13kx9uqadf7chf6kqz4wpxja6senvj4h3anvnw56cj67z/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<?php 
$content = ob_get_clean();

$extraJS = <<<'JS'
<script>
let datatable = null;

$(document).ready(function() {
    initTinyMCE();
    loadPosts();

    // Form Submit Handler
    $("#formPost").on("submit", function(e) {
        e.preventDefault();
        
        if (tinymce.get("conteudo")) {
            tinymce.get("conteudo").save();
        }

        const formData = new FormData(this);
        const btn = $("#btnSalvar");
        const loading = $("#btnLoading");

        btn.prop("disabled", true);
        loading.removeClass("d-none");

        fetch("admin_postagem.php?api=1", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, "success");
                bootstrap.Modal.getInstance(document.getElementById("modalPost")).hide();
                loadPosts();
            } else {
                showAlert(data.message || "Erro ao salvar", "danger");
            }
        })
        .catch(err => {
            console.error(err);
            showAlert("Erro na requisição.", "danger");
        })
        .finally(() => {
            btn.prop("disabled", false);
            loading.addClass("d-none");
        });
    });
});

function initTinyMCE() {
    tinymce.init({
        selector: '#conteudo',
        plugins: 'link image code lists',
        toolbar: 'undo redo | bold italic | bullist numlist | link | code',
        height: 300,
        menubar: false,
        branding: false,
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        }
    });

    document.addEventListener('focusin', function (e) {
        if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
            e.stopImmediatePropagation();
        }
    });
}

function loadPosts() {
    fetch(`admin_postagem.php?api=1&action=list&limite=5000`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            renderTable(res.data);
        } else {
            showAlert("Erro ao carregar dados: " + res.message, "danger");
        }
    })
    .catch(err => {
        console.error(err);
        showAlert("Erro ao conectar com o servidor.", "danger");
    });
}

function renderTable(data) {
    if (datatable) {
        datatable.destroy();
    }

    const container = $("#posts-container");
    let html = "";
    
    data.forEach(item => {
        const badgeClass = item.status === 'ativo' ? 'bg-success' : 'bg-danger';
        const statusLabel = item.status === 'ativo' ? 'Ativo' : 'Inativo';
        
        const catColors = {
            'Maxtrade': 'blue',
            'Portal': 'cyan',
            'RH': 'green'
        };
        const catColor = catColors[item.categoria] || 'secondary';

        const safeData = JSON.stringify({
            id: item.id,
            titulo: item.titulo,
            categoria: item.categoria,
            status: item.status,
            conteudo: item.conteudo,
            imagem: item.imagem
        }).replace(/'/g, "&apos;");

        html += `
            <tr id="post-${item.id}">
                <td class="text-muted">#${item.id}</td>
                <td>
                    <div class="font-weight-medium text-truncate" style="max-width: 250px;" title="${item.titulo}">
                        ${item.titulo}
                    </div>
                </td>
                <td>
                    <span class="badge bg-${catColor}-lt">${item.categoria}</span>
                </td>
                <td class="text-muted">
                    ${new Date(item.data_publicacao).toLocaleDateString('pt-BR')}
                </td>
                <td>
                    <a href="javascript:void(0)" class="status-toggle text-decoration-none" onclick="toggleStatus(${item.id}, '${item.status}')">
                        <span class="badge ${badgeClass}">${statusLabel}</span>
                    </a>
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Ações
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick='editPost(${safeData})'>
                                    <i class="ti ti-edit me-2"></i> Editar
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deletePost(${item.id})">
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

    // Inicializa DataTables com tradução para PT-BR e estilos Tabler
    datatable = $("#tabelaNoticias").DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        },
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, "desc"]], // Ordena por ID decrescente por padrão
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

function resetModal() {
    document.getElementById("modalTitle").textContent = "Nova Postagem";
    document.getElementById("formAction").value = "create";
    document.getElementById("postId").value = "";
    document.getElementById("formPost").reset();
    document.getElementById("imgPreview").classList.add("d-none");
    
    if (tinymce.get("conteudo")) {
        tinymce.get("conteudo").setContent("");
    }
}

function editPost(data) {
    document.getElementById("modalTitle").textContent = "Editar Postagem";
    document.getElementById("formAction").value = "update";
    document.getElementById("postId").value = data.id;
    document.getElementById("titulo").value = data.titulo;
    document.getElementById("categoria").value = data.categoria;
    document.getElementById("status").value = data.status;
    document.getElementById("imagem").value = "";
    
    if (data.imagem) {
        var imgEl = document.querySelector("#imgPreview img");
        imgEl.src = "../" + data.imagem;
        document.getElementById("imgPreview").classList.remove("d-none");
    } else {
        document.getElementById("imgPreview").classList.add("d-none");
    }
    
    if (tinymce.get("conteudo")) {
        tinymce.get("conteudo").setContent(data.conteudo || "");
    } else {
        document.getElementById("conteudo").value = data.conteudo || "";
    }
    
    var myModal = new bootstrap.Modal(document.getElementById("modalPost"));
    myModal.show();
}

function toggleStatus(id, currentStatus) {
    const nextStatus = currentStatus === "ativo" ? "inativo" : "ativo";
    
    const formData = new FormData();
    formData.append("action", "toggle_status");
    formData.append("id", id);
    formData.append("status", nextStatus);

    fetch("admin_postagem.php?api=1", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadPosts();
        } else {
            showAlert(data.message, "danger");
        }
    });
}

function deletePost(id) {
    if (!confirm("Tem certeza que deseja excluir esta postagem?")) return;

    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    fetch("admin_postagem.php?api=1", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, "success");
            loadPosts();
        } else {
            showAlert(data.message, "danger");
        }
    });
}
</script>
JS;

include 'admin_layout.php';
?>
