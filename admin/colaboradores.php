<?php
/**
 * Painel Administrativo - Gerenciamento de Colaboradores SPA
 * Grupo Barão - Portal TI
 */

$requiredAccess = ['Colaboradores', 'Gestão de Colaboradores', 'Gestão de Usuários'];
require_once 'check_access.php';
require_once 'config.php';

// --- API Handler ---
if (isset($_GET['api']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => ''];

    try {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_REQUEST['action'] ?? '';

        // Helper functions from original api/colaboradores.php
        $registrarHistorico = function($colaborador_id, $acao, $dados_anteriores = null, $dados_novos = null) use ($pdo) {
            $usuario_id = $_SESSION['user_id'] ?? null;
            $usuario_nome = $_SESSION['nome'] ?? 'Sistema';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $pdo->prepare("
                INSERT INTO colaboradores_historico 
                (colaborador_id, acao, dados_anteriores, dados_novos, usuario_id, usuario_nome, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $colaborador_id,
                $acao,
                $dados_anteriores ? json_encode($dados_anteriores) : null,
                $dados_novos ? json_encode($dados_novos) : null,
                $usuario_id,
                $usuario_nome,
                $ip_address,
                $user_agent
            ]);
        };

        if ($method === 'GET') {
            if ($action === 'list') {
                $stmt = $pdo->query("SELECT * FROM colaboradores ORDER BY nome ASC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                exit;
            } elseif ($action === 'stats') {
                $stats = [];
                $stats['total'] = $pdo->query("SELECT COUNT(*) FROM colaboradores")->fetchColumn();
                $stats['ativos'] = $pdo->query("SELECT COUNT(*) FROM colaboradores WHERE status = 'ativo'")->fetchColumn();
                $stats['inativos'] = $stats['total'] - $stats['ativos'];
                echo json_encode(['success' => true, 'data' => $stats]);
                exit;
            } elseif ($action === 'aux') {
                $tipo = $_GET['tipo'] ?? '';
                if ($tipo === 'empresas') {
                    $stmt = $pdo->query("SELECT DISTINCT empresa FROM colaboradores WHERE empresa != '' ORDER BY empresa ASC");
                    $res = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo json_encode(['success' => true, 'data' => $res]);
                    exit;
                } elseif ($tipo === 'setores') {
                    $stmt = $pdo->query("SELECT DISTINCT setor FROM colaboradores WHERE setor != '' ORDER BY setor ASC");
                    $res = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo json_encode(['success' => true, 'data' => $res]);
                    exit;
                }
            } elseif ($action === 'detail' && isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $colab = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($colab) {
                    echo json_encode(['success' => true, 'data' => $colab]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Não encontrado']);
                }
                exit;
            }
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            
            $nome = $input['nome'] ?? '';
            $ramal = $input['ramal'] ?? '';
            $empresa = $input['empresa'] ?? '';
            $setor = $input['setor'] ?? '';
            $email = $input['email'] ?? '';
            $telefone = $input['telefone'] ?? '';
            $teams = $input['teams'] ?? '';
            $status = $input['status'] ?? 'ativo';
            $observacoes = $input['observacoes'] ?? '';

            if (empty($nome) || empty($empresa) || empty($setor) || empty($email)) {
                throw new Exception("Campos obrigatórios faltando.");
            }

            if ($id > 0) {
                // Update
                $stmtPrev = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
                $stmtPrev->execute([$id]);
                $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

                $sql = "UPDATE colaboradores SET ramal=?, nome=?, empresa=?, setor=?, email=?, telefone=?, teams=?, status=?, observacoes=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ramal,$nome,$empresa,$setor,$email,$telefone,$teams,$status,$observacoes, $id]);
                
                $registrarHistorico($id, 'atualizado', $prev, $input);
                $response = ['success' => true, 'message' => 'Colaborador atualizado!'];
            } else {
                // Create
                $sql = "INSERT INTO colaboradores (ramal, nome, empresa, setor, email, telefone, teams, status, observacoes) VALUES (?,?,?,?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ramal,$nome,$empresa,$setor,$email,$telefone,$teams,$status,$observacoes]);
                $newId = $pdo->lastInsertId();
                
                $registrarHistorico($newId, 'criado', null, $input);
                $response = ['success' => true, 'message' => 'Colaborador criado!'];
            }
        }

        if ($method === 'DELETE' || $action === 'delete') {
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $stmtPrev = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
                $stmtPrev->execute([$id]);
                $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

                $registrarHistorico($id, 'excluido', $prev, null);
                $pdo->prepare("DELETE FROM colaboradores WHERE id = ?")->execute([$id]);
                $response = ['success' => true, 'message' => 'Colaborador removido!'];
            }
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
// --- End API ---

$pageTitle = 'Gerenciar Colaboradores';
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-users me-2"></i>Gerenciar Colaboradores
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-colaborador" onclick="resetForm()">
                        <i class="ti ti-plus me-1"></i> Novo Colaborador
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
                                <span class="bg-primary text-white avatar"><i class="ti ti-users"></i></span>
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
                                <span class="bg-red text-white avatar"><i class="ti ti-user-x"></i></span>
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
                        <h3 class="card-title">Lista de Colaboradores</h3>
                    </div>
                    <div class="table-responsive p-3" style="min-height: 200px;">
                        <table id="tblColaboradores" class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Colaborador</th>
                                    <th class="w-1">Ramal</th>
                                    <th>Empresa/Setor</th>
                                    <th>E-mail</th>
                                    <th class="text-center">Status</th>
                                    <th class="w-1 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="colaboradores-container">
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
<div class="modal modal-blur fade" id="modal-colaborador" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form class="modal-content" id="form-colaborador">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Novo Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="colab-id">
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label required">Nome Completo</label>
                            <input type="text" class="form-control" name="nome" id="colab-nome" required>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label class="form-label">Ramal</label>
                            <input type="text" class="form-control" name="ramal" id="colab-ramal">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label required">Empresa</label>
                            <input type="text" class="form-control" name="empresa" id="colab-empresa" list="list-empresas" required>
                            <datalist id="list-empresas"></datalist>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label required">Setor</label>
                            <input type="text" class="form-control" name="setor" id="colab-setor" list="list-setores" required>
                            <datalist id="list-setores"></datalist>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label required">E-mail</label>
                            <input type="email" class="form-control" name="email" id="colab-email" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone" id="colab-telefone">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label">Teams (ID Convite)</label>
                            <input type="text" class="form-control" name="teams" id="colab-teams">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="colab-status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observacoes" id="colab-obs" rows="3"></textarea>
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

    $("#form-colaborador").on("submit", function(e) {
        e.preventDefault();
        const data = {};
        $(this).serializeArray().forEach(item => data[item.name] = item.value);

        const btn = $("#btnSave");
        btn.prop("disabled", true).addClass("btn-loading");

        fetch("colaboradores.php?api=1", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showAlert(res.message, "success");
                bootstrap.Modal.getInstance(document.getElementById("modal-colaborador")).hide();
                refreshAll();
            } else {
                showAlert(res.message, "danger");
            }
        })
        .catch(err => { console.error(err); showAlert("Erro ao salvar", "danger"); })
        .finally(() => btn.prop("disabled", false).removeClass("btn-loading"));
    });
});

