<?php
/**
 * admin/servidores_admin.php
 * Gestão de Servidores e Links de Monitoramento.
 */

$requiredAccess = 'Super Administrador';
require_once 'check_access.php';

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added': $message = 'Servidor adicionado com sucesso!'; $messageType = 'success'; break;
        case 'updated': $message = 'Servidor atualizado com sucesso!'; $messageType = 'success'; break;
        case 'deleted': $message = 'Servidor removido com sucesso!'; $messageType = 'success'; break;
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
            $stmt = $pdo->prepare("INSERT INTO monitoramento_servidores (nome, ip_ou_url, tipo, categoria_propriedade, verificar_estabilidade, exibir_dashboard, is_public, exibir_topo, tempo_bom_ms, tempo_lento_ms) VALUES (:nome, :ip_ou_url, :tipo, :categoria, :verificar, :exibir, :is_public, :exibir_topo, :tempo_bom, :tempo_lento)");
            $stmt->execute([
                ':nome'      => $_POST['nome'] ?? '',
                ':ip_ou_url' => trim($_POST['ip_ou_url']),
                ':tipo'      => $_POST['tipo'] ?? 'externo',
                ':categoria' => $_POST['categoria_propriedade'] ?? 'proprio',
                ':verificar' => (int)($_POST['verificar_estabilidade'] ?? 1),
                ':exibir'    => (int)($_POST['exibir_dashboard'] ?? 0),
                ':is_public' => (int)($_POST['is_public'] ?? 0),
                ':exibir_topo' => (int)($_POST['exibir_topo'] ?? 0),
                ':tempo_bom' => (int)($_POST['tempo_bom_ms'] ?: 1500),
                ':tempo_lento' => (int)($_POST['tempo_lento_ms'] ?: 3500)
            ]);
            header('Location: servidores_admin.php?success=added');
            exit;
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE monitoramento_servidores SET nome = :nome, ip_ou_url = :ip_ou_url, tipo = :tipo, categoria_propriedade = :categoria, verificar_estabilidade = :verificar, exibir_dashboard = :exibir, is_public = :is_public, exibir_topo = :exibir_topo, tempo_bom_ms = :tempo_bom, tempo_lento_ms = :tempo_lento WHERE id = :id");
            $stmt->execute([
                ':nome'      => $_POST['nome'] ?? '',
                ':ip_ou_url' => trim($_POST['ip_ou_url']),
                ':tipo'      => $_POST['tipo'] ?? 'externo',
                ':categoria' => $_POST['categoria_propriedade'] ?? 'proprio',
                ':verificar' => (int)($_POST['verificar_estabilidade'] ?? 1),
                ':exibir'    => (int)($_POST['exibir_dashboard'] ?? 0),
                ':is_public' => (int)($_POST['is_public'] ?? 0),
                ':exibir_topo' => (int)($_POST['exibir_topo'] ?? 0),
                ':tempo_bom' => (int)($_POST['tempo_bom_ms'] ?: 1500),
                ':tempo_lento' => (int)($_POST['tempo_lento_ms'] ?: 3500),
                ':id'        => $id
            ]);
            header('Location: servidores_admin.php?success=updated');
            exit;
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM monitoramento_servidores WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header('Location: servidores_admin.php?success=deleted');
            exit;
        }
    } catch (Exception $e) {
        header('Location: servidores_admin.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

$servidores = $pdo->query("SELECT * FROM monitoramento_servidores ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Monitoramento de Servidores';
ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title"><i class="ti ti-activity me-2"></i> Monitoramento de Servidores</h2>
                <div class="text-muted mt-1">Gerencie os servidores e links internos/externos para monitoramento de status online e oscilações.</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="ti ti-plus me-1"></i> Adicionar Servidor
                </button>
            </div>
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

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter table-mobile-md card-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>IP / URL</th>
                            <th>Tipo</th>
                            <th>Status (Último)</th>
                            <th>Tempo Resposta</th>
                            <th>Última Verif.</th>
                            <th class="w-1">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($servidores)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum servidor cadastrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($servidores as $s): ?>
                        <tr>
                            <td data-label="Nome">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-xs me-2"><i class="ti ti-server"></i></span>
                                    <span class="font-weight-bold text-dark"><?= e($s['nome']) ?></span>
                                </div>
                            </td>
                            <td data-label="IP/URL"><code><?= e($s['ip_ou_url']) ?></code></td>
                            <td data-label="Tipo">
                                <span class="badge badge-outline text-<?= ($s['tipo'] === 'interno' ? 'blue' : 'orange') ?>">
                                    <?= ucfirst($s['tipo']) ?>
                                </span>
                            </td>
                            <td data-label="Status">
                                <?php if ($s['status'] === 'online'): ?>
                                    <span class="badge bg-success">Online</span>
                                <?php elseif ($s['status'] === 'lento'): ?>
                                    <span class="badge bg-warning text-dark">Lento / Oscilação</span>
                                <?php elseif ($s['status'] === 'offline'): ?>
                                    <span class="badge bg-danger">Offline</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tempo"><?= $s['tempo_resposta_ms'] ?> ms</td>
                            <td data-label="Check">
                                <?= $s['ultima_verificacao'] ? date('d/m/Y H:i:s', strtotime($s['ultima_verificacao'])) : '-' ?>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <button class="btn btn-ghost-primary btn-icon" onclick='editServidor(<?= json_encode($s) ?>)' title="Editar"><i class="ti ti-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
<div class="modal fade" id="servidorModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Servidor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="servAction" value="create">
                <input type="hidden" name="id" id="servId" value="">

                <div class="mb-3">
                    <label class="form-label">Nome do Servidor / Link</label>
                    <input type="text" name="nome" id="servNome" class="form-control" required placeholder="Ex: Servidor de Dados principal">
                </div>

                <div class="mb-3">
                    <label class="form-label">IP ou URL</label>
                    <input type="text" name="ip_ou_url" id="servIpUrl" class="form-control" required placeholder="Ex: 192.168.0.1 ou https://google.com">
                    <small class="text-muted">Se for IP, o sistema tentará uma conexão socket TCP. Se for URL, usará cURL HTTP.</small>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Técnico (Acesso)</label>
                        <select name="tipo" id="servTipo" class="form-select">
                            <option value="externo">Público (URL/Domínio)</option>
                            <option value="interno">Interno (IP Rede)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Propriedade</label>
                        <select name="categoria_propriedade" id="servCategoria" class="form-select">
                            <option value="proprio">Proprio / Contratado</option>
                            <option value="terceiro">Terceiros / Parceiros</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Monitoramento Ativo?</label>
                        <select name="verificar_estabilidade" id="servVerificar" class="form-select">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Exibir no Dashboard Principal?</label>
                    <select name="exibir_dashboard" id="servExibir" class="form-select">
                        <option value="0">Não (Oculto)</option>
                        <option value="1">Sim (Versão Compacta)</option>
                    </select>
                    <small class="text-muted">Aparecerá logo abaixo do título "Ferramentas Corporativas" na home.</small>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Visibilidade</label>
                        <select name="is_public" id="servPublic" class="form-select">
                            <option value="0">Uso Interno TI (Oculto no Portal)</option>
                            <option value="1">Compartilhado no Portal (Visível a todos)</option>
                        </select>
                        <small class="text-muted">Define se o status será compartilhado publicamente.</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Exibir no Topo da Index?</label>
                        <select name="exibir_topo" id="servTopo" class="form-select">
                            <option value="0">Não</option>
                            <option value="1">Sim (Destaque Superior)</option>
                        </select>
                        <small class="text-muted">Cards de monitoramento no cabeçalho.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Tempo Bom (ms)</label>
                        <input type="number" name="tempo_bom_ms" id="servTempoBom" class="form-control" value="1500">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Tempo Lento (ms)</label>
                        <input type="number" name="tempo_lento_ms" id="servTempoLento" class="form-control" value="3500">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary ms-auto">Salvar</button>
            </div>
        </form>
    </div>
</div>


<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Novo Servidor';
    document.getElementById('servAction').value = 'create';
    document.getElementById('servId').value = '';
    document.getElementById('servNome').value = '';
    document.getElementById('servIpUrl').value = '';
    document.getElementById('servTipo').value = 'externo';
    document.getElementById('servCategoria').value = 'proprio';
    document.getElementById('servVerificar').value = '1';
    document.getElementById('servExibir').value = '0';
    document.getElementById('servPublic').value = '0';
    document.getElementById('servTopo').value = '0';
    document.getElementById('servTempoBom').value = '1500';
    document.getElementById('servTempoLento').value = '3500';
    new bootstrap.Modal(document.getElementById('servidorModal')).show();
}

function editServidor(data) {
    document.getElementById('modalTitle').textContent = 'Editar Servidor';
    document.getElementById('servAction').value = 'update';
    document.getElementById('servId').value = data.id;
    document.getElementById('servNome').value = data.nome;
    document.getElementById('servIpUrl').value = data.ip_ou_url;
    document.getElementById('servTipo').value = data.tipo;
    document.getElementById('servCategoria').value = data.categoria_propriedade || 'proprio';
    document.getElementById('servVerificar').value = data.verificar_estabilidade;
    document.getElementById('servExibir').value = data.exibir_dashboard;
    document.getElementById('servPublic').value = data.is_public;
    document.getElementById('servTopo').value = data.exibir_topo;
    document.getElementById('servTempoBom').value = data.tempo_bom_ms;
    document.getElementById('servTempoLento').value = data.tempo_lento_ms;
    new bootstrap.Modal(document.getElementById('servidorModal')).show();
}
</script>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
