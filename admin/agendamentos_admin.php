<?php
/**
 * admin/agendamentos_admin.php
 * Gestão de Agendamentos de Scripts (Web Cron).
 */

$requiredAccess = 'Super Administrador';
require_once 'check_access.php';

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added': $message = 'Agendamento adicionado com sucesso!'; $messageType = 'success'; break;
        case 'updated': $message = 'Agendamento atualizado com sucesso!'; $messageType = 'success'; break;
        case 'deleted': $message = 'Agendamento removido com sucesso!'; $messageType = 'success'; break;
    }
}
if (isset($_GET['error'])) {
    $message = 'Erro: ' . htmlspecialchars($_GET['error']);
    $messageType = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO sys_agendamentos (nome, url_script, intervalo_minutos, status) VALUES (:nome, :url_script, :intervalo_minutos, :status)");
            $stmt->execute([
                ':nome'              => $_POST['nome'] ?? '',
                ':url_script'        => trim($_POST['url_script']),
                ':intervalo_minutos' => (int)$_POST['intervalo_minutos'],
                ':status'            => $_POST['status'] ?? 'ativo'
            ]);
            header('Location: agendamentos_admin.php?success=added');
            exit;
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE sys_agendamentos SET nome = :nome, url_script = :url_script, intervalo_minutos = :intervalo_minutos, status = :status WHERE id = :id");
            $stmt->execute([
                ':nome'              => $_POST['nome'] ?? '',
                ':url_script'        => trim($_POST['url_script']),
                ':intervalo_minutos' => (int)$_POST['intervalo_minutos'],
                ':status'            => $_POST['status'] ?? 'ativo',
                ':id'                => $id
            ]);
            header('Location: agendamentos_admin.php?success=updated');
            exit;
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM sys_agendamentos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header('Location: agendamentos_admin.php?success=deleted');
            exit;
        } elseif ($action === 'toggle_heartbeat') {
            $value = $_POST['value'] ?? '0';
            $stmt = $pdo->prepare("INSERT INTO sys_config (chave, valor) VALUES ('web_cron_heartbeat', :val) ON DUPLICATE KEY UPDATE valor = :val");
            $stmt->execute([':val' => $value]);
            header('Location: agendamentos_admin.php?success=updated');
            exit;
        } elseif ($action === 'run_now') {
            $id = (int)$_POST['id'];
            $token = $_ENV['CRON_TOKEN'] ?? '';
            // Chama o runner internamente via cURL ou apenas redireciona para teste
            header("Location: ../api/scheduler_runner.php?token=$token");
            exit;
        }
    } catch (Exception $e) {
        header('Location: agendamentos_admin.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

$agendamentos = $pdo->query("SELECT * FROM sys_agendamentos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Agendamento de Scripts (Web Cron)';
ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title"><i class="ti ti-clock-play me-2"></i> Agendamento de Scripts</h2>
                <div class="text-muted mt-1">Gerencie scripts que precisam ser executados periodicamente sem depender de múltiplos Crons no servidor.</div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex align-items-center">
                <form method="POST" class="me-3">
                    <input type="hidden" name="action" value="toggle_heartbeat">
                    <?php $heartbeatActive = getSysConfig('web_cron_heartbeat', '1') === '1'; ?>
                    <label class="form-check form-switch m-0 pt-1" title="Se ativado, o portal tenta disparar os scripts durante a navegação.">
                        <input class="form-check-input" type="checkbox" name="value" value="1" onchange="this.form.submit()" <?= $heartbeatActive ? 'checked' : '' ?>>
                        <span class="form-check-label text-muted small">Execução Interna (Heartbeat)</span>
                    </label>
                </form>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="ti ti-plus me-1"></i> Novo Agendamento
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-3">
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i> <strong>Instrução para Cron no Servidor:</strong> 
            Para execução automática via servidor, utilize a URL: 
            <code><?= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/portal/api/scheduler_runner.php?token=" . ($_ENV['CRON_TOKEN'] ?? 'CONFIGURAR_TOKEN_NO_ENV') ?></code>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible" role="alert">
                <div><?= $message ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-status-top bg-blue"></div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-blue-lt me-3">
                        <i class="ti ti-help-hexagon"></i>
                    </div>
                    <div>
                        <h3 class="card-title mb-1">Como utilizar?</h3>
                        <p class="text-muted mb-0">
                            Configure apenas <strong>um único Cron</strong> no seu servidor chamando a URL do Runner central: <br>
                            <code>curl -s "<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/portal/api/scheduler_runner.php" ?>"</code> <br>
                            O Runner verificará as tarefas abaixo e processará as que estiverem pendentes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter table-mobile-md card-table">
                    <thead>
                        <tr>
                            <th>Tarefa</th>
                            <th>Script (URL)</th>
                            <th>Intervalo</th>
                            <th>Última Exec.</th>
                            <th>Próxima Exec.</th>
                            <th>Status</th>
                            <th class="w-1">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agendamentos)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum agendamento cadastrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($agendamentos as $a): ?>
                        <tr>
                            <td data-label="Tarefa">
                                <div class="font-weight-bold text-dark"><?= e($a['nome']) ?></div>
                            </td>
                            <td data-label="URL">
                                <span class="text-muted small" title="<?= e($a['url_script']) ?>">
                                    <?= strlen($a['url_script']) > 40 ? substr(e($a['url_script']), 0, 40) . '...' : e($a['url_script']) ?>
                                </span>
                            </td>
                            <td data-label="Intervalo">
                                <span class="badge badge-outline text-blue"><?= $a['intervalo_minutos'] ?> min</span>
                            </td>
                            <td data-label="Última">
                                <?= $a['ultima_execucao'] ? date('d/m/Y H:i', strtotime($a['ultima_execucao'])) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td data-label="Próxima">
                                <?php 
                                    if ($a['status'] === 'inativo') {
                                        echo '<span class="text-muted">Desativado</span>';
                                    } else {
                                        echo $a['proxima_execucao'] ? date('d/m/Y H:i', strtotime($a['proxima_execucao'])) : '<span class="badge bg-warning text-white">Pendente</span>';
                                    }
                                ?>
                            </td>
                            <td data-label="Status">
                                <span class="badge bg-<?= ($a['status'] === 'ativo' ? 'success' : 'secondary') ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="run_now">
                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-ghost-info btn-icon" title="Executar Agora"><i class="ti ti-player-play"></i></button>
                                    </form>
                                    <button class="btn btn-ghost-primary btn-icon" onclick='editAgendamento(<?= json_encode($a) ?>)' title="Editar"><i class="ti ti-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-ghost-danger btn-icon" title="Excluir"><i class="ti ti-trash"></i></button>
                                    </form>
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

<!-- Modal ADD/EDIT -->
<div class="modal fade" id="modalAgendamento" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="fieldId" value="">

                <div class="mb-3">
                    <label class="form-label">Nome descritivo</label>
                    <input type="text" name="nome" id="fieldNome" class="form-control" required placeholder="Ex: Verificação de Servidores">
                </div>

                <div class="mb-3">
                    <label class="form-label">URL do Script</label>
                    <input type="url" name="url_script" id="fieldUrl" class="form-control" required placeholder="http://localhost/portal/api/script.php">
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Intervalo (Minutos)</label>
                        <input type="number" name="intervalo_minutos" id="fieldIntervalo" class="form-control" required value="5" min="1">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="fieldStatus" class="form-select">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Agendamento</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Novo Agendamento';
    document.getElementById('formAction').value = 'create';
    document.getElementById('fieldId').value = '';
    document.getElementById('fieldNome').value = '';
    document.getElementById('fieldUrl').value = '';
    document.getElementById('fieldIntervalo').value = '5';
    document.getElementById('fieldStatus').value = 'ativo';
    new bootstrap.Modal(document.getElementById('modalAgendamento')).show();
}

function editAgendamento(data) {
    document.getElementById('modalTitle').textContent = 'Editar Agendamento';
    document.getElementById('formAction').value = 'update';
    document.getElementById('fieldId').value = data.id;
    document.getElementById('fieldNome').value = data.nome;
    document.getElementById('fieldUrl').value = data.url_script;
    document.getElementById('fieldIntervalo').value = data.intervalo_minutos;
    document.getElementById('fieldStatus').value = data.status;
    new bootstrap.Modal(document.getElementById('modalAgendamento')).show();
}
</script>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
