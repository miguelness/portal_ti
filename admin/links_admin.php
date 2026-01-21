<?php
/**
 * admin/links_admin.php
 * Gestão de Menu com interface moderna Tabler e Drag-and-Drop Hierárquico
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
        case 'reordered':
            $message = 'Ordem do menu atualizada com sucesso!';
            $messageType = 'success';
            break;
    }
}

// Processa CRUD: Adicionar / Editar / Excluir / Reordenar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
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
                ':ordem'        => $_POST['ordem'] ?? 0, // Será recalculado via JS
                ':status'       => $_POST['status'] ?? 'ativo',
                ':parent_id'    => $_POST['parent_id'] ?: null,
                ':modal_class'  => $_POST['modal_class'] ?? ''
            ]);
            header('Location: links_admin.php?success=added');
            exit;

        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID inválido");

            $sql = "UPDATE menu_links SET
                      titulo = :titulo,
                      descricao = :descricao,
                      url = :url,
                      target_blank = :target_blank,
                      cor = :cor,
                      tamanho = :tamanho,
                      icone = :icone,
                      ordem = :ordem,
                      status = :status,
                      parent_id = :parent_id,
                      modal_class = :modal_class
                    WHERE id = :id";
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
                ':modal_class'  => $_POST['modal_class'] ?? '',
                ':id'           => $id
            ]);
            header('Location: links_admin.php?success=updated');
            exit;

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID inválido");

            $stmt = $pdo->prepare("DELETE FROM menu_links WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header('Location: links_admin.php?success=deleted');
            exit;
            
        } elseif ($action === 'reorder') {
            // Recebe JSON com a estrutura [{id:1, children:[{id:2}, ...]}, ...]
            $input = json_decode($_POST['data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido'); 
            }

            // Função recursiva para atualizar ordem e parent_id
            function updateOrder($items, $parentId = null, &$pdo) {
                foreach ($items as $item) {
                    $order = $item['order']; // Agora pegamos a string "1.1", "1.2" enviada pelo JS
                    $id = (int)$item['id'];
                    
                    // Atualiza o item atual
                    $stmt = $pdo->prepare("UPDATE menu_links SET parent_id = :pid, ordem = :ordem WHERE id = :id");
                    $stmt->execute([':pid' => $parentId, ':ordem' => $order, ':id' => $id]);
                    
                    // Processa filhos recursivamente
                    if (isset($item['children']) && is_array($item['children'])) {
                        updateOrder($item['children'], $id, $pdo);
                    }
                }
            }
            
            $pdo->beginTransaction();
            updateOrder($input, null, $pdo);
            $pdo->commit();
            
            exit('success');
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Erro ao processar: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Busca todos os links
$sql = "SELECT * FROM menu_links ORDER BY ordem, titulo";
$allLinks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Constrói a árvore de menus
function buildTree(array $elements, $parentId = null) {
    $branch = array();
    foreach ($elements as $element) {
        // Verifica se parent_id é null (raiz) ou igual ao id procurado
        $elementParentId = $element['parent_id'] ?: null; // Normaliza 0 e null
        $targetParentId = $parentId ?: null;
        
        if ($elementParentId == $targetParentId) {
            $children = buildTree($elements, $element['id']);
            $element['children'] = $children;
            $branch[] = $element;
        }
    }
    // Ordena pela coluna 'ordem' localmente também, para garantir
    usort($branch, function($a, $b) {
        return $a['ordem'] <=> $b['ordem'];
    });
    return $branch;
}

$menuTree = buildTree($allLinks);

// Possíveis pais para select (plano)
$possibleParents = $pdo->query(
    "SELECT id, titulo FROM menu_links ORDER BY titulo"
)->fetchAll(PDO::FETCH_ASSOC);

// Ícones Tabler
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
    .icon-preview { font-size: 1.2rem; margin-right: 8px; vertical-align: middle; }
    
    /* Sortable Styles */
    .nested-sortable {
        min-height: 20px;
        padding-left: 0;
        margin-bottom: 0;
        list-style: none;
    }
    .nested-sortable .list-group-item {
        cursor: move;
        border: 1px solid #e6e8e9;
        margin-bottom: 5px;
        background: #fff;
        border-radius: 4px;
    }
    .nested-sortable .nested-sortable {
        margin-top: 5px;
        margin-left: 30px; /* Indentação visual */
        border-left: 2px dashed #eee;
        padding-left: 10px;
    }
    .drag-handle {
        cursor: grab;
        color: #aaa;
        padding-right: 10px;
    }
    .ghost-class {
        background-color: #f0f6ff;
        border: 1px dashed #206bc4;
        opacity: 0.8;
    }
    .item-number {
        font-weight: bold;
        color: #666;
        margin-right: 10px;
        min-width: 30px;
        display: inline-block;
    }
