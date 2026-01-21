<?php
require_once 'config.php';

// Verifica se está logado e tem acesso
requireLogin();
if (!hasAccess('Gestão de Usuários', $user_accesses)) {
    header('Location: index.php');
    exit;
}

// Título da página
$pageTitle = 'Gestão de Usuários';

// Processa ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                // Verifica se o usuário já existe
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$_POST['username']]);
                if ($stmt->fetch()) {
                    throw new Exception('Nome de usuário já existe!');
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username, password, name, email, status, 
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'ativo', NOW(), NOW())
                ");
                $stmt->execute([
                    $_POST['username'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['name'],
                    $_POST['email']
                ]);
                $userId = $pdo->lastInsertId();
                
                // Adiciona acessos
                if (!empty($_POST['accesses'])) {
                    $stmt = $pdo->prepare("INSERT INTO user_access (user_id, access_name) VALUES (?, ?)");
                    foreach ($_POST['accesses'] as $access) {
                        $stmt->execute([$userId, $access]);
                    }
                }
                
                $message = 'Usuário adicionado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        name = ?, email = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['id']
                ]);
                
                // Atualiza senha se fornecida
                if (!empty($_POST['password'])) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([
                        password_hash($_POST['password'], PASSWORD_DEFAULT),
                        $_POST['id']
                    ]);
                }
                
                // Atualiza acessos
                $stmt = $pdo->prepare("DELETE FROM user_access WHERE user_id = ?");
                $stmt->execute([$_POST['id']]);
                
                if (!empty($_POST['accesses'])) {
                    $stmt = $pdo->prepare("INSERT INTO user_access (user_id, access_name) VALUES (?, ?)");
                    foreach ($_POST['accesses'] as $access) {
                        $stmt->execute([$_POST['id'], $access]);
                    }
                }
                
                $message = 'Usuário atualizado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("UPDATE users SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = 'Usuário removido com sucesso!';
                $messageType = 'success';
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['id']]);
                $message = 'Status atualizado com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Busca usuários
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT u.*, GROUP_CONCAT(ua.access_name) as accesses 
        FROM users u 
        LEFT JOIN user_access ua ON u.id = ua.user_id 
        WHERE 1=1";
$params = [];

if ($status) {
    $sql .= " AND u.status = ?";
    $params[] = $status;
}

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY u.id ORDER BY u.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Busca acessos disponíveis
$acessos = $pdo->query("SELECT DISTINCT access_name FROM accesses ORDER BY access_name")->fetchAll(PDO::FETCH_COLUMN);

// Header da página
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <div class="page-pretitle">Gestão</div>
        <h2 class="page-title">Usuários do Sistema</h2>
    </div>
    <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                <i class="ti ti-plus me-1"></i>
                Novo Usuário
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
            <div class="col-md-5">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nome, usuário ou email...">
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

<!-- Lista de Usuários -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Usuários Cadastrados (<?= count($usuarios) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="tabelaUsuarios">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Acessos</th>
                    <th>Status</th>
                    <th>Atualizado</th>
                    <th class="w-1">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td>
                        <div class="d-flex py-1 align-items-center">
                            <span class="avatar me-2">
                                <?= strtoupper(substr($usuario['name'], 0, 2)) ?>
                            </span>
                            <div class="flex-fill">
                                <div class="font-weight-medium"><?= htmlspecialchars($usuario['name']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($usuario['username']) ?></td>
                    <td>
                        <?php if ($usuario['email']): ?>
                        <a href="mailto:<?= htmlspecialchars($usuario['email']) ?>">
                            <?= htmlspecialchars($usuario['email']) ?>
                        </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($usuario['accesses']): ?>
                            <?php foreach (explode(',', $usuario['accesses']) as $access): ?>
                            <span class="badge bg-blue-lt me-1"><?= htmlspecialchars($access) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">Nenhum acesso</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                            <input type="hidden" name="status" value="<?= $usuario['status'] === 'ativo' ? 'inativo' : 'ativo' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $usuario['status'] === 'ativo' ? 'success' : 'secondary' ?>">
                                <?= $usuario['status'] === 'ativo' ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </td>
                    <td><?= date('d/m/Y', strtotime($usuario['updated_at'])) ?></td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editarUsuario(<?= htmlspecialchars(json_encode($usuario)) ?>)">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="excluirUsuario(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['name']) ?>')">
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

<!-- Modal Usuário -->
<div class="modal modal-blur fade" id="modalUsuario" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUsuario" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="usuarioId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Nome Completo</label>
                                <input type="text" class="form-control" name="name" id="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Nome de Usuário</label>
                                <input type="text" class="form-control" name="username" id="username" required>
                                <div class="form-hint">Usado para login no sistema</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" id="passwordLabel">Senha</label>
                                <input type="password" class="form-control" name="password" id="password">
                                <div class="form-hint" id="passwordHint">Mínimo 6 caracteres</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Acessos do Sistema</label>
                        <div class="row">
                            <?php foreach ($acessos as $acesso): ?>
                            <div class="col-md-6 mb-2">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="accesses[]" value="<?= htmlspecialchars($acesso) ?>">
                                    <span class="form-check-label"><?= htmlspecialchars($acesso) ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
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
                <div>Deseja realmente excluir o usuário <strong id="nomeExcluir"></strong>?</div>
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
    $("#tabelaUsuarios").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },
        "pageLength": 25,
        "order": [[0, "asc"]]
    });
});

function editarUsuario(usuario) {
    $("#modalTitle").text("Editar Usuário");
    $("#action").val("edit");
    $("#usuarioId").val(usuario.id);
    $("#name").val(usuario.name);
    $("#username").val(usuario.username).prop("readonly", true);
    $("#email").val(usuario.email);
    $("#password").val("");
    $("#passwordLabel").text("Nova Senha (deixe em branco para manter)");
    $("#passwordHint").text("Deixe em branco para manter a senha atual");
    
    // Limpa checkboxes
    $("input[name=\"accesses[]\"]").prop("checked", false);
    
    // Marca acessos do usuário
    if (usuario.accesses) {
        var userAccesses = usuario.accesses.split(",");
        userAccesses.forEach(function(access) {
            $("input[name=\"accesses[]\"][value=\"" + access + "\"]").prop("checked", true);
        });
    }
    
    $("#modalUsuario").modal("show");
}

function excluirUsuario(id, nome) {
    $("#idExcluir").val(id);
    $("#nomeExcluir").text(nome);
    $("#modalExcluir").modal("show");
}

// Reset form when modal is closed
$("#modalUsuario").on("hidden.bs.modal", function() {
    $("#formUsuario")[0].reset();
    $("#modalTitle").text("Novo Usuário");
    $("#action").val("add");
    $("#usuarioId").val("");
    $("#username").prop("readonly", false);
    $("#passwordLabel").text("Senha");
    $("#passwordHint").text("Mínimo 6 caracteres");
    $("#password").prop("required", true);
});

// Quando abre modal para novo usuário, senha é obrigatória
$("#modalUsuario").on("show.bs.modal", function() {
    if ($("#action").val() === "add") {
        $("#password").prop("required", true);
    } else {
        $("#password").prop("required", false);
    }
});
</script>
';

// Inclui o layout
require_once 'layout.php';
?>