<?php
/**
 * Painel Administrativo - Gestão de Acessos SPA
 * Grupo Barão - Portal TI
 */

$requiredAccess = 'Acessos';
require_once 'check_access.php';
require_once 'config.php';

// --- API Handler ---
if (isset($_GET['api']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => ''];

    try {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_REQUEST['action'] ?? '';

        if ($method === 'GET') {
            if ($action === 'list') {
                $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($users as &$user) {
                    $sqlAccess = "SELECT a.access_name FROM user_access ua
                                  JOIN accesses a ON ua.access_id = a.id
                                  WHERE ua.user_id = :user_id";
                    $stmtAccess = $pdo->prepare($sqlAccess);
                    $stmtAccess->execute([':user_id' => $user['id']]);
                    $user['accesses'] = $stmtAccess->fetchAll(PDO::FETCH_COLUMN);
                }
                
                echo json_encode(['success' => true, 'data' => $users]);
                exit;
            } elseif ($action === 'stats') {
                $total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $withAccess = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_access")->fetchColumn();
                $withoutAccess = $total - $withAccess;
                echo json_encode(['success' => true, 'data' => [
                    'total' => $total,
                    'with_access' => $withAccess,
                    'without_access' => $withoutAccess
                ]]);
                exit;
            } elseif ($action === 'available_accesses') {
                $user_id = (int)($_GET['user_id'] ?? 0);
                
                $sqlAll = "SELECT * FROM accesses ORDER BY access_name ASC";
                $all_accesses = $pdo->query($sqlAll)->fetchAll(PDO::FETCH_ASSOC);
                
                $current_accesses = [];
                if ($user_id > 0) {
                    $sqlUserAccess = "SELECT access_id FROM user_access WHERE user_id = :user_id";
                    $stmtUserAccess = $pdo->prepare($sqlUserAccess);
                    $stmtUserAccess->execute([':user_id' => $user_id]);
                    $current_accesses = $stmtUserAccess->fetchAll(PDO::FETCH_COLUMN);
                }

                // Agrupamento por categoria
                $categorias_map = [
                    'Administração' => ['Super Administrador', 'Acessos', 'Gestão de Usuários', 'Organograma'],
                    'Conteúdo' => ['Feeds TI', 'Feeds RH', 'Alertas', 'Links', 'Gestão de Menu'],
                    'Documentos' => ['Documentos RH', 'Documentos Liderança'],
                    'Moderação' => ['Moderar Comentários', 'Reports'],
                    'Colaboradores' => ['Colaboradores'],
                    'Relatórios' => ['Estatisticas de Artigos']
                ];
                
                $organizado = [];
                foreach ($all_accesses as $access) {
                    $cat = 'Outros';
                    foreach ($categorias_map as $c => $nomes) {
                        if (in_array($access['access_name'], $nomes)) { $cat = $c; break; }
                    }
                    $access['checked'] = in_array($access['id'], $current_accesses);
                    $organizado[$cat][] = $access;
                }
                
                echo json_encode(['success' => true, 'data' => $organizado]);
                exit;
            }
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $action = $input['action'] ?? '';
            
            if ($action === 'save') {
                $user_id = (int)($input['user_id'] ?? 0);
                $accesses = $input['accesses'] ?? []; 

                if ($user_id <= 0) throw new Exception("Usuário inválido.");

                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("DELETE FROM user_access WHERE user_id = ?");
                $stmt->execute([$user_id]);

                if (!empty($accesses)) {
                    $stmt = $pdo->prepare("INSERT INTO user_access (user_id, access_id) VALUES (?, ?)");
                    foreach ($accesses as $access_id) {
                        $stmt->execute([$user_id, $access_id]);
                    }
                }
                
                $pdo->commit();
                $response = ['success' => true, 'message' => 'Acessos atualizados!'];
            }
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
// --- End API ---

$pageTitle = 'Gestão de Acessos';
ob_start(); ?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-shield-lock me-2"></i>Gestão de Acessos
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-secondary" onclick="refreshAll()">
                        <i class="ti ti-refresh me-1"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div id="api-alerts"></div>

        <div class="row row-cards mb-3">
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar"><i class="ti ti-users"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium" id="stat-total">0</div>
                                <div class="text-muted">Total de Usuários</div>
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
                                <span class="bg-green text-white avatar"><i class="ti ti-lock-access"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-green" id="stat-with-access">0</div>
                                <div class="text-muted">Com Acesso Definido</div>
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
                                <span class="bg-warning text-white avatar"><i class="ti ti-lock-open"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-warning" id="stat-without-access">0</div>
                                <div class="text-muted">Sem Nenhum Acesso</div>
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
                        <h3 class="card-title">Permissões por Usuário</h3>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tblAccesses" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Acessos Atuais</th>
                                    <th class="w-1 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="accesses-container">
                                <!-- AJAX content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-edit-access" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form class="modal-content" id="form-edit-access">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modal-title">Editar Acessos</h5>
                    <div class="text-muted small" id="modal-user-name"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="available-accesses-list" style="max-height: 60vh; overflow-y: auto;">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="user_id" id="edit-user-id">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary ms-auto" id="btnSave">
                    <i class="ti ti-check me-1"></i> Salvar Alterações
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

    $("#form-edit-access").on("submit", function(e) {
        e.preventDefault();
        const userId = $("#edit-user-id").val();
        const selectedAccesses = [];
        $(this).find("input[name='accesses[]']:checked").each(function() {
            selectedAccesses.push($(this).val());
        });

        const btn = $("#btnSave");
        btn.prop("disabled", true).addClass("btn-loading");

        fetch("accesses_admin.php?api=1", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "save", user_id: userId, accesses: selectedAccesses })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showAlert(res.message, "success");
                bootstrap.Modal.getInstance(document.getElementById("modal-edit-access")).hide();
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
    loadUsers();
}

function loadStats() {
    fetch('accesses_admin.php?api=1&action=stats')
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            $("#stat-total").text(res.data.total);
            $("#stat-with-access").text(res.data.with_access);
            $("#stat-without-access").text(res.data.without_access);
        }
    });
}

