<?php
/**
 * admin/link_editar.php
 * Edição de links do menu com interface moderna Tabler
 */

$requiredAccess = 'Gestão de Menu';
include 'check_access.php';

// Verifica se o ID foi passado via GET
if (!isset($_GET['id'])) {
    header('Location: links_admin.php');
    exit;
}

$id = $_GET['id'];

// Busca os dados do link no banco
$sql = "SELECT * FROM menu_links WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    header('Location: links_admin.php');
    exit;
}

// Se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo       = $_POST['titulo'];
    $descricao    = $_POST['descricao'];
    $url          = $_POST['url'];
    $target_blank = isset($_POST['target_blank']) ? 1 : 0;
    $cor          = $_POST['cor'];
    $tamanho      = $_POST['tamanho'];
    $icone        = $_POST['icone'];
    $ordem        = $_POST['ordem'];
    $status       = $_POST['status'];
    $parent_id    = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

    // Atualiza o registro no banco
    $sql = "UPDATE menu_links
            SET titulo = :titulo,
                descricao = :descricao,
                url = :url,
                target_blank = :target_blank,
                cor = :cor,
                tamanho = :tamanho,
                icone = :icone,
                ordem = :ordem,
                status = :status,
                parent_id = :parent_id
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':url', $url);
    $stmt->bindParam(':target_blank', $target_blank);
    $stmt->bindParam(':cor', $cor);
    $stmt->bindParam(':tamanho', $tamanho);
    $stmt->bindParam(':icone', $icone);
    $stmt->bindParam(':ordem', $ordem, PDO::PARAM_INT);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: links_admin.php?success=updated');
    exit;
}

// Consulta dos possíveis pais (exceto ele mesmo)
$sqlParents = "SELECT id, titulo FROM menu_links
               WHERE (parent_id IS NULL OR parent_id = 0) 
                 AND id <> :id
               ORDER BY titulo";
$stmtParents = $pdo->prepare($sqlParents);
$stmtParents->bindParam(':id', $id, PDO::PARAM_INT);
$stmtParents->execute();
$possibleParents = $stmtParents->fetchAll(PDO::FETCH_ASSOC);

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
    'ti ti-minus' => 'Remover',
    'ti ti-check' => 'Confirmar',
    'ti ti-x' => 'Fechar',
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
    'ti ti-chat' => 'Chat',
    'ti ti-video' => 'Vídeo',
    'ti ti-camera' => 'Câmera',
    'ti ti-photo' => 'Foto',
    'ti ti-music' => 'Música',
    'ti ti-volume' => 'Volume',
    'ti ti-map' => 'Mapa',
    'ti ti-location' => 'Localização',
    'ti ti-car' => 'Carro',
    'ti ti-truck' => 'Caminhão',
    'ti ti-plane' => 'Avião',
    'ti ti-building' => 'Prédio',
    'ti ti-home-2' => 'Casa 2',
    'ti ti-office' => 'Escritório',
    'ti ti-school' => 'Escola',
    'ti ti-hospital' => 'Hospital',
    'ti ti-shopping-cart' => 'Carrinho',
    'ti ti-credit-card' => 'Cartão',
    'ti ti-currency-dollar' => 'Dólar',
    'ti ti-currency-real' => 'Real',
    'ti ti-calculator' => 'Calculadora',
    'ti ti-report' => 'Relatório',
    'ti ti-presentation' => 'Apresentação',
    'ti ti-book' => 'Livro',
    'ti ti-notebook' => 'Caderno',
    'ti ti-clipboard' => 'Prancheta',
    'ti ti-list' => 'Lista',
    'ti ti-menu' => 'Menu',
    'ti ti-grid-dots' => 'Grade',
    'ti ti-layout' => 'Layout',
    'ti ti-palette' => 'Paleta',
    'ti ti-brush' => 'Pincel',
    'ti ti-tool' => 'Ferramenta',
    'ti ti-hammer' => 'Martelo',
    'ti ti-wrench' => 'Chave Inglesa',
    'ti ti-device-desktop' => 'Desktop',
    'ti ti-device-laptop' => 'Laptop',
    'ti ti-device-mobile' => 'Mobile',
    'ti ti-device-tablet' => 'Tablet',
    'ti ti-wifi' => 'WiFi',
    'ti ti-bluetooth' => 'Bluetooth',
    'ti ti-usb' => 'USB',
    'ti ti-battery' => 'Bateria',
    'ti ti-plug' => 'Tomada',
    'ti ti-bulb' => 'Lâmpada',
    'ti ti-flame' => 'Chama',
    'ti ti-snowflake' => 'Floco de Neve',
    'ti ti-sun' => 'Sol',
    'ti ti-moon' => 'Lua',
    'ti ti-cloud-rain' => 'Chuva',
    'ti ti-umbrella' => 'Guarda-chuva'
];

