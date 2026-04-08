<?php
/**
 * Administração do Organograma
 * Interface para gerenciar a estrutura organizacional (Tabela colaboradores)
 */

$requiredAccess = ['Organograma', 'Super Administrador'];
require_once 'check_access.php';
require_once 'config.php';

$toastMsg = '';
$toastType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add' || $action === 'update') {
            $nome = $_POST['nome'];
            $cargo = $_POST['cargo'];
            $departamento = $_POST['departamento'];
            $empresa = $_POST['empresa'];
            $tipo_contrato = $_POST['tipo_contrato'];
            $parent_id = $_POST['parent_id'] ?: null;
            $nivel_hierarquico = (int)$_POST['nivel_hierarquico'];
            $ordem_exibicao = (int)$_POST['ordem_exibicao'];
            $email = $_POST['email'] ?: null;
            $ramal = $_POST['ramal'] ?: null;
            $telefone = $_POST['telefone'] ?: null;
            $teams = $_POST['teams'] ?: null;
            $data_admissao = $_POST['data_admissao'] ?: null;
            $descricao = $_POST['descricao'] ?: null;
            $observacoes = $_POST['observacoes'] ?: null;
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            // Processar upload de foto
            $foto_nome = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $upload_dir = '../organograma/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $foto_nome = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $foto_nome;
                    
                    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                        throw new Exception("Erro ao fazer upload da foto.");
                    }
                } else {
                    throw new Exception("Formato de arquivo não permitido. Use JPG, PNG ou GIF.");
                }
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO colaboradores (nome, cargo, departamento, empresa, tipo_contrato, parent_id, nivel_hierarquico, ordem_exibicao, email, ramal, telefone, teams, data_admissao, descricao, observacoes, foto, ativo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nome, $cargo, $departamento, $empresa, $tipo_contrato, $parent_id, $nivel_hierarquico, $ordem_exibicao, $email, $ramal, $telefone, $teams, $data_admissao, $descricao, $observacoes, $foto_nome, $ativo
                ]);
                $toastMsg = "Colaborador adicionado com sucesso!";
                $toastType = "success";

            } else {
                $id = (int)$_POST['id'];
                
                $sql = "UPDATE colaboradores SET 
                            nome = ?, cargo = ?, departamento = ?, empresa = ?, tipo_contrato = ?, 
                            parent_id = ?, nivel_hierarquico = ?, ordem_exibicao = ?, 
                            email = ?, ramal = ?, telefone = ?, teams = ?, 
                            data_admissao = ?, descricao = ?, observacoes = ?, ativo = ?";
                
                $params = [$nome, $cargo, $departamento, $empresa, $tipo_contrato, $parent_id, $nivel_hierarquico, $ordem_exibicao, $email, $ramal, $telefone, $teams, $data_admissao, $descricao, $observacoes, $ativo];

                if ($foto_nome) {
                    $sql .= ", foto = ?";
                    $params[] = $foto_nome;
                }

                $sql .= " WHERE id = ?";
                $params[] = $id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $toastMsg = "Colaborador atualizado com sucesso!";
                $toastType = "success";
            }

        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $toastMsg = "Colaborador removido com sucesso!";
            $toastType = "success";
        }
    } catch (Exception $e) {
        $toastMsg = "Erro: " . $e->getMessage();
        $toastType = "danger";
    }
}

// Buscar dados
$search = $_GET['search'] ?? '';
$departamento_filter = $_GET['departamento'] ?? '';