function refreshAll() {
    loadStats();
    loadColaboradores();
    loadAux();
}

function loadStats() {
    fetch('colaboradores.php?api=1&action=stats')
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            $("#stat-total").text(res.data.total);
            $("#stat-ativos").text(res.data.ativos);
            $("#stat-inativos").text(res.data.inativos);
        }
    });
}

function loadAux() {
    fetch('colaboradores.php?api=1&action=aux&tipo=empresas').then(r=>r.json()).then(r => {
        $("#list-empresas").html(r.data.map(e => `<option value="${e}">`).join(''));
    });
    fetch('colaboradores.php?api=1&action=aux&tipo=setores').then(r=>r.json()).then(r => {
        $("#list-setores").html(r.data.map(e => `<option value="${e}">`).join(''));
    });
}

function loadColaboradores() {
    fetch('colaboradores.php?api=1&action=list')
    .then(res => res.json())
    .then(res => {
        if (res.success) renderTable(res.data);
        else showAlert("Erro ao carregar lista", "danger");
    })
    .catch(err => showAlert("Erro de conexão", "danger"));
}

function renderTable(data) {
    if (datatable) datatable.destroy();

    const container = $("#colaboradores-container");
    let html = "";
    
    data.forEach(item => {
        const statusBadge = item.status === 'ativo' ? 'bg-green' : 'bg-red';
        const initials = item.nome.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm me-2 bg-blue-lt">${initials}</span>
                        <div class="flex-fill">
                            <div class="font-weight-medium">${item.nome}</div>
                        </div>
                    </div>
                </td>
                <td><span class="text-muted">${item.ramal || '-'}</span></td>
                <td>
                    <div class="small">${item.empresa}</div>
                    <div class="text-muted small">${item.setor}</div>
                </td>
                <td><a href="mailto:${item.email}" class="text-reset">${item.email}</a></td>
                <td class="text-center">
                    <span class="badge ${statusBadge}">${item.status === 'ativo' ? 'Ativo' : 'Inativo'}</span>
                </td>
                <td class="text-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Ações</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="editColab(${item.id})"><i class="ti ti-edit me-2"></i> Editar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteColab(${item.id})"><i class="ti ti-trash me-2"></i> Excluir</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    });
    
    container.html(html);

    datatable = $("#tblColaboradores").DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
        pageLength: 25,
        order: [[0, "asc"]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        drawCallback: function() { $(".dataTables_paginate > .pagination").addClass("pagination-sm"); }
    });
}

function resetForm() {
    $("#modal-title").text("Novo Colaborador");
    $("#colab-id").val("");
    $("#form-colaborador")[0].reset();
}

function editColab(id) {
    fetch(`colaboradores.php?api=1&action=detail&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const d = res.data;
            $("#modal-title").text("Editar Colaborador");
            $("#colab-id").val(d.id);
            $("#colab-nome").val(d.nome);
            $("#colab-ramal").val(d.ramal);
            $("#colab-empresa").val(d.empresa);
            $("#colab-setor").val(d.setor);
            $("#colab-email").val(d.email);
            $("#colab-telefone").val(d.telefone);
            $("#colab-teams").val(d.teams);
            $("#colab-status").val(d.status);
            $("#colab-obs").val(d.observacoes);
            
            new bootstrap.Modal(document.getElementById("modal-colaborador")).show();
        }
    });
}

function deleteColab(id) {
    if (!confirm("Excluir este colaborador?")) return;
    fetch(`colaboradores.php?api=1&action=delete&id=${id}`, { method: "DELETE" })
    .then(r => r.json())
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