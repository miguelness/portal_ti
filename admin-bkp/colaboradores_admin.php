<?php
/**
 * Painel de Administração de Colaboradores
 * Sistema completo de CRUD para gerenciamento da tabela colaboradores
 * Framework: Tabler UI
 * Autor: Sistema TI Grupo Barão
 */

$requiredAccess = 'Gestão de Usuários';
require_once 'check_access.php';

$pageTitle = 'Gerenciar Colaboradores';

// Processamento de ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO colaboradores (ramal, nome, empresa, setor, email, telefone, teams, status, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $_POST['ramal'] ?? null,
                    $_POST['nome'],
                    $_POST['empresa'],
                    $_POST['setor'],
                    $_POST['email'] ?? null,
                    $_POST['telefone'] ?? null,
                    $_POST['teams'] ?? null,
                    $_POST['status'] ?? 'ativo',
                    $_POST['observacoes'] ?? null
                ]);
                echo json_encode(['success' => $result, 'message' => $result ? 'Colaborador criado com sucesso!' : 'Erro ao criar colaborador']);
                break;
                
            case 'update':
                $stmt = $pdo->prepare("UPDATE colaboradores SET ramal=?, nome=?, empresa=?, setor=?, email=?, telefone=?, teams=?, status=?, observacoes=? WHERE id=?");
                $result = $stmt->execute([
                    $_POST['ramal'] ?? null,
                    $_POST['nome'],
                    $_POST['empresa'],
                    $_POST['setor'],
                    $_POST['email'] ?? null,
                    $_POST['telefone'] ?? null,
                    $_POST['teams'] ?? null,
                    $_POST['status'] ?? 'ativo',
                    $_POST['observacoes'] ?? null,
                    $_POST['id']
                ]);
                echo json_encode(['success' => $result, 'message' => $result ? 'Colaborador atualizado com sucesso!' : 'Erro ao atualizar colaborador']);
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                echo json_encode(['success' => $result, 'message' => $result ? 'Colaborador excluído com sucesso!' : 'Erro ao excluir colaborador']);
                break;
                
            case 'get':
                $stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $colaborador]);
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE colaboradores SET status = CASE WHEN status = 'ativo' THEN 'inativo' ELSE 'ativo' END WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                echo json_encode(['success' => $result, 'message' => $result ? 'Status alterado com sucesso!' : 'Erro ao alterar status']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Parâmetros de busca e filtros
$search = $_GET['search'] ?? '';
$empresa_filter = $_GET['empresa'] ?? '';
$setor_filter = $_GET['setor'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Construção da query com filtros
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(nome LIKE ? OR email LIKE ? OR ramal LIKE ? OR telefone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($empresa_filter)) {
    $where_conditions[] = "empresa = ?";
    $params[] = $empresa_filter;
}

if (!empty($setor_filter)) {
    $where_conditions[] = "setor = ?";
    $params[] = $setor_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query para contar total de registros
$count_sql = "SELECT COUNT(*) FROM colaboradores $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Query principal com paginação
$sql = "SELECT * FROM colaboradores $where_clause ORDER BY nome ASC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar empresas e setores únicos para filtros
$empresas = $pdo->query("SELECT DISTINCT empresa FROM colaboradores ORDER BY empresa")->fetchAll(PDO::FETCH_COLUMN);
$setores = $pdo->query("SELECT DISTINCT setor FROM colaboradores ORDER BY setor")->fetchAll(PDO::FETCH_COLUMN);

ob_start();
?>

<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Administração</div>
                    <h2 class="page-title">
                        <i class="ti ti-users me-2"></i>
                        Gerenciar Colaboradores
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalColaborador">
                            <i class="ti ti-plus"></i>
                            Novo Colaborador
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <!-- Estatísticas -->
            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total de Colaboradores</div>
                                <div class="ms-auto lh-1">
                                    <div class="dropdown">
                                        <a class="dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="?">Ver todos</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="h1 mb-3"><?= number_format($total_records) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Colaboradores Ativos</div>
                            </div>
                            <div class="h1 mb-3 text-green">
                                <?php
                                $ativos = $pdo->query("SELECT COUNT(*) FROM colaboradores WHERE status = 'ativo'")->fetchColumn();
                                echo number_format($ativos);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Colaboradores Inativos</div>
                            </div>
                            <div class="h1 mb-3 text-red">
                                <?php
                                $inativos = $pdo->query("SELECT COUNT(*) FROM colaboradores WHERE status = 'inativo'")->fetchColumn();
                                echo number_format($inativos);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total de Empresas</div>
                            </div>
                            <div class="h1 mb-3 text-blue">
                                <?= count($empresas) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros e busca -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nome, email, ramal...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Empresa</label>
                            <select class="form-select" name="empresa">
                                <option value="">Todas</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?= htmlspecialchars($empresa) ?>" <?= $empresa_filter === $empresa ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($empresa) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Setor</label>
                            <select class="form-select" name="setor">
                                <option value="">Todos</option>
                                <?php foreach ($setores as $setor): ?>
                                    <option value="<?= htmlspecialchars($setor) ?>" <?= $setor_filter === $setor ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($setor) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Todos</option>
                                <option value="ativo" <?= $status_filter === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= $status_filter === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de colaboradores -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lista de Colaboradores</h3>
                    <div class="card-actions">
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-download"></i> Exportar CSV
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ramal</th>
                                <th>Nome</th>
                                <th>Empresa</th>
                                <th>Setor</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($colaboradores)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ti ti-users-off fs-1 mb-2"></i>
                                        <div>Nenhum colaborador encontrado</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($colaboradores as $colaborador): ?>
                                    <tr>
                                        <td><?= $colaborador['id'] ?></td>
                                        <td><?= htmlspecialchars($colaborador['ramal'] ?? '-') ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-sm me-2" style="background-image: url('https://ui-avatars.com/api/?name=<?= urlencode($colaborador['nome']) ?>&background=random')"></span>
                                                <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($colaborador['empresa']) ?></td>
                                        <td><?= htmlspecialchars($colaborador['setor']) ?></td>
                                        <td>
                                            <?php if ($colaborador['email']): ?>
                                                <a href="mailto:<?= htmlspecialchars($colaborador['email']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($colaborador['email']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($colaborador['telefone'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $colaborador['status'] === 'ativo' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($colaborador['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarColaborador(<?= $colaborador['id'] ?>)">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleStatus(<?= $colaborador['id'] ?>)">
                                                    <i class="ti ti-toggle-<?= $colaborador['status'] === 'ativo' ? 'right' : 'left' ?>"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="excluirColaborador(<?= $colaborador['id'] ?>, '<?= htmlspecialchars($colaborador['nome']) ?>')">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer d-flex align-items-center">
                        <p class="m-0 text-muted">
                            Mostrando <?= $offset + 1 ?> a <?= min($offset + $per_page, $total_records) ?> de <?= $total_records ?> registros
                        </p>
                        <ul class="pagination m-0 ms-auto">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="ti ti-chevron-left"></i> Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        Próxima <i class="ti ti-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Colaborador -->
<div class="modal modal-blur fade" id="modalColaborador" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formColaborador">
                <div class="modal-body">
                    <input type="hidden" id="colaboradorId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ramal</label>
                                <input type="text" class="form-control" id="ramal" name="ramal" placeholder="Ex: 2000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" required placeholder="Nome completo do colaborador">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Empresa <span class="text-danger">*</span></label>
                                <select class="form-select" id="empresa" name="empresa" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($empresas as $empresa): ?>
                                        <option value="<?= htmlspecialchars($empresa) ?>"><?= htmlspecialchars($empresa) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Setor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="setor" name="setor" required placeholder="Ex: TI, RH, Financeiro">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="email@empresa.com.br">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(11) 99999-9999">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teams</label>
                        <input type="text" class="form-control" id="teams" name="teams" placeholder="Link ou informações do Teams">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Observações adicionais..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$extraCSS = '
<style>
.avatar {
    border-radius: 50%;
}
.table th {
    font-weight: 600;
    border-bottom: 2px solid #e6e7e9;
}
.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 0.25rem;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
.card-actions .btn {
    margin-left: 0.5rem;
}

/* Correção simples e cirúrgica para centralização do modal */
.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100vh - 2rem);
}

/* Garantir que o modal seja scrollável */
.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
</style>';

$extraJS = '
<script>
let isEditing = false;

// Função para criar novo colaborador
function novoColaborador() {
    isEditing = false;
    document.getElementById("modalTitle").textContent = "Novo Colaborador";
    document.getElementById("formColaborador").reset();
    document.getElementById("colaboradorId").value = "";
}

// Função para editar colaborador
async function editarColaborador(id) {
    isEditing = true;
    document.getElementById("modalTitle").textContent = "Editar Colaborador";
    
    try {
        const formData = new FormData();
        formData.append("action", "get");
        formData.append("id", id);
        
        const response = await fetch(window.location.href, {
            method: "POST",
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            document.getElementById("colaboradorId").value = data.id;
            document.getElementById("ramal").value = data.ramal || "";
            document.getElementById("nome").value = data.nome || "";
            document.getElementById("empresa").value = data.empresa || "";
            document.getElementById("setor").value = data.setor || "";
            document.getElementById("email").value = data.email || "";
            document.getElementById("telefone").value = data.telefone || "";
            document.getElementById("teams").value = data.teams || "";
            document.getElementById("status").value = data.status || "ativo";
            document.getElementById("observacoes").value = data.observacoes || "";
            
            const modal = new bootstrap.Modal(document.getElementById("modalColaborador"));
            modal.show();
        } else {
            showAlert("Erro ao carregar dados do colaborador", "danger");
        }
    } catch (error) {
        showAlert("Erro de conexão", "danger");
    }
}

// Função para alternar status
async function toggleStatus(id) {
    if (!confirm("Deseja alterar o status deste colaborador?")) return;
    
    try {
        const formData = new FormData();
        formData.append("action", "toggle_status");
        formData.append("id", id);
        
        const response = await fetch(window.location.href, {
            method: "POST",
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, "success");
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, "danger");
        }
    } catch (error) {
        showAlert("Erro de conexão", "danger");
    }
}

// Função para excluir colaborador
async function excluirColaborador(id, nome) {
    if (!confirm(`Tem certeza que deseja excluir o colaborador "${nome}"?\\n\\nEsta ação não pode ser desfeita.`)) return;
    
    try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("id", id);
        
        const response = await fetch(window.location.href, {
            method: "POST",
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, "success");
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, "danger");
        }
    } catch (error) {
        showAlert("Erro de conexão", "danger");
    }
}