$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(nome LIKE ? OR cargo LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($departamento_filter) {
    $where_conditions[] = "departamento = ?";
    $params[] = $departamento_filter;
}

$where_clause = implode(" AND ", $where_conditions);

$stmt = $pdo->prepare("
    SELECT o.*, p.nome as parent_nome 
    FROM colaboradores o 
    LEFT JOIN colaboradores p ON o.parent_id = p.id 
    WHERE $where_clause
    ORDER BY departamento, nivel_hierarquico, ordem_exibicao, nome
");
$stmt->execute($params);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar departamentos para filtro
$dept_stmt = $pdo->query("SELECT DISTINCT departamento FROM colaboradores WHERE ativo = 1 ORDER BY departamento");
$departamentos = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar possíveis supervisores para dropdown
$supervisores_stmt = $pdo->query("SELECT id, nome, cargo, departamento FROM colaboradores WHERE ativo = 1 ORDER BY departamento, nome");
$supervisores = $supervisores_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gerenciar Organograma';

ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-hierarchy-2 me-2"></i>
                    Gerenciar Organograma
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalColaborador" onclick="resetModal()">
                        <i class="ti ti-plus me-1"></i>
                        Adicionar Colaborador
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <?php if ($toastMsg): ?>
        <div class="alert alert-<?= $toastType ?> alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-<?= $toastType === 'success' ? 'check' : 'alert-circle' ?> icon alert-icon"></i></div>
                <div><?= htmlspecialchars($toastMsg) ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nome ou cargo...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="departamento">
                            <option value="">Todos os departamentos</option>
                            <?php foreach ($departamentos as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $dept === $departamento_filter ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ti ti-search me-1"></i>Filtrar
                        </button>
                        <a href="organograma_admin.php" class="btn btn-outline-secondary">
                            <i class="ti ti-x me-1"></i>Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Colaboradores -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-users me-2"></i>
                    Colaboradores (<?= count($colaboradores) ?>)
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="organogramaTable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Cargo</th>
                            <th>Departamento</th>
                            <th>Empresa</th>
                            <th>Status</th>
                            <th>Supervisor</th>
                            <th>Nível</th>
                            <th class="w-1">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colaboradores as $colaborador): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3" style="background-image: url(<?= !empty($colaborador['foto']) ? '../organograma/uploads/' . $colaborador['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($colaborador['nome']) . '&background=random' ?>)">
                                        <?php if (empty($colaborador['foto'])): ?>
                                            <?= strtoupper(substr($colaborador['nome'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($colaborador['nome']) ?></div>
                                        <?php if ($colaborador['email']): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($colaborador['email']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($colaborador['cargo']) ?></td>
                            <td>
                                <span class="badge bg-blue-lt"><?= htmlspecialchars($colaborador['departamento']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($colaborador['empresa']) ?></td>
                            <td>
                                <span class="badge <?= $colaborador['ativo'] ? 'bg-green' : 'bg-red' ?>">
                                    <?= $colaborador['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <?= $colaborador['parent_nome'] ? htmlspecialchars($colaborador['parent_nome']) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-gray-lt">Nível <?= $colaborador['nivel_hierarquico'] ?></span>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick='editColaborador(<?= json_encode($colaborador) ?>)'>
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteColaborador(<?= $colaborador['id'] ?>, '<?= htmlspecialchars($colaborador['nome']) ?>')">
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
    </div>
</div>

<!-- Modal Adicionar/Editar -->
<div class="modal modal-blur fade" id="modalColaborador" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Adicionar Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="colabId" value="">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Nome</label>
                                <input type="text" class="form-control" name="nome" id="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Cargo</label>
                                <input type="text" class="form-control" name="cargo" id="cargo" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Departamento</label>
                                <input type="text" class="form-control" name="departamento" id="departamento" required list="departamentos">
                                <datalist id="departamentos">
                                    <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Empresa</label>
                                <select class="form-select" name="empresa" id="empresa" required>
                                    <option value="">Selecione...</option>
                                    <option value="Grupo Barão">Grupo Barão</option>
                                    <option value="Barão">Barão</option>
                                    <option value="Toymania">Toymania</option>
                                    <option value="Alfaness">Alfaness</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Tipo de Contrato</label>
                                <select class="form-select" name="tipo_contrato" id="tipo_contrato" required>
                                    <option value="CLT">CLT</option>
                                    <option value="PJ">PJ</option>
                                    <option value="Aprendiz">Aprendiz</option>
                                    <option value="Terceirizado">Terceirizado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo" checked>
                                    <label class="form-check-label" for="ativo">Ativo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supervisor</label>
                                <select class="form-select" name="parent_id" id="parent_id">
                                    <option value="">Nenhum (Diretor/Gerente)</option>
                                    <?php foreach ($supervisores as $supervisor): ?>
                                    <option value="<?= $supervisor['id'] ?>">
                                        <?= htmlspecialchars($supervisor['nome']) ?> - <?= htmlspecialchars($supervisor['cargo']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label required">Nível Hierárquico</label>
                                <input type="number" class="form-control" name="nivel_hierarquico" id="nivel_hierarquico" min="1" max="10" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" name="ordem_exibicao" id="ordem_exibicao" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Ramal</label>
                                <input type="text" class="form-control" name="ramal" id="ramal">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone" id="telefone">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teams (URL ou ID)</label>
                                <input type="text" class="form-control" name="teams" id="teams">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data de Admissão</label>
                                <input type="date" class="form-control" name="data_admissao" id="data_admissao">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Foto</label>
                                <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div id="fotoPreview" class="mt-2" style="display:none">
                                <img src="" alt="Preview" class="avatar avatar-lg">
                             </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição (Cargo)</label>
                        <textarea class="form-control" name="descricao" id="descricao" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" id="observacoes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvar">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

$extraJS = <<<'JS'
<script>
$(document).ready(function() {
    $('#organogramaTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        order: [[2, 'asc'], [6, 'asc'], [0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });
});

function resetModal() {
    document.getElementById("modalTitle").textContent = "Adicionar Colaborador";
    document.getElementById("formAction").value = "add";
    document.getElementById("colabId").value = "";
    document.getElementById("btnSalvar").textContent = "Adicionar";
    document.getElementById("fotoPreview").style.display = "none";
    
    // Resetar campos
    document.getElementById("nome").value = "";
    document.getElementById("cargo").value = "";
    document.getElementById("departamento").value = "";
    document.getElementById("empresa").value = "";
    document.getElementById("tipo_contrato").value = "CLT";
    document.getElementById("parent_id").value = "";
    document.getElementById("nivel_hierarquico").value = "1";
    document.getElementById("ordem_exibicao").value = "0";
    document.getElementById("email").value = "";
    document.getElementById("ramal").value = "";
    document.getElementById("telefone").value = "";
    document.getElementById("teams").value = "";
    document.getElementById("data_admissao").value = "";
    document.getElementById("descricao").value = "";
    document.getElementById("observacoes").value = "";
    document.getElementById("ativo").checked = true;
}

function editColaborador(data) {
    document.getElementById("modalTitle").textContent = "Editar Colaborador";
    document.getElementById("formAction").value = "update";
    document.getElementById("colabId").value = data.id;
    document.getElementById("btnSalvar").textContent = "Salvar Alterações";
    
    // Preencher campos
    document.getElementById("nome").value = data.nome || "";
    document.getElementById("cargo").value = data.cargo || "";
    document.getElementById("departamento").value = data.departamento || "";
    document.getElementById("empresa").value = data.empresa || "";
    document.getElementById("tipo_contrato").value = data.tipo_contrato || "CLT";
    document.getElementById("parent_id").value = data.parent_id || "";
    document.getElementById("nivel_hierarquico").value = data.nivel_hierarquico || 1;
    document.getElementById("ordem_exibicao").value = data.ordem_exibicao || 0;
    document.getElementById("email").value = data.email || "";
    document.getElementById("ramal").value = data.ramal || "";
    document.getElementById("telefone").value = data.telefone || "";
    document.getElementById("teams").value = data.teams || "";
    document.getElementById("data_admissao").value = data.data_admissao || "";
    document.getElementById("descricao").value = data.descricao || "";
    document.getElementById("observacoes").value = data.observacoes || "";
    document.getElementById("ativo").checked = data.ativo == 1;

    if(data.foto) {
        document.querySelector("#fotoPreview img").src = "../organograma/uploads/" + data.foto;
        document.getElementById("fotoPreview").style.display = "block";
    } else {
        document.getElementById("fotoPreview").style.display = "none";
    }
    
    var myModal = new bootstrap.Modal(document.getElementById("modalColaborador"));
    myModal.show();
}

function deleteColaborador(id, nome) {
    if (confirm(`Tem certeza que deseja remover ${nome} do organograma?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
JS;

include 'admin_layout.php';
?>