<?php
/**
 * admin/links_admin.php
 * Gestão de Menu com interface moderna Tabler
 */

$requiredAccess = 'Gestão de Menu';
require_once 'check_access.php';

// Processa mensagens de sucesso/erro
$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $message = 'Item adicionado com sucesso!';
            $messageType = 'success';
            break;
        case 'updated':
            $message = 'Item atualizado com sucesso!';
            $messageType = 'success';
            break;
        case 'deleted':
            $message = 'Item excluído com sucesso!';
            $messageType = 'success';
            break;
    }
}

// Processa adição de novo item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "INSERT INTO menu_links
                  (titulo, descricao, url, target_blank, cor, tamanho, icone, ordem, status,
                   parent_id, modal_class)
                VALUES
                  (:titulo, :descricao, :url, :target_blank, :cor, :tamanho, :icone, :ordem,
                   :status, :parent_id, :modal_class)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo'       => $_POST['titulo'] ?? '',
            ':descricao'    => $_POST['descricao'] ?? '',
            ':url'          => $_POST['url'] ?? '',
            ':target_blank' => isset($_POST['target_blank']) ? 1 : 0,
            ':cor'          => $_POST['cor'] ?? '#206bc4',
            ':tamanho'      => $_POST['tamanho'] ?? 'col-lg-3 col-xl-3',
            ':icone'        => $_POST['icone'] ?? '',
            ':ordem'        => $_POST['ordem'] ?? 0,
            ':status'       => $_POST['status'] ?? 'ativo',
            ':parent_id'    => $_POST['parent_id'] ?: null,
            ':modal_class'  => $_POST['modal_class'] ?? ''
        ]);
        header('Location: links_admin.php?success=added');
        exit;
    } catch (Exception $e) {
        $message = 'Erro ao adicionar item: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Busca dados para listagem organizados hierarquicamente
$sql = "SELECT ml.*, mp.titulo as parent_titulo
        FROM menu_links ml
        LEFT JOIN menu_links mp ON mp.id = ml.parent_id
        ORDER BY 
            CASE WHEN ml.parent_id IS NULL THEN ml.id ELSE ml.parent_id END,
            CASE WHEN ml.parent_id IS NULL THEN 0 ELSE 1 END,
            ml.ordem, 
            ml.titulo";
$allLinks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Organiza os links hierarquicamente
$links = [];
$parentLinks = [];
$childLinks = [];

// Separa pais e filhos
foreach ($allLinks as $link) {
    if (empty($link['parent_id'])) {
        $parentLinks[$link['id']] = $link;
        $childLinks[$link['id']] = [];
    } else {
        $childLinks[$link['parent_id']][] = $link;
    }
}

// Monta array final com hierarquia
foreach ($parentLinks as $parentId => $parent) {
    $links[] = $parent;
    // Adiciona filhos logo após o pai
    if (!empty($childLinks[$parentId])) {
        foreach ($childLinks[$parentId] as $child) {
            $links[] = $child;
        }
    }
}

// Possíveis pais para select
$possibleParents = $pdo->query(
    "SELECT id, titulo FROM menu_links
     WHERE parent_id IS NULL OR parent_id = 0
     ORDER BY titulo"
)->fetchAll(PDO::FETCH_ASSOC);

// Ícones Tabler disponíveis
$tablerIcons = [
    '' => 'Sem ícone',
    'ti ti-home' => 'Casa',
    'ti ti-user' => 'Usuário',
    'ti ti-users' => 'Usuários',
    'ti ti-settings' => 'Configurações',
    'ti ti-mail' => 'E-mail',
    'ti ti-phone' => 'Telefone',
    'ti ti-calendar' => 'Calendário',
    'ti ti-clock' => 'Relógio',
    'ti ti-file' => 'Arquivo',
    'ti ti-folder' => 'Pasta',
    'ti ti-chart-bar' => 'Gráfico',
    'ti ti-dashboard' => 'Dashboard',
    'ti ti-database' => 'Banco de Dados',
    'ti ti-server' => 'Servidor',
    'ti ti-cloud' => 'Nuvem',
    'ti ti-download' => 'Download',
    'ti ti-upload' => 'Upload',
    'ti ti-link' => 'Link',
    'ti ti-external-link' => 'Link Externo',
    'ti ti-world' => 'Mundo/Web',
    'ti ti-shield' => 'Segurança',
    'ti ti-lock' => 'Bloqueado',
    'ti ti-key' => 'Chave',
    'ti ti-eye' => 'Visualizar',
    'ti ti-edit' => 'Editar',
    'ti ti-trash' => 'Lixeira',
    'ti ti-plus' => 'Adicionar',
    'ti ti-search' => 'Buscar',
    'ti ti-filter' => 'Filtrar',
    'ti ti-refresh' => 'Atualizar',
    'ti ti-printer' => 'Imprimir',
    'ti ti-share' => 'Compartilhar',
    'ti ti-heart' => 'Favorito',
    'ti ti-star' => 'Estrela',
    'ti ti-bookmark' => 'Marcador',
    'ti ti-tag' => 'Tag',
    'ti ti-bell' => 'Notificação',
    'ti ti-message' => 'Mensagem',
    'ti ti-building' => 'Prédio',
    'ti ti-office' => 'Escritório',
    'ti ti-shopping-cart' => 'Carrinho',
    'ti ti-credit-card' => 'Cartão',
    'ti ti-calculator' => 'Calculadora',
    'ti ti-report' => 'Relatório',
    'ti ti-book' => 'Livro',
    'ti ti-clipboard' => 'Prancheta',
    'ti ti-list' => 'Lista',
    'ti ti-menu' => 'Menu',
    'ti ti-grid-dots' => 'Grade',
    'ti ti-layout' => 'Layout',
    'ti ti-palette' => 'Paleta',
    'ti ti-tool' => 'Ferramenta',
    'ti ti-device-desktop' => 'Desktop',
    'ti ti-device-laptop' => 'Laptop',
    'ti ti-device-mobile' => 'Mobile',
    'ti ti-wifi' => 'WiFi',
    'ti ti-battery' => 'Bateria',
    'ti ti-bulb' => 'Lâmpada'
];

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Gerenciar Links do Menu';
$extraCSS = '
<style>
    .color-swatch {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
        margin-right: 8px;
        vertical-align: middle;
    }
    .icon-preview {
        font-size: 1.2rem;
        margin-right: 8px;
        vertical-align: middle;
    }
    .table-actions {
        white-space: nowrap;
    }
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .status-badge {
        font-size: 0.75rem;
    }
    .parent-badge {
        font-size: 0.75rem;
        background-color: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }
    .hierarchy-indent {
        background-color: #f8f9fa;
        border-left: 3px solid #206bc4;
    }
    .hierarchy-indent td:first-child {
        padding-left: 2rem;
        position: relative;
    }
    .hierarchy-indent td:first-child::before {
        content: "└─";
        position: absolute;
        left: 0.5rem;
        color: #206bc4;
        font-weight: bold;
    }
    .parent-row {
        background-color: #fff;
        border-bottom: 2px solid #e9ecef;
    }
    .parent-row td {
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .stats-card .card-body {
        padding: 1.5rem;
    }
</style>';

$extraJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Inicializa DataTable
    if (typeof $ !== "undefined" && $.fn.DataTable) {
        $("#tblLinks").DataTable({
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [],
            ordering: false,
            columnDefs: [
                { orderable: false, targets: "_all" },
                { searchable: false, targets: [8] }
            ],
            responsive: true,
            dom: "<\"row\"<\"col-sm-12 col-md-6\"l><\"col-sm-12 col-md-6\"f>>" +
                 "<\"row\"<\"col-sm-12\"tr>>" +
                 "<\"row\"<\"col-sm-12 col-md-5\"i><\"col-sm-12 col-md-7\"p>>",
        });
    }
    
    // Preview de cor no modal
    function updateColorPreview() {
        const colorSelect = document.getElementById("cor");
        const preview = document.getElementById("color-preview");
        
        if (colorSelect && preview) {
            preview.style.backgroundColor = colorSelect.value;
        }
    }
    
    // Preview de ícone no modal
    function updateIconPreview() {
        const iconSelect = document.getElementById("icone");
        const preview = document.getElementById("icon-preview");
        
        if (iconSelect && preview) {
            preview.className = "icon-preview " + iconSelect.value;
        }
    }
    
    // Event listeners
    document.getElementById("cor")?.addEventListener("change", updateColorPreview);
    document.getElementById("icone")?.addEventListener("change", updateIconPreview);
    
    // Inicializa previews
    updateColorPreview();
    updateIconPreview();
    
    // Confirmação de exclusão
    document.querySelectorAll(".btn-delete").forEach(btn => {
        btn.addEventListener("click", function(e) {
            if (!confirm("Tem certeza que deseja excluir este item?\\n\\nEsta ação não pode ser desfeita.")) {
                e.preventDefault();
            }
        });
    });
});
</script>';

