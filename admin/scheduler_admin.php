<?php
/**
 * admin/scheduler_admin.php
 * Interface para gerenciar o agendador de tarefas interno
 */

$requiredAccess = 'Super Administrador';
require_once 'check_access.php';

// Processamento de Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle') {
        $id = $_POST['id'];
        $status = $_POST['current_status'] === 'ativo' ? 'inativo' : 'ativo';
        $stmt = $pdo->prepare("UPDATE scheduler_tasks SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM scheduler_tasks WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($_POST['action'] === 'save') {
        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'];
        $script_path = $_POST['script_path'];
        $intervalo = (int)$_POST['intervalo_minutos'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE scheduler_tasks SET nome = :nome, script_path = :script, intervalo_minutos = :intervalo WHERE id = :id");
            $stmt->execute([':nome' => $nome, ':script' => $script_path, ':intervalo' => $intervalo, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO scheduler_tasks (nome, script_path, intervalo_minutos) VALUES (:nome, :script, :intervalo)");
            $stmt->execute([':nome' => $nome, ':script' => $script_path, ':intervalo' => $intervalo]);
        }
    }
}

// Busca tarefas
$stmt = $pdo->query("SELECT * FROM scheduler_tasks ORDER BY nome ASC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Agendador de Tarefas";
ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Agendador de Tarefas (Internal Cron)</h2>
                <div class="text-muted mt-1">Gerencie os scripts que rodam automaticamente em intervalos definidos.</div>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTarefa" onclick="limparForm()">
                    <i class="ti ti-plus me-1"></i> Nova Tarefa
                </button>
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
                            <th class="w-1">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>
                                <div class="font-weight-medium"><?= htmlspecialchars($task['nome']) ?></div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($task['script_path']) ?></td>
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
                                <div class="btn-group">
                                    <button class="btn btn-icon btn-sm btn-ghost-primary" 
                                            onclick='editarTarefa(<?= json_encode($task) ?>)' 
                                            title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $task['status'] ?>">
                                        <button type="submit" name="action" value="toggle" class="btn btn-icon btn-sm btn-ghost-<?= $task['status'] === 'ativo' ? 'warning' : 'success' ?>" title="<?= $task['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                            <i class="ti ti-<?= $task['status'] === 'ativo' ? 'player-pause' : 'player-play' ?>"></i>
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta tarefa permanentemente?')">
                                        <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-icon btn-sm btn-ghost-danger" title="Excluir">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
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

                        <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhuma tarefa agendada.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted">
                <i class="ti ti-info-circle me-1"></i> As tarefas são executadas sempre que um administrador acessa o painel do portal.
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Tarefa -->
<div class="modal modal-blur fade" id="modalTarefa" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nova Tarefa Agendada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="task_id">
                <div class="mb-3">
                    <label class="form-label">Nome da Tarefa</label>
                    <input type="text" class="form-control" name="nome" id="task_nome" placeholder="Ex: Backup de Banco de Dados" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Caminho do Script (relativo à raiz)</label>
                    <input type="text" class="form-control" name="script_path" id="task_path" placeholder="Ex: api/meu_script.php" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Intervalo (minutos)</label>
                    <input type="number" class="form-control" name="intervalo_minutos" id="task_intervalo" value="5" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" name="action" value="save" class="btn btn-primary">Salvar Tarefa</button>
            </div>
        </form>
    </div>
</div>

<script>
function limparForm() {
    document.getElementById('task_id').value = '';
    document.getElementById('task_nome').value = '';
    document.getElementById('task_path').value = '';
    document.getElementById('task_intervalo').value = '5';
    document.getElementById('modalTitle').innerText = 'Nova Tarefa Agendada';
}

function editarTarefa(task) {
    document.getElementById('task_id').value = task.id;
    document.getElementById('task_nome').value = task.nome;
    document.getElementById('task_path').value = task.script_path;
    document.getElementById('task_intervalo').value = task.intervalo_minutos;
    document.getElementById('modalTitle').innerText = 'Editar Tarefa: ' + task.nome;
    
    // Abre o modal manualmente
    var myModal = new bootstrap.Modal(document.getElementById('modalTarefa'));
    myModal.show();
}
</script>

<?php
$content = ob_get_clean();
require_once 'admin_layout.php';
?>