</style>';

$extraJS = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Inicializa SortableJS recursivamente
    var nestedSortables = [].slice.call(document.querySelectorAll('.nested-sortable'));

    // Para lidar com aninhamento, usamos um loop
    // Na verdade, basta inicializar em todos os containers .nested-sortable
    // A biblioteca suporta "group" para permitir arrastar entre listas
    
    nestedSortables.forEach(function (el) {
        new Sortable(el, {
            group: 'nested', // Permite arrastar entre listas
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            handle: '.drag-handle', // Só arrasta pelo handle
            ghostClass: 'ghost-class',
            onEnd: function (evt) {
                updateNumbering();
                saveOrder();
            }
        });
    });

    updateNumbering();
    
    // Preview functions no Modal
    function updateColorPreview() {
        const colorSelect = document.getElementById("cor");
        const preview = document.getElementById("color-preview");
        if (colorSelect && preview) preview.style.backgroundColor = colorSelect.value;
    }
    function updateIconPreview() {
        const iconSelect = document.getElementById("icone");
        const preview = document.getElementById("icon-preview");
        if (iconSelect && preview) preview.className = "icon-preview " + iconSelect.value;
    }
    document.getElementById("cor")?.addEventListener("change", updateColorPreview);
    document.getElementById("icone")?.addEventListener("change", updateIconPreview);
});

// Atualiza a numeração hierárquica (1, 1.1, 1.2, etc.)
function updateNumbering() {
    const rootContainer = document.querySelector('#root-list > .nested-sortable');
    
    function processList(list, prefix) {
        const items = Array.from(list.children).filter(el => el.classList.contains('list-group-object'));
        items.forEach((item, index) => {
            const currentNum = prefix ? `${prefix}.${index + 1}` : `${index + 1}`;
            
            // Grava o número calculado em um data-attribute para o saveOrder pegar
            item.dataset.computedOrder = currentNum;

            // Atualiza o texto do número
            const numberSpan = item.querySelector('.item-number');
            if (numberSpan) {
                numberSpan.textContent = currentNum;
                numberSpan.style.display = 'inline-block';
                numberSpan.style.minWidth = '40px';
                numberSpan.className = 'item-number badge bg-muted text-white me-2';
            }
            
            // Processa filhos
            const subList = item.querySelector('.nested-sortable');
            if (subList) {
                processList(subList, currentNum);
            }
        });
    }
    
    if(rootContainer) processList(rootContainer, '');
}

// Salva a ordem via AJAX
function saveOrder() {
    const rootContainer = document.querySelector('#root-list > .nested-sortable');
    if (!rootContainer) return;

    function getStructure(list) {
        const items = Array.from(list.children).filter(el => el.classList.contains('list-group-object'));
        return items.map(item => {
            const id = item.dataset.id;
            const order = item.dataset.computedOrder; // Pega o "1.1", "1.2" etc
            const subList = item.querySelector('.nested-sortable');
            const children = subList ? getStructure(subList) : [];
            return { id: id, order: order, children: children };
        });
    }
    
    const tree = getStructure(rootContainer);
    
    fetch('links_admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=reorder&data=' + encodeURIComponent(JSON.stringify(tree))
    }).then(res => res.text())
      .then(text => {
          if(text.trim() === 'success') {
              console.log('Ordem salva');
          } else {
              console.error('Erro ao salvar ordem:', text);
          }
      });
}