function loadUsers() {
    fetch('accesses_admin.php?api=1&action=list')
    .then(res => res.json())
    .then(res => {
        if (res.success) renderTable(res.data);
    });
}

function renderTable(data) {
    if (datatable) datatable.destroy();

    const container = $("#accesses-container");
    let html = "";
    
    data.forEach(item => {
        const initials = item.username[0].toUpperCase();
        const accessesBadges = item.accesses.length > 0 
            ? item.accesses.map(a => `<span class="badge bg-azure-lt me-1 mb-1">${a}</span>`).join('')
            : '<span class="text-muted small">Nenhum privilégio administrativo</span>';

        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm me-2 bg-blue-lt">${initials}</span>
                        <div class="flex-fill">
                            <div class="font-weight-medium">${item.username}</div>
                            <div class="text-muted small">ID #${item.id}</div>
                        </div>
                    </div>
                </td>
                <td style="max-width: 400px; white-space: normal;">${accessesBadges}</td>
                <td class="text-center">
                    <button class="btn btn-outline-primary btn-sm" onclick="editAccess(${item.id}, '${item.username}')">
                        <i class="ti ti-edit me-1"></i> Editar
                    </button>
                </td>
            </tr>
        `;
    });
    
    container.html(html);

    datatable = $("#tblAccesses").DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
        pageLength: 25,
        order: [[0, "asc"]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });
}

function editAccess(userId, userName) {
    $("#edit-user-id").val(userId);
    $("#modal-user-name").text(userName);
    $("#available-accesses-list").html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>');
    
    const modal = new bootstrap.Modal(document.getElementById("modal-edit-access"));
    modal.show();

    fetch(`accesses_admin.php?api=1&action=available_accesses&user_id=${userId}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            let html = "";
            for (const [cat, items] of Object.entries(res.data)) {
                const catId = cat.toLowerCase().replace(/\s+/g, '_').normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                html += `
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <h4 class="card-title text-primary mb-0">${cat}</h4>
                            <button type="button" class="btn btn-sm btn-ghost-primary" onclick="toggleCategory('${catId}')">
                                Marcar Todos
                            </button>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                ${items.map(acc => `
                                    <div class="col-md-6 mb-2">
                                        <label class="form-check cursor-pointer mb-0">
                                            <input class="form-check-input cat-check-${catId}" type="checkbox" name="accesses[]" value="${acc.id}" ${acc.checked ? 'checked' : ''}>
                                            <span class="form-check-label">
                                                <strong>${acc.access_name}</strong>
                                                ${acc.description ? `<br><small class="text-muted d-block" style="line-height:1">${acc.description}</small>` : ''}
                                            </span>
                                        </label>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
            }
            $("#available-accesses-list").html(html);
        }
    });
}

function toggleCategory(catId) {
    const checks = $(`.cat-check-${catId}`);
    const allChecked = Array.from(checks).every(c => c.checked);
    checks.prop('checked', !allChecked);
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