$pageTitle = 'Editar Item de Menu';
$extraCSS = '
<style>
    .color-preview {
        display: inline-block;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 2px solid #e9ecef;
        margin-right: 8px;
        vertical-align: middle;
        transition: all 0.2s ease;
    }
    .icon-preview {
        font-size: 1.5rem;
        margin-right: 8px;
        vertical-align: middle;
        color: #495057;
    }
    .form-selectgroup-item {
        margin-bottom: 0.5rem;
    }
    .card-header-tabs .nav-link {
        border-bottom: 2px solid transparent;
    }
    .card-header-tabs .nav-link.active {
        border-bottom-color: var(--tblr-primary);
    }
    .preview-card {
        border: 2px dashed #e9ecef;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .preview-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
</style>';

$extraJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Atualiza preview da cor
    function updateColorPreview() {
        const colorSelect = document.getElementById("cor");
        const preview = document.getElementById("preview-cor");
        const previewCard = document.getElementById("preview-card");
        
        if (colorSelect && preview) {
            preview.style.backgroundColor = colorSelect.value;
            if (previewCard) {
                previewCard.style.backgroundColor = colorSelect.value;
                previewCard.style.color = "white";
            }
        }
    }
    
    // Atualiza preview do ícone
    function updateIconPreview() {
        const iconSelect = document.getElementById("icone");
        const preview = document.getElementById("preview-icon");
        const previewCard = document.getElementById("preview-card-icon");
        
        if (iconSelect && preview) {
            preview.className = "icon-preview " + iconSelect.value;
            if (previewCard) {
                previewCard.className = "preview-icon " + iconSelect.value;
            }
        }
    }
    
    // Atualiza preview do título
    function updateTitlePreview() {
        const titleInput = document.getElementById("titulo");
        const preview = document.getElementById("preview-title");
        
        if (titleInput && preview) {
            preview.textContent = titleInput.value || "Título do Botão";
        }
    }
    
    // Atualiza preview da descrição
    function updateDescPreview() {
        const descInput = document.getElementById("descricao");
        const preview = document.getElementById("preview-desc");
        
        if (descInput && preview) {
            preview.textContent = descInput.value || "Descrição do botão";
        }
    }
    
    // Event listeners
    document.getElementById("cor")?.addEventListener("change", updateColorPreview);
    document.getElementById("icone")?.addEventListener("change", updateIconPreview);
    document.getElementById("titulo")?.addEventListener("input", updateTitlePreview);
    document.getElementById("descricao")?.addEventListener("input", updateDescPreview);
    
    // Inicializa previews
    updateColorPreview();
    updateIconPreview();
    updateTitlePreview();
    updateDescPreview();
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
                    Gestão de Menu
                </div>
                <h2 class="page-title">
                    <i class="ti ti-edit me-2"></i>
                    Editar Item de Menu
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="links_admin.php" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-lg-8">
                <form method="POST" class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                            <li class="nav-item">
                                <a href="#tabs-basic" class="nav-link active" data-bs-toggle="tab">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Informações Básicas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#tabs-appearance" class="nav-link" data-bs-toggle="tab">
                                    <i class="ti ti-palette me-1"></i>
                                    Aparência
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#tabs-advanced" class="nav-link" data-bs-toggle="tab">
                                    <i class="ti ti-settings me-1"></i>
                                    Configurações
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Aba: Informações Básicas -->
                            <div class="tab-pane active" id="tabs-basic">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required">
                                                <i class="ti ti-tag me-1"></i>
                                                Título do Botão
                                            </label>
                                            <input type="text" name="titulo" id="titulo" class="form-control" 
                                                   required value="<?= htmlspecialchars($link['titulo']) ?>"
                                                   placeholder="Ex: Sistema ERP">
                                            <small class="form-hint">Nome que aparecerá no botão do portal</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required">
                                                <i class="ti ti-file-text me-1"></i>
                                                Descrição
                                            </label>
                                            <input type="text" name="descricao" id="descricao" class="form-control" 
                                                   required value="<?= htmlspecialchars($link['descricao']) ?>"
                                                   placeholder="Ex: Acesso ao sistema de gestão">
                                            <small class="form-hint">Descrição curta que aparece no botão</small>
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
                                        <input type="url" name="url" id="url" class="form-control" 
                                               value="<?= htmlspecialchars($link['url']) ?>"
                                               placeholder="https://exemplo.com">
                                    </div>
                                    <small class="form-hint">Deixe vazio se este for apenas um menu pai (sem link direto)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-check">
                                        <input type="checkbox" name="target_blank" id="target_blank" 
                                               class="form-check-input" value="1" 
                                               <?= $link['target_blank'] ? 'checked' : '' ?>>
                                        <span class="form-check-label">
                                            <i class="ti ti-external-link me-1"></i>
                                            Abrir em nova aba/janela
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Aba: Aparência -->
                            <div class="tab-pane" id="tabs-appearance">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-palette me-1"></i>
                                                Cor do Botão
                                            </label>
                                            <select name="cor" id="cor" class="form-select">
                                                <option value="#206bc4" <?= $link['cor'] == '#206bc4' ? 'selected' : '' ?>>
                                                    🔵 Azul Padrão (#206bc4)
                                                </option>
                                                <option value="#2590cf" <?= $link['cor'] == '#2590cf' ? 'selected' : '' ?>>
                                                    🔷 Azul Claro (#2590cf)
                                                </option>
                                                <option value="rgb(96,54,119)" <?= $link['cor'] == 'rgb(96,54,119)' ? 'selected' : '' ?>>
                                                    🟣 Roxo (rgb(96,54,119))
                                                </option>
                                                <option value="#dc3545" <?= $link['cor'] == '#dc3545' ? 'selected' : '' ?>>
                                                    🔴 Vermelho (#dc3545)
                                                </option>
                                                <option value="#fd7e14" <?= $link['cor'] == '#fd7e14' ? 'selected' : '' ?>>
                                                    🟠 Laranja (#fd7e14)
                                                </option>
                                                <option value="orangered" <?= $link['cor'] == 'orangered' ? 'selected' : '' ?>>
                                                    🟠 Laranja Forte (orangered)
                                                </option>
                                                <option value="#ffcb18" <?= $link['cor'] == '#ffcb18' ? 'selected' : '' ?>>
                                                    🟡 Amarelo (#ffcb18)
                                                </option>
                                                <option value="#198754" <?= $link['cor'] == '#198754' ? 'selected' : '' ?>>
                                                    🟢 Verde (#198754)
                                                </option>
                                                <option value="#20c997" <?= $link['cor'] == '#20c997' ? 'selected' : '' ?>>
                                                    🟢 Verde Água (#20c997)
                                                </option>
                                                <option value="#0dcaf0" <?= $link['cor'] == '#0dcaf0' ? 'selected' : '' ?>>
                                                    🔵 Ciano (#0dcaf0)
                                                </option>
                                                <option value="#6f42c1" <?= $link['cor'] == '#6f42c1' ? 'selected' : '' ?>>
                                                    🟣 Roxo Escuro (#6f42c1)
                                                </option>
                                                <option value="#e91e63" <?= $link['cor'] == '#e91e63' ? 'selected' : '' ?>>
                                                    🌸 Rosa (#e91e63)
                                                </option>
                                                <option value="#6c757d" <?= $link['cor'] == '#6c757d' ? 'selected' : '' ?>>
                                                    ⚫ Cinza (#6c757d)
                                                </option>
                                                <option value="black" <?= $link['cor'] == 'black' ? 'selected' : '' ?>>
                                                    ⚫ Preto
                                                </option>
                                            </select>
                                            <div class="mt-2">
                                                <span id="preview-cor" class="color-preview" 
                                                      style="background-color: <?= $link['cor'] ?>;"></span>
                                                <small class="text-muted">Pré-visualização da cor</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-layout me-1"></i>
                                                Tamanho do Botão
                                            </label>
                                            <select name="tamanho" id="tamanho" class="form-select">
                                                <option value="col-lg-2 col-xl-2" <?= $link['tamanho'] == 'col-lg-2 col-xl-2' ? 'selected' : '' ?>>
                                                    📱 Pequeno (2 colunas)
                                                </option>
                                                <option value="col-lg-3 col-xl-3" <?= $link['tamanho'] == 'col-lg-3 col-xl-3' ? 'selected' : '' ?>>
                                                    📄 Médio (3 colunas)
                                                </option>
                                                <option value="col-lg-4 col-xl-4" <?= $link['tamanho'] == 'col-lg-4 col-xl-4' ? 'selected' : '' ?>>
                                                    📋 Grande (4 colunas)
                                                </option>
                                                <option value="col-lg-6 col-xl-6" <?= $link['tamanho'] == 'col-lg-6 col-xl-6' ? 'selected' : '' ?>>
                                                    📊 Extra Grande (6 colunas)
                                                </option>
                                                <option value="col-lg-12 col-xl-12" <?= $link['tamanho'] == 'col-lg-12 col-xl-12' ? 'selected' : '' ?>>
                                                    📺 Largura Completa (12 colunas)
                                                </option>
                                            </select>
                                            <small class="form-hint">Quanto maior o número, mais largo fica o botão</small>
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
                                            <option value="<?= $class ?>" <?= $link['icone'] == $class ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="mt-2">
                                        <i id="preview-icon" class="icon-preview <?= $link['icone'] ?>"></i>
                                        <small class="text-muted">Pré-visualização do ícone</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba: Configurações -->
                            <div class="tab-pane" id="tabs-advanced">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-sort-ascending me-1"></i>
                                                Ordem de Exibição
                                            </label>
                                            <input type="number" name="ordem" id="ordem" class="form-control" 
                                                   value="<?= htmlspecialchars($link['ordem']) ?>" min="0">
                                            <small class="form-hint">Itens com menor número aparecem primeiro</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-hierarchy me-1"></i>
                                                Menu Pai
                                            </label>
                                            <select name="parent_id" id="parent_id" class="form-select">
                                                <option value="" <?= empty($link['parent_id']) ? 'selected' : '' ?>>
                                                    🏠 Nenhum (item principal)
                                                </option>
                                                <?php foreach($possibleParents as $p): ?>
                                                    <option value="<?= $p['id'] ?>" 
                                                            <?= $link['parent_id'] == $p['id'] ? 'selected' : '' ?>>
                                                        📁 <?= htmlspecialchars($p['titulo']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-hint">Se escolher um item, este aparecerá como sub-menu</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-toggle-left me-1"></i>
                                                Status
                                            </label>
                                            <select name="status" id="status" class="form-select">
                                                <option value="ativo" <?= $link['status'] == 'ativo' ? 'selected' : '' ?>>
                                                    ✅ Ativo (visível)
                                                </option>
                                                <option value="inativo" <?= $link['status'] == 'inativo' ? 'selected' : '' ?>>
                                                    ❌ Inativo (oculto)
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="links_admin.php" class="btn btn-outline-secondary me-auto">
                                <i class="ti ti-arrow-left me-1"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Coluna lateral: Preview -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-eye me-2"></i>
                            Pré-visualização
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="preview-card" id="preview-card" style="background-color: <?= $link['cor'] ?>; color: white;">
                            <div>
                                <i id="preview-card-icon" class="preview-icon <?= $link['icone'] ?>"></i>
                                <div>
                                    <strong id="preview-title"><?= htmlspecialchars($link['titulo']) ?></strong>
                                    <div id="preview-desc" class="small opacity-75">
                                        <?= htmlspecialchars($link['descricao']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="ti ti-info-circle me-1"></i>
                            Esta é uma aproximação de como o botão aparecerá no portal
                        </small>
                    </div>
                </div>
                
                <!-- Card de informações -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-info-circle me-2"></i>
                            Informações
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">ID do Item</div>
                                <div class="datagrid-content">#<?= $link['id'] ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Criado em</div>
                                <div class="datagrid-content">
                                    <?= isset($link['created_at']) ? date('d/m/Y H:i', strtotime($link['created_at'])) : 'N/A' ?>
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Status Atual</div>
                                <div class="datagrid-content">
                                    <?php if ($link['status'] == 'ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
?>