function resetModal() {
    document.getElementById("modalTitle").textContent = "Novo Item de Menu";
    document.getElementById("formAction").value = "create";
    document.getElementById("linkId").value = "";
    
    // Campos
    document.querySelector("input[name='titulo']").value = "";
    document.querySelector("input[name='descricao']").value = "";
    document.querySelector("input[name='url']").value = "";
    document.querySelector("input[name='target_blank']").checked = false;
    document.querySelector("select[name='cor']").value = "#206bc4";
    document.querySelector("select[name='tamanho']").value = "col-lg-3 col-xl-3";
    document.querySelector("select[name='icone']").value = "";
    document.querySelector("input[name='ordem']").value = "0"; // Pode ser string agora
    document.querySelector("select[name='parent_id']").value = "";
    document.querySelector("select[name='status']").value = "ativo";
    document.querySelector("select[name='modal_class']").value = "modal-85";

    // Triggers
    document.getElementById("cor").dispatchEvent(new Event('change'));
    document.getElementById("icone").dispatchEvent(new Event('change'));
}

function editLink(data) {
    document.getElementById("modalTitle").textContent = "Editar Item de Menu";
    document.getElementById("formAction").value = "update";
    document.getElementById("linkId").value = data.id;
    
    // Campos
    document.querySelector("input[name='titulo']").value = data.titulo || "";
    document.querySelector("input[name='descricao']").value = data.descricao || "";
    document.querySelector("input[name='url']").value = data.url || "";
    document.querySelector("input[name='target_blank']").checked = (data.target_blank == 1);
    document.querySelector("select[name='cor']").value = data.cor || "#206bc4";
    document.querySelector("select[name='tamanho']").value = data.tamanho || "col-lg-3 col-xl-3";
    document.querySelector("select[name='icone']").value = data.icone || "";
    document.querySelector("input[name='ordem']").value = data.ordem || 0;
    document.querySelector("select[name='parent_id']").value = data.parent_id || "";
    document.querySelector("select[name='status']").value = data.status || "ativo";
    document.querySelector("select[name='modal_class']").value = data.modal_class || "";

    // Triggers
    document.getElementById("cor").dispatchEvent(new Event('change'));
    document.getElementById("icone").dispatchEvent(new Event('change'));
    
    var myModal = new bootstrap.Modal(document.getElementById("addModal"));
    myModal.show();
}
</script>
JS;

