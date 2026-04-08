<?php
/**
 * admin/treinamentos_admin.php
 * Gestão de Vídeos de Treinamento e Políticas com Anexos
 */

$requiredAccess = 'Super Administrador'; // Simplificando para quem tem acesso total
require_once 'check_access.php';

$uploadDir = '../uploads_treinamentos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Processa mensagens de sucesso/erro
$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added': $message = 'Vídeo adicionado com sucesso!'; $messageType = 'success'; break;
        case 'updated': $message = 'Vídeo atualizado com sucesso!'; $messageType = 'success'; break;
        case 'deleted': $message = 'Vídeo excluído com sucesso!'; $messageType = 'success'; break;
        case 'anexo_added': $message = 'Anexo(s) enviado(s) com sucesso!'; $messageType = 'success'; break;
        case 'anexo_deleted': $message = 'Anexo excluído com sucesso!'; $messageType = 'success'; break;
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
            $sql = "INSERT INTO videos_treinamento (titulo, descricao, url_video, ordem, status, menu_link_id)
                    VALUES (:titulo, :descricao, :url_video, :ordem, :status, :menu_link_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $_POST['titulo'],
                ':descricao' => $_POST['descricao'] ?? '',
                ':url_video' => $_POST['url_video'],
                ':ordem' => $_POST['ordem'] ?? 0,
                ':status' => $_POST['status'] ?? 'ativo',
                ':menu_link_id' => !empty($_POST['menu_link_id']) ? $_POST['menu_link_id'] : null
            ]);
            header('Location: treinamentos_admin.php?success=added');
            exit;
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $sql = "UPDATE videos_treinamento SET 
                    titulo = :titulo, descricao = :descricao, url_video = :url_video, 
                    ordem = :ordem, status = :status, menu_link_id = :menu_link_id WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $_POST['titulo'],
                ':descricao' => $_POST['descricao'] ?? '',
                ':url_video' => $_POST['url_video'],
                ':ordem' => $_POST['ordem'] ?? 0,
                ':status' => $_POST['status'] ?? 'ativo',
                ':menu_link_id' => !empty($_POST['menu_link_id']) ? $_POST['menu_link_id'] : null,
                ':id' => $id
            ]);
            header('Location: treinamentos_admin.php?success=updated');
            exit;
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // Apaga anexos fisicamente primeiro
            $stmtA = $pdo->prepare("SELECT caminho_arquivo FROM videos_anexos WHERE video_id = :id");
            $stmtA->execute([':id' => $id]);
            $anexos = $stmtA->fetchAll();
            foreach($anexos as $a) {
                if(file_exists($uploadDir . $a['caminho_arquivo'])) {
                    unlink($uploadDir . $a['caminho_arquivo']);
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM videos_treinamento WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header('Location: treinamentos_admin.php?success=deleted');
            exit;
        } elseif ($action === 'upload_anexos') {
            $video_id = (int)$_POST['video_id'];
            
            if (!empty($_FILES['anexos']['name'][0])) {
                $count = count($_FILES['anexos']['name']);
                for ($i = 0; $i < $count; $i++) {
                    $tmpName = $_FILES['anexos']['tmp_name'][$i];
                    $originalName = $_FILES['anexos']['name'][$i];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $newName = uniqid('anexo_') . '_' . time() . '.' . $ext;
                    
                    if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                        $stmt = $pdo->prepare("INSERT INTO videos_anexos (video_id, nome_documento, caminho_arquivo) VALUES (?, ?, ?)");
                        $stmt->execute([$video_id, $originalName, $newName]);
                    }
                }
            }
            header('Location: treinamentos_admin.php?success=anexo_added');
            exit;
        } elseif ($action === 'delete_anexo') {
            $anexo_id = (int)$_POST['anexo_id'];
            $stmt = $pdo->prepare("SELECT caminho_arquivo FROM videos_anexos WHERE id = :id");
            $stmt->execute([':id' => $anexo_id]);
            $anexo = $stmt->fetch();
            
            if ($anexo) {
                if(file_exists($uploadDir . $anexo['caminho_arquivo'])) {
                    unlink($uploadDir . $anexo['caminho_arquivo']);
                }
                $stmtDel = $pdo->prepare("DELETE FROM videos_anexos WHERE id = :id");
                $stmtDel->execute([':id' => $anexo_id]);
            }
            header('Location: treinamentos_admin.php?success=anexo_deleted');
            exit;
        }
    } catch (Exception $e) {
        header('Location: treinamentos_admin.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Fetch Vídeos e Anexos (com left join na categoria/cartão pai)
$sql = "SELECT vt.*, ml.titulo as nome_cartao 
        FROM videos_treinamento vt 
        LEFT JOIN menu_links ml ON vt.menu_link_id = ml.id 
        ORDER BY vt.ordem ASC, vt.id DESC";
$videos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sqlAnexos = "SELECT * FROM videos_anexos ORDER BY criado_em ASC";
$todosAnexosRaw = $pdo->query($sqlAnexos)->fetchAll(PDO::FETCH_ASSOC);

$anexosPorVideo = [];
foreach($todosAnexosRaw as $anx) {
    $anexosPorVideo[$anx['video_id']][] = $anx;
}

// Fetch Cartões disponíveis para grupo de treinamentos
$cartoesTreinamento = $pdo->query("SELECT id, titulo FROM menu_links WHERE is_treinamento = 1 ORDER BY titulo ASC")->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Gerenciar Treinamentos e Políticas';
ob_start();
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title"><i class="ti ti-video me-2"></i> Gerenciar Treinamentos e Políticas</h2>
                <div class="text-muted mt-1">Cadastre vídeos e gerencie seus arquivos anexos para a Central de Treinamento.</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="ti ti-plus me-1"></i> Novo Vídeo
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
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>
            </div>
        <?php endif; ?>

        <div class="row row-cards">
            <?php if(empty($videos)): ?>
                <div class="col-12 text-center py-5 text-muted">Nenhum vídeo cadastrado ainda. Clique em "Novo Vídeo" para começar.</div>
            <?php endif; ?>

            <?php foreach($videos as $vid): 
                $meusAnexos = $anexosPorVideo[$vid['id']] ?? [];
            ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <?php if($vid['status'] == 'ativo'): ?>
                                <span class="badge bg-success me-2">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger me-2">Inativo</span>
                            <?php endif; ?>
                            <i class="ti ti-player-play me-1 text-primary"></i> <?= e($vid['titulo']) ?>
                            <br>
                            <small class="text-muted fs-5"><i class="ti ti-school me-1"></i> Cartão: <?= $vid['nome_cartao'] ? e($vid['nome_cartao']) : 'Geral (Nenhum Selecionado)' ?></small>
                        </h3>
                        <div class="btn-list">
                            <span class="text-muted fs-5 me-3">Ordem: <?= $vid['ordem'] ?></span>
                            <button class="btn btn-sm btn-outline-primary" onclick='editVideo(<?= json_encode($vid) ?>)'><i class="ti ti-edit"></i> Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir este vídeo e todos os seus anexos?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $vid['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <strong>Descrição:</strong><br>
                                <p class="text-muted"><?= nl2br(e($vid['descricao'])) ?></p>
                                <strong>URL do Vídeo:</strong><br>
                                <a href="<?= e($vid['url_video']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width:300px;"><?= e($vid['url_video']) ?></a>
                            </div>
                            <div class="col-md-7">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Documentos Anexos (<?= count($meusAnexos) ?>)</strong>
                                    <button class="btn btn-sm btn-ghost-primary" onclick="openAnexoModal(<?= $vid['id'] ?>, '<?= e($vid['titulo']) ?>')">
                                        <i class="ti ti-upload me-1"></i> Enviar Anexo
                                    </button>
                                </div>
                                <?php if(count($meusAnexos) > 0): ?>
                                    <ul class="list-group list-group-flush border rounded">
                                        <?php foreach($meusAnexos as $anx): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                            <div class="text-truncate me-2" style="max-width: 80%;">
                                                <i class="ti ti-file-text text-muted me-1"></i> 
                                                <a href="<?= $uploadDir . $anx['caminho_arquivo'] ?>" target="_blank"><?= e($anx['nome_documento']) ?></a>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Apagar este anexo?');">
                                                <input type="hidden" name="action" value="delete_anexo">
                                                <input type="hidden" name="anexo_id" value="<?= $anx['id'] ?>">
                                                <button class="btn btn-sm btn-ghost-danger btn-icon" title="Excluir"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-muted fs-5 bg-light p-2 rounded text-center">Nenhum anexo.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal ADD/EDIT Vídeo -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalTitle">Novo Vídeo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="videoAction" value="create">
                <input type="hidden" name="id" id="videoId" value="">
                
                <div class="mb-3">
                    <label class="form-label required">Título do Treinamento/Política</label>
                    <input type="text" name="titulo" id="videoTitulo" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required">Link do Vídeo (Embed, YouTube, Vimeo, Drive, HTML5...)</label>
                    <input type="url" name="url_video" id="videoUrl" class="form-control" required placeholder="Ex: https://www.youtube.com/embed/xxxxx">
                    <small class="text-muted">No caso do YouTube, prefira o link de "Embed" gerado ao clicar em Compartilhar -> Incorporar.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required"><i class="ti ti-school me-1 text-primary"></i> Categoria / Grupo de Vídeos</label>
                    <select name="menu_link_id" id="videoMenuLinkId" class="form-select" required>
                        <option value="">-- Selecione a qual Cartão de Menu este vídeo pertence --</option>
                        <?php foreach($cartoesTreinamento as $cartao): ?>
                            <option value="<?= $cartao['id'] ?>"><?= e($cartao['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Apenas Cartões de Menu marcados como "Treinamento" no Gestor de Links aparecem aqui.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Contexto/Descrição</label>
                    <textarea name="descricao" id="videoDescricao" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ordem de Exibição (0 = Primeiro)</label>
                        <input type="number" name="ordem" id="videoOrdem" class="form-control" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="videoStatus" class="form-select">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Vídeo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ADD Anexo -->
<div class="modal fade" id="anexoModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Anexos: <span id="anexoVideoTitle" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="upload_anexos">
                <input type="hidden" name="video_id" id="anexoVideoId" value="">
                
                <div class="mb-3">
                    <label class="form-label">Selecione os arquivos (PDFs, Docs, Imagens, etc)</label>
                    <input type="file" name="anexos[]" class="form-control" multiple required>
                    <small class="text-muted">Você pode selecionar múltiplos arquivos de uma vez segurando CTRL.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="ti ti-upload me-1"></i> Fazer Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('videoModalTitle').textContent = 'Novo Vídeo';
    document.getElementById('videoAction').value = 'create';
    document.getElementById('videoId').value = '';
    document.getElementById('videoTitulo').value = '';
    document.getElementById('videoUrl').value = '';
    document.getElementById('videoMenuLinkId').value = '';
    document.getElementById('videoDescricao').value = '';
    document.getElementById('videoOrdem').value = '0';
    document.getElementById('videoStatus').value = 'ativo';
    
    new bootstrap.Modal(document.getElementById('videoModal')).show();
}

function editVideo(data) {
    document.getElementById('videoModalTitle').textContent = 'Editar Vídeo';
    document.getElementById('videoAction').value = 'update';
    document.getElementById('videoId').value = data.id;
    document.getElementById('videoTitulo').value = data.titulo;
    document.getElementById('videoUrl').value = data.url_video;
    document.getElementById('videoMenuLinkId').value = data.menu_link_id || '';
    document.getElementById('videoDescricao').value = data.descricao;
    document.getElementById('videoOrdem').value = data.ordem;
    document.getElementById('videoStatus').value = data.status;
    
    new bootstrap.Modal(document.getElementById('videoModal')).show();
}

function openAnexoModal(vidId, vidTitle) {
    document.getElementById('anexoVideoId').value = vidId;
    document.getElementById('anexoVideoTitle').textContent = vidTitle;
    
    new bootstrap.Modal(document.getElementById('anexoModal')).show();
}
</script>

<?php 
$content = ob_get_clean();
include 'admin_layout.php';