// Função para exibir alertas
function showAlert(message, type = "info") {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Função para garantir que o modal apareça corretamente
function configurarModal() {
    const modal = document.getElementById("modalColaborador");
    
    // Garantir que o modal tenha backdrop
    if (!modal.hasAttribute("data-bs-backdrop")) {
        modal.setAttribute("data-bs-backdrop", "true");
    }
    if (!modal.hasAttribute("data-bs-keyboard")) {
        modal.setAttribute("data-bs-keyboard", "true");
    }
}

// Event listeners
document.addEventListener("DOMContentLoaded", function() {
    // Configurar modal corretamente
    configurarModal();
    
    // Configurar modal para novo colaborador
    document.getElementById("modalColaborador").addEventListener("show.bs.modal", function(event) {
        if (!isEditing) {
            novoColaborador();
        }
    });
    
    // Event listener para quando o modal for escondido
    document.getElementById("modalColaborador").addEventListener("hidden.bs.modal", function(event) {
        // Modal fechado - limpar formulário se necessário
    });
    
    // Submissão do formulário
    document.getElementById("formColaborador").addEventListener("submit", async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const action = isEditing ? "update" : "create";
        formData.append("action", action);
        
        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message, "success");
                bootstrap.Modal.getInstance(document.getElementById("modalColaborador")).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message, "danger");
            }
        } catch (error) {
            showAlert("Erro de conexão", "danger");
        }
    });
    
    // Máscara para telefone
    const telefoneInput = document.getElementById("telefone");
    if (telefoneInput) {
        telefoneInput.addEventListener("input", function(e) {
            let value = e.target.value.replace(/\D/g, "");
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, "($1) $2");
            }
            e.target.value = value;
        });
    }
});
</script>';

require_once 'admin_layout.php';
?>