ob_start();
?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administração</div>
                <h2 class="page-title">
                    <i class="ti ti-menu-2 me-2"></i>
                    Gerenciar Links do Menu
                </h2>
                <div class="text-muted mt-1">Arraste os itens para reordenar (suporta hierarquia)</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="../index-tabler-modern.php" class="btn btn-outline-secondary" target="_blank">
                        <i class="ti ti-eye me-1"></i> Visualizar Portal
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal" onclick="resetModal()">
                        <i class="ti ti-plus me-1"></i> Novo Item
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
                    <div><i class="ti ti-<?= $messageType === 'success' ? 'check' : 'alert-circle' ?> me-2"></i></div>
                    <div><?= $message ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                
                <?php 
                // Função recursiva para renderizar HTML
                function renderMenuTree($items) {
                    echo '<div class="list-group nested-sortable" ' . (empty($items) ? 'style="min-height:30px;"' : '') . '>';
                    foreach ($items as $item) {
                        $editData = json_encode([
                            'id' => $item['id'],
                            'titulo' => $item['titulo'],
                            'descricao' => $item['descricao'],
                            'url' => $item['url'],
                            'target_blank' => $item['target_blank'],
                            'cor' => $item['cor'],
                            'tamanho' => $item['tamanho'],
                            'icone' => $item['icone'],
                            'ordem' => $item['ordem'],
                            'status' => $item['status'],
                            'parent_id' => $item['parent_id'],
                            'modal_class' => $item['modal_class']
                        ]);
                        
                        echo '<div class="list-group-item list-group-object p-2" data-id="' . $item['id'] . '">';
                            
                            // Conteúdo do Item
                            echo '<div class="d-flex align-items-center justify-content-between">';
                                echo '<div class="d-flex align-items-center flex-grow-1">';
                                    echo '<span class="drag-handle fs-2"><i class="ti ti-grip-vertical"></i></span>';
                                    echo '<span class="item-number"></span>'; // JS vai preencher
                                    
                                    if ($item['icone']) echo '<i class="' . $item['icone'] . ' me-2 text-muted"></i>';
                                    
                                    echo '<div>';
                                    echo '<div class="font-weight-medium">' . e($item['titulo']) . '</div>';
                                    echo '<div class="text-muted small">' . e($item['descricao']) . '</div>';
                                    echo '</div>';
                                    
                                    if ($item['status'] !== 'ativo') echo '<span class="badge bg-danger ms-2">Inativo</span>';
                                echo '</div>';
                                
                                echo '<div class="btn-list flex-nowrap ms-2">';
                                    echo '<span class="color-swatch" style="background-color:'.$item['cor'].'"></span>';
                                    echo '<button class="btn btn-ghost-primary btn-icon btn-sm" onclick=\'editLink('.$editData.')\' title="Editar"><i class="ti ti-edit"></i></button>';
                                    echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Excluir este item?\');">';
                                        echo '<input type="hidden" name="action" value="delete">';
                                        echo '<input type="hidden" name="id" value="'.$item['id'].'">';
                                        echo '<button type="submit" class="btn btn-ghost-danger btn-icon btn-sm" title="Excluir"><i class="ti ti-trash"></i></button>';
                                    echo '</form>';
                                echo '</div>';
                            echo '</div>'; // End content
                            
                            // Container para filhos
                            renderMenuTree($item['children']);
                            
                        echo '</div>'; // End Item
                    }
                    echo '</div>'; // End List Group
                }
                ?>
                
                <div id="root-list">
                    <?php renderMenuTree($menuTree); ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Novo/Editar Item -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Item de Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="linkId" value="">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required"><i class="ti ti-tag me-1"></i> Título</label>
                            <input name="titulo" class="form-control" required placeholder="Ex: Sistema ERP">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required"><i class="ti ti-file-text me-1"></i> Descrição</label>
                            <input name="descricao" class="form-control" required placeholder="Ex: Acesso ao sistema de gestão">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="ti ti-link me-1"></i> URL de Destino</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-world"></i></span>
                        <input name="url" class="form-control" placeholder="https://exemplo.com">
                    </div>
                    <small class="form-hint">Deixe vazio se for apenas um menu pai</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_blank">
                        <span class="form-check-label"><i class="ti ti-external-link me-1"></i> Abrir em nova aba/janela</span>
                    </label>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="ti ti-palette me-1"></i> Cor do Botão</label>
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
                            <label class="form-label"><i class="ti ti-layout me-1"></i> Tamanho do Botão</label>
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
                    <label class="form-label"><i class="ti ti-icons me-1"></i> Ícone do Botão</label>
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
                            <label class="form-label"><i class="ti ti-sort-ascending me-1"></i> Ordem (manual)</label>
                            <input name="ordem" type="number" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label"><i class="ti ti-hierarchy me-1"></i> Menu Pai</label>
                            <select name="parent_id" class="form-select">
                                <option value="">🏠 Nenhum (item principal)</option>
                                <?php foreach ($possibleParents as $p): ?>
                                    <option value="<?= $p['id'] ?>">📁 <?= e($p['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">Ou arraste na lista para mudar o pai</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><i class="ti ti-toggle-left me-1"></i> Status</label>
                            <select name="status" class="form-select">
                                <option value="ativo" selected>✅ Ativo</option>
                                <option value="inativo">❌ Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="ti ti-window me-1"></i> Tamanho do Modal (se aplicável)</label>
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
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
include 'admin_layout.php';
