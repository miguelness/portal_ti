<?php
/**
 * admin/ips_admin.php
 * Gestão de IPs Internos para controle de visibilidade de links
 */

$requiredAccess = 'Super Administrador';
require_once 'check_access.php';

$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added': $message = 'IP/faixa adicionado com sucesso!'; $messageType = 'success'; break;
        case 'updated': $message = 'IP/faixa atualizado com sucesso!'; $messageType = 'success'; break;
        case 'deleted': $message = 'IP/faixa excluído com sucesso!'; $messageType = 'success'; break;
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
            $stmt = $pdo->prepare("INSERT INTO ips_internos (descricao, ip_inicio, ip_fim, status) VALUES (:descricao, :ip_inicio, :ip_fim, :status)");
            $stmt->execute([
                ':descricao' => $_POST['descricao'] ?? '',
                ':ip_inicio' => trim($_POST['ip_inicio']),
                ':ip_fim'    => !empty(trim($_POST['ip_fim'])) ? trim($_POST['ip_fim']) : null,
                ':status'    => $_POST['status'] ?? 'ativo'
            ]);
            header('Location: ips_admin.php?success=added');
            exit;
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE ips_internos SET descricao = :descricao, ip_inicio = :ip_inicio, ip_fim = :ip_fim, status = :status WHERE id = :id");
            $stmt->execute([
                ':descricao' => $_POST['descricao'] ?? '',
                ':ip_inicio' => trim($_POST['ip_inicio']),
                ':ip_fim'    => !empty(trim($_POST['ip_fim'])) ? trim($_POST['ip_fim']) : null,
                ':status'    => $_POST['status'] ?? 'ativo',
                ':id'        => $id
            ]);
            header('Location: ips_admin.php?success=updated');
            exit;
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM ips_internos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header('Location: ips_admin.php?success=deleted');
            exit;
        }
    } catch (Exception $e) {
        header('Location: ips_admin.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Fetch IPs
$ips = $pdo->query("SELECT * FROM ips_internos ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Detecta IP atual do visitante (para exibição informativa)
$currentIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido';
if (strpos($currentIp, ',') !== false) {
    $currentIp = trim(explode(',', $currentIp)[0]);
}

$pageTitle = 'Gestão de IPs Internos';
ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title"><i class="ti ti-network me-2"></i> Gestão de IPs Internos</h2>
                <div class="text-muted mt-1">
                    Cadastre IPs e faixas de IP da rede interna. Links marcados como "Somente Rede Interna" só aparecerão para visitantes desses IPs.
                    <br><strong>Seu IP atual:</strong> <code><?= e($currentIp) ?></code>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="ti ti-plus me-1"></i> Novo IP / Faixa
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-<?= $messageType === 'success' ? 'check' : 'alert-circle' ?> me-2"></i></div>
                    <div><?= $message ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-list me-1"></i> IPs e Faixas Cadastradas</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>IP Início</th>
                            <th>IP Fim (Faixa)</th>
                            <th>Status</th>
                            <th>Cadastrado em</th>
                            <th class="w-1">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ips)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum IP cadastrado ainda.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ips as $ip): ?>
                        <tr>
                            <td><?= e($ip['descricao']) ?></td>
                            <td><code><?= e($ip['ip_inicio']) ?></code></td>
                            <td>
                                <?php if ($ip['ip_fim']): ?>
                                    <code><?= e($ip['ip_fim']) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">IP Único</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ip['status'] === 'ativo'): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($ip['criado_em'])) ?></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <button class="btn btn-sm btn-ghost-primary btn-icon" onclick='editIp(<?= json_encode($ip) ?>)' title="Editar"><i class="ti ti-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir este IP/faixa?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ip['id'] ?>">
                                        <button class="btn btn-sm btn-ghost-danger btn-icon" title="Excluir"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dica -->
        <div class="alert alert-info mt-3">
            <div class="d-flex">
                <div><i class="ti ti-info-circle me-2 fs-2"></i></div>
                <div>
                    <h4 class="alert-title">Como funciona?</h4>
                    <ul class="mb-0">
                        <li><strong>IP Único:</strong> Preencha apenas o campo "IP Início" (ex: <code>192.168.1.50</code>).</li>
                        <li><strong>Faixa de IPs:</strong> Preencha "IP Início" e "IP Fim" (ex: <code>192.168.1.1</code> até <code>192.168.1.254</code>).</li>
                        <li>Os links marcados como "Somente Rede Interna" no <a href="links_admin.php">Gestor de Links</a> serão visíveis apenas para visitantes com IPs cadastrados aqui.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ADD/EDIT IP -->
<div class="modal fade" id="ipModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="ipModalTitle">Novo IP / Faixa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="ipAction" value="create">
                <input type="hidden" name="id" id="ipId" value="">

                <div class="mb-3">
                    <label class="form-label required"><i class="ti ti-tag me-1"></i> Descrição</label>
                    <input type="text" name="descricao" id="ipDescricao" class="form-control" required placeholder="Ex: Rede do Escritório, VPN Corporativa...">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required"><i class="ti ti-network me-1"></i> IP Início</label>
                        <input type="text" name="ip_inicio" id="ipInicio" class="form-control" required placeholder="Ex: 192.168.1.1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="ti ti-arrows-horizontal me-1"></i> IP Fim (opcional)</label>
                        <input type="text" name="ip_fim" id="ipFim" class="form-control" placeholder="Ex: 192.168.1.254">
                        <small class="text-muted">Deixe vazio para cadastrar um IP único.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="ipStatus" class="form-select">
                        <option value="ativo">✅ Ativo</option>
                        <option value="inativo">❌ Inativo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('ipModalTitle').textContent = 'Novo IP / Faixa';
    document.getElementById('ipAction').value = 'create';
    document.getElementById('ipId').value = '';
    document.getElementById('ipDescricao').value = '';
    document.getElementById('ipInicio').value = '';
    document.getElementById('ipFim').value = '';
    document.getElementById('ipStatus').value = 'ativo';
    new bootstrap.Modal(document.getElementById('ipModal')).show();
}

function editIp(data) {
    document.getElementById('ipModalTitle').textContent = 'Editar IP / Faixa';
    document.getElementById('ipAction').value = 'update';
    document.getElementById('ipId').value = data.id;
    document.getElementById('ipDescricao').value = data.descricao;
    document.getElementById('ipInicio').value = data.ip_inicio;
    document.getElementById('ipFim').value = data.ip_fim || '';
    document.getElementById('ipStatus').value = data.status;
    new bootstrap.Modal(document.getElementById('ipModal')).show();
}
</script>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
