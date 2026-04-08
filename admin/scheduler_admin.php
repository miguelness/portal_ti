<?php
/**
 * admin/scheduler_admin.php
 * Interface para gerenciar o agendador de tarefas interno
 */

$pageTitle = "Agendador de Tarefas";

// Processamento de Ações (antes de capturar o buffer)
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Verificação básica de acesso (Super Admin)
if (!isset($_SESSION['acessos']) || !in_array('Super Administrador', $_SESSION['acessos'])) {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle') {
        $id = $_POST['id'];
        $status = $_POST['current_status'] === 'ativo' ? 'inativo' : 'ativo';
        $stmt = $pdo->prepare("UPDATE scheduler_tasks SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }
}

// Busca tarefas
$stmt = $pdo->query("SELECT * FROM scheduler_tasks ORDER BY nome ASC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Agendador de Tarefas (Internal Cron)</h2>
                <div class="text-muted mt-1">Gerencie os scripts que rodam automaticamente em intervalos definidos.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Tarefa</th>
                            <th>Script</th>
                            <th>Intervalo</th>
                            <th>Última Execução</th>
                            <th>Próxima</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>
                                <div class="font-weight-medium"><?= htmlspecialchars($task['nome']) ?></div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($task['script_path']) ?></td>
                            <td><?= $task['intervalo_minutos'] ?> min</td>
                            <td><?= $task['ultima_execucao'] ? date('d/m/H:i', strtotime($task['ultima_execucao'])) : 'Nunca' ?></td>
                            <td>
                                <span class="badge bg-azure-lt">
                                    <?= $task['proxima_execucao'] ? date('d/m/H:i', strtotime($task['proxima_execucao'])) : 'Pendente' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $task['status'] === 'ativo' ? 'success' : 'secondary' ?>-lt">
                                    <?= ucfirst($task['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $task['status'] ?>">
                                    <button type="submit" name="action" value="toggle" class="btn btn-sm btn-<?= $task['status'] === 'ativo' ? 'warning' : 'success' ?>">
                                        <?= $task['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php if (!empty($task['last_log'])): ?>
                        <tr class="bg-light">
                            <td colspan="7">
                                <small class="text-muted"><strong>Log:</strong> <?= htmlspecialchars($task['last_log']) ?></small>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted">
                <i class="ti ti-info-circle me-1"></i> As tarefas são executadas sempre que um administrador acessa o painel do portal.
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'admin_layout.php';
?>