ob_start();
?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    Administração
                </div>
                <h2 class="page-title">
                    <i class="ti ti-menu-2 me-2"></i>
                    Gerenciar Links do Menu
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="../index-tabler-modern.php" class="btn btn-outline-secondary" target="_blank">
                        <i class="ti ti-eye me-1"></i>
                        Visualizar Portal
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="ti ti-plus me-1"></i>
                        Novo Item
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>
                        <?php if ($messageType === 'success'): ?>
                            <i class="ti ti-check me-2"></i>
                        <?php else: ?>
                            <i class="ti ti-alert-circle me-2"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $message ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Estatísticas -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total de Itens</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-menu-2 fs-1 opacity-75"></i>
                            </div>
                        </div>
                        <div class="h1 m-0"><?= count($links) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Itens Ativos</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-check text-success fs-1"></i>
                            </div>
                        </div>
                        <div class="h1 m-0 text-success">
                            <?= count(array_filter($links, fn($l) => $l['status'] === 'ativo')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Itens Principais</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-home text-primary fs-1"></i>
                            </div>
                        </div>
                        <div class="h1 m-0 text-primary">
                            <?= count(array_filter($links, fn($l) => empty($l['parent_id']))) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Sub-itens</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-folder text-warning fs-1"></i>
                            </div>
                        </div>
                        <div class="h1 m-0 text-warning">
                            <?= count(array_filter($links, fn($l) => !empty($l['parent_id']))) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-list me-2"></i>
                            Lista de Links do Menu
                        </h3>
                        <div class="card-actions">
                            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                                <i class="ti ti-refresh me-1"></i>
                                Atualizar
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tblLinks" class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Título</th>
                                    <th>Descrição</th>
                                    <th class="w-1">Cor</th>
                                    <th>Hierarquia</th>
                                    <th class="w-1">Ordem</th>
                                    <th class="w-1">Alvo</th>
                                    <th class="w-1">Status</th>
                                    <th class="w-1">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $l): 
                                    $isChild = !empty($l['parent_id']);
                                    $isParent = empty($l['parent_id']);
                                    $rowClass = '';
                                    if ($isChild) {
                                        $rowClass = 'hierarchy-indent';
                                    } elseif ($isParent) {
                                        $rowClass = 'parent-row';
                                    }
                                ?>
                                    <tr class="<?= $rowClass ?>"<?php if ($isChild): ?> data-parent="true"<?php endif; ?>>
                                        <td>
                                            <span class="text-muted">#<?= $l['id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($l['icone']): ?>
                                                    <i class="<?= $l['icone'] ?> me-2 text-muted"></i>
                                                <?php endif; ?>
                                                <strong><?= e($l['titulo']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?= e($l['descricao']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($l['cor']): ?>
                                                <span class="color-swatch" style="background-color: <?= $l['cor'] ?>" 
                                                      title="<?= $l['cor'] ?>"></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($l['parent_titulo']): ?>
                                                <span class="badge parent-badge">
                                                    <i class="ti ti-folder me-1"></i>
                                                    <?= e($l['parent_titulo']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="ti ti-home me-1"></i>
                                                    Principal
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $l['ordem'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($l['target_blank']): ?>
                                                <i class="ti ti-external-link text-info" title="Nova aba"></i>
                                            <?php else: ?>
                                                <i class="ti ti-link text-muted" title="Mesma aba"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($l['status'] === 'ativo'): ?>
                                                <span class="badge bg-success status-badge">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger status-badge">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-actions">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="link_editar.php?id=<?= $l['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <a href="link_excluir.php?id=<?= $l['id'] ?>" 
                                                   class="btn btn-outline-danger btn-delete" title="Excluir">
                                                    <i class="ti ti-trash"></i>
                                                </a>
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
    </div>
</div>

<!-- Modal Novo Item -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-plus me-2"></i>
                    Novo Item de Menu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">
                                <i class="ti ti-tag me-1"></i>
                                Título
                            </label>
                            <input name="titulo" class="form-control" required 
                                   placeholder="Ex: Sistema ERP">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">
                                <i class="ti ti-file-text me-1"></i>
                                Descrição
                            </label>
                            <input name="descricao" class="form-control" required 
                                   placeholder="Ex: Acesso ao sistema de gestão">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="ti ti-link me-1"></i>
                        URL de Destino
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ti ti-world"></i>
                        </span>
                        <input name="url" class="form-control" placeholder="https://exemplo.com">
                    </div>
                    <small class="form-hint">Deixe vazio se for apenas um menu pai</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_blank">
                        <span class="form-check-label">
                            <i class="ti ti-external-link me-1"></i>
                            Abrir em nova aba/janela
                        </span>
                    </label>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="ti ti-palette me-1"></i>
                                Cor do Botão
                            </label>
                            <select name="cor" id="cor" class="form-select">
                                <option value="#206bc4">🔵 Azul Padrão</option>
                                <option value="#2590cf">🔷 Azul Claro</option>
                                <option value="rgb(96,54,119)">🟣 Roxo</option>
                                <option value="#dc3545">🔴 Vermelho</option>
                                <option value="#fd7e14">🟠 Laranja</option>
                                <option value="orangered">🟠 Laranja Forte</option>
                                <option value="#ffcb18">🟡 Amarelo</option>
                                <option value="#198754">🟢 Verde</option>
                                <option value="#20c997">🟢 Verde Água</option>
                                <option value="#6f42c1">🟣 Roxo Escuro</option>
                                <option value="#e91e63">🌸 Rosa</option>
                                <option value="#6c757d">⚫ Cinza</option>
                                <option value="black">⚫ Preto</option>
                            </select>
                            <div class="mt-2">
                                <span id="color-preview" class="color-swatch" style="background-color: #206bc4;"></span>
                                <small class="text-muted">Pré-visualização</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="ti ti-layout me-1"></i>
                                Tamanho do Botão
                            </label>
                            <select name="tamanho" class="form-select">
                                <option value="col-lg-2 col-xl-2">📱 Pequeno (2 colunas)</option>
                                <option value="col-lg-3 col-xl-3" selected>📄 Médio (3 colunas)</option>
                                <option value="col-lg-4 col-xl-4">📋 Grande (4 colunas)</option>
                                <option value="col-lg-6 col-xl-6">📊 Extra Grande (6 colunas)</option>
                                <option value="col-lg-12 col-xl-12">📺 Largura Completa (12 colunas)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="ti ti-icons me-1"></i>
                        Ícone do Botão
                    </label>
                    <select name="icone" id="icone" class="form-select">
                        <?php foreach ($tablerIcons as $class => $name): ?>
                            <option value="<?= $class ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-2">
                        <i id="icon-preview" class="icon-preview"></i>
                        <small class="text-muted">Pré-visualização do ícone</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="ti ti-sort-ascending me-1"></i>
                                Ordem
                            </label>
                            <input name="ordem" type="number" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="ti ti-hierarchy me-1"></i>
                                Menu Pai
                            </label>
                            <select name="parent_id" class="form-select">
                                <option value="">🏠 Nenhum (item principal)</option>
                                <?php foreach ($possibleParents as $p): ?>
                                    <option value="<?= $p['id'] ?>">📁 <?= e($p['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="ti ti-toggle-left me-1"></i>
                                Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="ativo" selected>✅ Ativo</option>
                                <option value="inativo">❌ Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="ti ti-window me-1"></i>
                        Tamanho do Modal (se aplicável)
                    </label>
                    <select name="modal_class" class="form-select">
                        <option value="">Padrão</option>
                        <option value="modal-lg">Grande (modal-lg)</option>
                        <option value="modal-xl">Extra Grande (modal-xl)</option>
                        <option value="modal-85" selected>85% da tela</option>
                    </select>
                    <small class="form-hint">Para links que abrem modais no portal</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>
                    Salvar Item
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS específico para correções do modal
$extraCSS = '
<style>
/* Garantir que o modal seja scrollável */
.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
</style>';

require_once 'admin_layout.php';
?>
