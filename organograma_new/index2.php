<?php
// index2.php — Organograma Moderno com Componentes Reutilizáveis
require_once '../admin/check_access.php';

// Configuração e inicialização
class OrganogramaModerno {
    private $conn;
    private $config;
    
    public function __construct() {
        $this->initializeDatabase();
        $this->loadConfiguration();
    }
    
    private function initializeDatabase() {
        $base = __DIR__;
        $configPath = $base . '/../organograma/config.php';
        
        if (file_exists($configPath)) {
            require_once $configPath;
            $this->conn = $conn ?? null;
        } else {
            throw new Exception('Arquivo de configuração não encontrado');
        }
    }
    
    private function loadConfiguration() {
        $this->config = [
            'empresas' => ['Barão', 'Toymania', 'Alfaness'],
            'view_modes' => ['org' => 'Organograma', 'lista' => 'Lista', 'cards' => 'Cards'],
            'theme_modes' => ['light' => 'Claro', 'dark' => 'Escuro', 'auto' => 'Automático'],
            'items_per_page' => [15, 25, 50, 100]
        ];
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function getConfig() {
        return $this->config;
    }
}

// Helpers modernizados
class OrganogramaHelpers {
    public static function sanitize($data, $type = 'string') {
        switch ($type) {
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
            default:
                return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    
    public static function generateInitials($name) {
        $name = trim($name);
        if (empty($name)) return '??';
        
        $parts = preg_split('/\s+/u', $name);
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1, 'UTF-8') : '';
        
        return mb_strtoupper($first . ($last !== $first ? $last : ''), 'UTF-8');
    }
    
    public static function generateAvatarColor($name) {
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) {
            $hash = ord($name[$i]) + (($hash << 5) - $hash);
        }
        $hue = abs($hash % 360);
        return "hsl({$hue}, 55%, 68%)";
    }
    
    public static function formatPhone($phone) {
        $clean = preg_replace('/\D+/', '', $phone);
        if (strlen($clean) === 11) {
            return '(' . substr($clean, 0, 2) . ') ' . substr($clean, 2, 5) . '-' . substr($clean, 7);
        }
        return $phone;
    }
    
    public static function generateWhatsAppLink($phone) {
        $clean = preg_replace('/\D+/', '', $phone);
        if (empty($clean)) return '';
        if (!str_starts_with($clean, '55')) $clean = '55' . $clean;
        return 'https://wa.me/' . $clean;
    }
    
    public static function generateTeamsLink($teams) {
        $teams = trim($teams);
        if (empty($teams)) return '';
        if (preg_match('/^https?:/i', $teams)) return $teams;
        return 'https://teams.live.com/l/invite/' . rawurlencode($teams);
    }
}

// Classe principal do Organograma
class OrganogramaData {
    private $conn;
    private $helpers;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->helpers = new OrganogramaHelpers();
    }
    
    public function getColaboradores($filters = []) {
        $where = ["COALESCE(ativo, 1) = 1"];
        $params = [];
        $types = '';
        
        // Filtro por empresa
        if (!empty($filters['empresas']) && !in_array('todos', array_map('strtolower', $filters['empresas']))) {
            $empresas = array_filter($filters['empresas'], function($e) {
                return in_array($e, ['Barão', 'Toymania', 'Alfaness']);
            });
            if (!empty($empresas)) {
                $placeholders = str_repeat('?,', count($empresas) - 1) . '?';
                $where[] = "(empresa IN ($placeholders) OR (LOWER(empresa) LIKE '%grupo%' AND (LOWER(empresa) LIKE '%barão%' OR LOWER(empresa) LIKE '%barao%')))";
                $params = array_merge($params, $empresas);
                $types .= str_repeat('s', count($empresas));
            }
        }
        
        // Busca textual
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = "(nome LIKE ? OR cargo LIKE ? OR departamento LIKE ? OR empresa LIKE ? OR email LIKE ?)";
            array_push($params, $search, $search, $search, $search, $search);
            $types .= 'sssss';
        }
        
        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        
        if ($filters['view_mode'] === 'lista') {
            // Paginação para modo lista
            $page = max(1, $filters['page'] ?? 1);
            $per_page = max(15, $filters['per_page'] ?? 15);
            $offset = ($page - 1) * $per_page;
            
            // Contagem total
            $count_sql = "SELECT COUNT(*) as total FROM colaboradores" . $whereClause;
            $count_stmt = $this->executeQuery($count_sql, $params, $types);
            $total = $count_stmt->get_result()->fetch_assoc()['total'];
            
            // Dados paginados
            $sql = "SELECT * FROM colaboradores" . $whereClause . " ORDER BY nome LIMIT ? OFFSET ?";
            array_push($params, $per_page, $offset);
            $types .= 'ii';
            
            $stmt = $this->executeQuery($sql, $params, $types);
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total / $per_page)
                ]
            ];
        } else {
            // Todos os dados para organograma
            $sql = "SELECT * FROM colaboradores" . $whereClause . " ORDER BY COALESCE(nivel_hierarquico, 1), COALESCE(ordem_exibicao, 0), nome";
            $stmt = $this->executeQuery($sql, $params, $types);
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    private function executeQuery($sql, $params, $types) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Erro na preparação da query: ' . $this->conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Erro na execução da query: ' . $stmt->error);
        }
        
        return $stmt;
    }
    
    public function buildHierarchy($colaboradores) {
        $rows = [];
        $children = [];
        
        foreach ($colaboradores as $colaborador) {
            $id = (int)$colaborador['id'];
            $parent_id = $colaborador['parent_id'] !== null ? (int)$colaborador['parent_id'] : null;
            
            $rows[$id] = $colaborador;
            $pid = $parent_id ?? 0;
            if (!isset($children[$pid])) $children[$pid] = [];
            $children[$pid][] = $id;
        }
        
        return ['rows' => $rows, 'children' => $children];
    }
}

// Inicialização
try {
    $organograma = new OrganogramaModerno();
    $conn = $organograma->getConnection();
    $config = $organograma->getConfig();
    $dataManager = new OrganogramaData($conn);
    $helpers = new OrganogramaHelpers();
    
    // Processar parâmetros
    $params = [
        'view_mode' => $_GET['view'] ?? 'org',
        'empresas' => $_GET['empresas'] ?? ['todos'],
        'search' => $_GET['q'] ?? '',
        'page' => (int)($_GET['page'] ?? 1),
        'per_page' => (int)($_GET['per'] ?? 15),
        'theme' => $_GET['theme'] ?? 'auto',
        'zoom' => (float)($_GET['zoom'] ?? 1.0),
        'modo' => $_GET['modo'] ?? 'foco'
    ];
    
    // Buscar dados
    $result = $dataManager->getColaboradores($params);
    
    if ($params['view_mode'] === 'org') {
        $hierarchy = $dataManager->buildHierarchy($result);
        $rows = $hierarchy['rows'];
        $children = $hierarchy['children'];
        $roots = $children[0] ?? [];
    } else {
        $colaboradores = $result['data'];
        $pagination = $result['pagination'];
    }
    
} catch (Exception $e) {
    die('Erro: ' . $e->getMessage());
}

// Função para renderizar nós da árvore
function renderNode($id, $rows, $children, $level = 0, $open = true) {
    $n = $rows[$id];
    $hasKids = !empty($children[$id]);
    $expClass = $hasKids ? 'expandable' : '';
    $openAttr = $open ? ' open' : '';
    
    // Avatar
    $fotoPath = null;
    if (!empty($n['foto'])) {
        $rel = (strpos($n['foto'], 'uploads/') === 0) ? $n['foto'] : 'uploads/' . $n['foto'];
        if (is_file(__DIR__ . '/../organograma/' . $rel)) $fotoPath = '../organograma/' . $rel;
    }
    
    // Dados para o card
    $cardData = htmlspecialchars(json_encode([
        'id' => $n['id'],
        'nome' => $n['nome'],
        'cargo' => $n['cargo'],
        'departamento' => $n['departamento'],
        'empresa' => $n['empresa'],
        'ramal' => $n['ramal'],
        'telefone' => $n['telefone'],
        'email' => $n['email'],
        'teams' => $n['teams'],
        'tipo' => $n['tipo_contrato'],
        'admissao' => $n['data_admissao'],
        'obs' => $n['observacoes'],
        'foto' => $fotoPath
    ]), ENT_QUOTES, 'UTF-8');
    ?>
    <li>
        <details class="<?= $expClass ?>"<?= $openAttr ?>>
            <summary class="person-node" data-card='<?= $cardData ?>'>
                <div class="person-avatar">
                    <?php if ($fotoPath): ?>
                        <img src="<?= $fotoPath ?>" alt="<?= htmlspecialchars($n['nome']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="avatar-initials" style="background: <?= OrganogramaHelpers::generateAvatarColor($n['nome']) ?>">
                            <?= OrganogramaHelpers::generateInitials($n['nome']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="person-info">
                    <div class="person-name"><?= htmlspecialchars($n['nome']) ?></div>
                    <div class="person-title"><?= htmlspecialchars($n['cargo']) ?></div>
                    <?php if (!empty($n['departamento'])): ?>
                        <div class="person-dept"><?= htmlspecialchars($n['departamento']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($hasKids): ?>
                    <div class="expand-indicator">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </summary>
            
            <?php if ($hasKids): ?>
                <ul class="person-children">
                    <?php foreach ($children[$id] as $cid): ?>
                        <?= renderNode($cid, $rows, $children, $level + 1, $level < 1) ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </details>
    </li>
    <?php
}

// Gerar query string para links
function buildQueryString($params, $overrides = []) {
    $merged = array_merge($params, $overrides);
    $parts = [];
    foreach ($merged as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $parts[] = urlencode($key) . '[]=' . urlencode($v);
            }
        } else {
            $parts[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    return implode('&', $parts);
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= $params['theme'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organograma Moderno - Grupo Barão</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    
    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/organograma.css">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" href="../assets/img/logo/logo-cores.png">
    
    <!-- Meta tags -->
    <meta name="description" content="Organograma interativo do Grupo Barão">
    <meta name="theme-color" content="#2563eb">
    
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="header-brand">
                <img src="../assets/img/logo/logo-cores.png" alt="Grupo Barão" class="header-logo">
                <h1 class="header-title">Organograma</h1>
            </div>
            
            <div class="header-actions">
                <!-- Search -->
                <div class="search-container">
                    <input type="search" 
                           class="search-input" 
                           placeholder="Buscar colaborador..." 
                           value="<?= htmlspecialchars($params['search']) ?>"
                           id="searchInput">
                    <div class="search-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C4.01 14 2 11.99 2 9.5S4.01 5 6.5 5 11 7.01 11 9.5 8.99 14 6.5 14z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- View mode selector -->
                <div class="view-selector">
                    <?php foreach ($config['view_modes'] as $mode => $label): ?>
                        <button class="view-btn <?= $params['view_mode'] === $mode ? 'active' : '' ?>" 
                                data-view="<?= $mode ?>">
                            <?= $label ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Theme selector -->
                <div class="theme-selector">
                    <button class="theme-btn" id="themeToggle" title="Alternar tema">
                        <svg class="theme-icon theme-icon--light" viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0 .39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
                        </svg>
                        <svg class="theme-icon theme-icon--dark" viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M9 2c-1.05 0-2.05.16-3 .46 4.06 1.27 7 5.06 7 9.54 0 4.48-2.94 8.27-7 9.54.95.3 1.95.46 3 .46 5.52 0 10-4.48 10-10S14.52 2 9 2z"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Settings -->
                <button class="settings-btn" id="settingsBtn" title="Configurações">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="currentColor" d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                    </svg>
                </button>
                
                <!-- Logout -->
                <a href="../organograma/logout.php" class="logout-btn" title="Sair">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="currentColor" d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Main content -->
    <main class="main-content">
        <?php if ($params['view_mode'] === 'org'): ?>
            <!-- Organograma -->
            <div class="org-container">
                <div class="org-stage" id="orgStage">
                    <svg class="org-wires" id="orgWires"></svg>
                    <div class="org-tree" id="orgTree">
                        <?php if (empty($roots)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">👥</div>
                                <h3>Nenhum colaborador encontrado</h3>
                                <p>Ajuste os filtros e tente novamente</p>
                            </div>
                        <?php else: ?>
                            <ul class="org-level-0">
                                <?php if (count($roots) === 1): ?>
                                    <?= renderNode($roots[0], $rows, $children, 0, true) ?>
                                <?php else: ?>
                                    <li>
                                        <details class="expandable" open>
                                            <summary class="person-node placeholder">
                                                <div class="person-info">
                                                    <div class="person-name">Grupo Barão</div>
                                                </div>
                                            </summary>
                                            <ul class="org-level-1">
                                                <?php foreach ($roots as $rid): ?>
                                                    <?= renderNode($rid, $rows, $children, 0, true) ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Controls -->
                <div class="org-controls">
                    <div class="control-group">
                        <label>Zoom</label>
                        <input type="range" id="zoomRange" min="0.5" max="2" step="0.1" value="<?= $params['zoom'] ?>">
                        <span id="zoomValue"><?= $params['zoom'] ?>×</span>
                    </div>
                    
                    <div class="control-group">
                        <label>Modo de expansão</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="expansionMode" value="foco" <?= $params['modo'] === 'foco' ? 'checked' : '' ?>>
                                <span>Foco</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="expansionMode" value="livre" <?= $params['modo'] === 'livre' ? 'checked' : '' ?>>
                                <span>Livre</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>Empresas</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="selectAllEmpresas" <?= in_array('todos', array_map('strtolower', (array)$params['empresas'])) ? 'checked' : '' ?>>
                                <span>Todas</span>
                            </label>
                            <?php foreach ($config['empresas'] as $empresa): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="empresas[]" value="<?= $empresa ?>" 
                                           <?= in_array($empresa, (array)$params['empresas']) || in_array('todos', array_map('strtolower', (array)$params['empresas'])) ? 'checked' : '' ?>>
                                    <span><?= $empresa ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($params['view_mode'] === 'lista'): ?>
            <!-- Lista -->
            <div class="list-container">
                <div class="list-header">
                    <div class="list-info">
                        <h2>Colaboradores</h2>
                        <p><?= $pagination['total'] ?> registros encontrados</p>
                    </div>
                    <div class="list-actions">
                        <select id="itemsPerPage" class="select-input">
                            <?php foreach ($config['items_per_page'] as $option): ?>
                                <option value="<?= $option ?>" <?= $params['per_page'] === $option ? 'selected' : '' ?>>
                                    <?= $option ?> por página
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-secondary" id="exportBtn">
                            <svg viewBox="0 0 24 24" width="16" height="16">
                                <path fill="currentColor" d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                            </svg>
                            Exportar
                        </button>
                    </div>
                </div>
                
                <div class="list-content">
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Colaborador</th>
                                    <th>Cargo</th>
                                    <th>Departamento</th>
                                    <th>Empresa</th>
                                    <th>Contato</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php foreach ($colaboradores as $colaborador): 
                                    $fotoPath = null;
                                    if (!empty($colaborador['foto'])) {
                                        $rel = (strpos($colaborador['foto'], 'uploads/') === 0) ? $colaborador['foto'] : 'uploads/' . $colaborador['foto'];
                                        if (is_file(__DIR__ . '/../organograma/' . $rel)) $fotoPath = '../organograma/' . $rel;
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="person-cell">
                                                <div class="person-avatar-sm">
                                                    <?php if ($fotoPath): ?>
                                                        <img src="<?= $fotoPath ?>" alt="<?= htmlspecialchars($colaborador['nome']) ?>" loading="lazy">
                                                    <?php else: ?>
                                                        <div class="avatar-initials-sm" style="background: <?= OrganogramaHelpers::generateAvatarColor($colaborador['nome']) ?>">
                                                            <?= OrganogramaHelpers::generateInitials($colaborador['nome']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="person-details">
                                                    <div class="person-name-sm"><?= htmlspecialchars($colaborador['nome']) ?></div>
                                                    <?php if (!empty($colaborador['ramal'])): ?>
                                                        <div class="person-extension">Ramal: <?= htmlspecialchars($colaborador['ramal']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($colaborador['cargo'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($colaborador['departamento'] ?? '—') ?></td>
                                        <td>
                                            <span class="company-badge company-<?= strtolower($colaborador['empresa'] ?? 'default') ?>">
                                                <?= htmlspecialchars($colaborador['empresa'] ?? '—') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="contact-info">
                                                <?php if (!empty($colaborador['email'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($colaborador['email']) ?>" class="contact-link">
                                                        <svg viewBox="0 0 24 24" width="14" height="14">
                                                            <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                                        </svg>
                                                        <?= htmlspecialchars($colaborador['email']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($colaborador['telefone'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($colaborador['telefone']) ?>" class="contact-link">
                                                        <svg viewBox="0 0 24 24" width="14" height="14">
                                                            <path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                                        </svg>
                                                        <?= OrganogramaHelpers::formatPhone($colaborador['telefone']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if (!empty($colaborador['email'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($colaborador['email']) ?>" 
                                                       class="action-btn action-btn--email" 
                                                       title="Enviar e-mail">
                                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                                            <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($colaborador['telefone'])): 
                                                    $waLink = OrganogramaHelpers::generateWhatsAppLink($colaborador['telefone']);
                                                ?>
                                                    <a href="<?= $waLink ?>" 
                                                       class="action-btn action-btn--whatsapp" 
                                                       title="WhatsApp"
                                                       target="_blank" 
                                                       rel="noopener">
                                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                                            <path fill="currentColor" d="M16.75 13.96c.51.82.83 1.76.83 2.76 0 2.84-2.34 5.16-5.22 5.16A5.22 5.22 0 0 1 7.14 16.1c.31.04.62.06.94.06 1.11 0 2.15-.38 2.98-1.02-.53-.01-.99-.36-1.14-.83.18.03.37.05.56.05.22 0 .43-.03.63-.08-.56-.11-1-.6-1.09-1.18.19.04.39.07.6.07.21 0 .41-.03.6-.07-.58-.19-1-.77-1-1.46v-.02c.34.19.74.31 1.17.32-.34-.23-.58-.61-.58-1.05 0-.47.25-.88.63-1.1-.58-.72-1.46-1.2-2.45-1.23.72-.46 1.55-.73 2.44-.73 2.84 0 5.16 2.34 5.16 5.22 0 .41-.05.81-.14 1.2zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.83 11.86c-.18.51-.53.93-.98 1.19-.18.1-.37.18-.56.24-.11.04-.22.07-.34.09-.2.04-.4.06-.6.06s-.4-.02-.6-.06c-.12-.02-.23-.05-.34-.09-.19-.06-.38-.14-.56-.24-.45-.26-.8-.68-.98-1.19-.1-.3-.15-.62-.15-.94 0-.32.05-.64.15-.94.18-.51.53-.93.98-1.19.18-.1.37-.18.56-.24.11-.04.22-.07.34-.09.2-.04.4-.06.6-.06s.4.02.6.06c.12.02.23.05.34.09.19.06.38.14.56.24.45.26.8.68.98 1.19.1.3.15.62.15.94 0 .32-.05.64-.15.94z"/>
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($colaborador['teams'])): 
                                                    $teamsLink = OrganogramaHelpers::generateTeamsLink($colaborador['teams']);
                                                ?>
                                                    <a href="<?= $teamsLink ?>" 
                                                       class="action-btn action-btn--teams" 
                                                       title="Teams"
                                                       target="_blank" 
                                                       rel="noopener">
                                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                                            <path fill="currentColor" d="M13.6 4.8c1.3.3 2.6.7 3.8 1.2.5.2.8.7.8 1.2v5.5c0 .8-.6 1.4-1.4 1.4-.3 0-.5-.1-.7-.2-1.1-.6-2.3-1-3.6-1.3-.8-.2-1.3-.9-1.3-1.8V6c0-.8.6-1.4 1.4-1.4.5 0 .9.2 1.2.6zM9.2 6c0-.8.6-1.4 1.4-1.4h.8c.8 0 1.4.6 1.4 1.4v5.5c0 .8-.6 1.4-1.4 1.4h-.8c-.8 0-1.4-.6-1.4-1.4V6zM2 9.5c0-.8.6-1.4 1.4-1.4h.8c.8 0 1.4.6 1.4 1.4v5.5c0 .8-.6 1.4-1.4 1.4h-.8C2.6 16 2 15.4 2 14.5V9.5z"/>
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="pagination">
                            <button class="page-btn" 
                                    <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>
                                    data-page="<?= $pagination['page'] - 1 ?>">
                                Anterior
                            </button>
                            
                            <?php 
                            $start = max(1, $pagination['page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['page'] + 2);
                            
                            if ($start > 1): ?>
                                <button class="page-btn" data-page="1">1</button>
                                <?php if ($start > 2): ?>
                                    <span class="page-dots">...</span>
                                <?php endif;
                            endif;
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <button class="page-btn <?= $i === $pagination['page'] ? 'active' : '' ?>" 
                                        data-page="<?= $i ?>">
                                    <?= $i ?>
                                </button>
                            <?php endfor;
                            
                            if ($end < $pagination['total_pages']): 
                                if ($end < $pagination['total_pages'] - 1): ?>
                                    <span class="page-dots">...</span>
                                <?php endif; ?>
                                <button class="page-btn" data-page="<?= $pagination['total_pages'] ?>">
                                    <?= $pagination['total_pages'] ?>
                                </button>
                            <?php endif; ?>
                            ?>
                            
                            <button class="page-btn" 
                                    <?= $pagination['page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>
                                    data-page="<?= $pagination['page'] + 1 ?>">
                                Próxima
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($params['view_mode'] === 'cards'): ?>
            <!-- Cards view -->
            <div class="cards-container">
                <div class="cards-header">
                    <h2>Colaboradores</h2>
                    <p><?= count($colaboradores) ?> registros encontrados</p>
                </div>
                
                <div class="cards-grid" id="cardsGrid">
                    <?php foreach ($colaboradores as $colaborador): 
                        $fotoPath = null;
                        if (!empty($colaborador['foto'])) {
                            $rel = (strpos($colaborador['foto'], 'uploads/') === 0) ? $colaborador['foto'] : 'uploads/' . $colaborador['foto'];
                            if (is_file(__DIR__ . '/../organograma/' . $rel)) $fotoPath = '../organograma/' . $rel;
                        }
                    ?>
                        <div class="person-card">
                            <div class="card-header">
                                <div class="person-avatar-card">
                                    <?php if ($fotoPath): ?>
                                        <img src="<?= $fotoPath ?>" alt="<?= htmlspecialchars($colaborador['nome']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="avatar-initials-card" style="background: <?= OrganogramaHelpers::generateAvatarColor($colaborador['nome']) ?>">
                                            <?= OrganogramaHelpers::generateInitials($colaborador['nome']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name"><?= htmlspecialchars($colaborador['nome']) ?></h3>
                                    <p class="card-title"><?= htmlspecialchars($colaborador['cargo']) ?></p>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <?php if (!empty($colaborador['departamento'])): ?>
                                    <div class="card-field">
                                        <span class="card-label">Departamento:</span>
                                        <span class="card-value"><?= htmlspecialchars($colaborador['departamento']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-field">
                                    <span class="card-label">Empresa:</span>
                                    <span class="company-badge company-<?= strtolower($colaborador['empresa'] ?? 'default') ?>">
                                        <?= htmlspecialchars($colaborador['empresa'] ?? '—') ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($colaborador['ramal'])): ?>
                                    <div class="card-field">
                                        <span class="card-label">Ramal:</span>
                                        <span class="card-value"><?= htmlspecialchars($colaborador['ramal']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer">
                                <div class="card-actions">
                                    <?php if (!empty($colaborador['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($colaborador['email']) ?>" 
                                           class="card-action" 
                                           title="E-mail">
                                            <svg viewBox="0 0 24 24" width="18" height="18">
                                                <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($colaborador['telefone'])): 
                                        $waLink = OrganogramaHelpers::generateWhatsAppLink($colaborador['telefone']);
                                    ?>
                                        <a href="<?= $waLink ?>" 
                                           class="card-action" 
                                           title="WhatsApp"
                                           target="_blank" 
                                           rel="noopener">
                                            <svg viewBox="0 0 24 24" width="18" height="18">
                                                <path fill="currentColor" d="M16.75 13.96c.51.82.83 1.76.83 2.76 0 2.84-2.34 5.16-5.22 5.16A5.22 5.22 0 0 1 7.14 16.1c.31.04.62.06.94.06 1.11 0 2.15-.38 2.98-1.02-.53-.01-.99-.36-1.14-.83.18.03.37.05.56.05.22 0 .43-.03.63-.08-.56-.11-1-.6-1.09-1.18.19.04.39.07.6.07.21 0 .41-.03.6-.07-.58-.19-1-.77-1-1.46v-.02c.34.19.74.31 1.17.32-.34-.23-.58-.61-.58-1.05 0-.47.25-.88.63-1.1-.58-.72-1.46-1.2-2.45-1.23.72-.46 1.55-.73 2.44-.73 2.84 0 5.16 2.34 5.16 5.22 0 .41-.05.81-.14 1.2zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.83 11.86c-.18.51-.53.93-.98 1.19-.18.1-.37.18-.56.24-.11.04-.22.07-.34.09-.2.04-.4.06-.6.06s-.4-.02-.6-.06c-.12-.02-.23-.05-.34-.09-.19-.06-.38-.14-.56-.24-.45-.26-.8-.68-.98-1.19-.1-.3-.15-.62-.15-.94 0-.32.05-.64.15-.94.18-.51.53-.93.98-1.19.18-.1.37-.18.56-.24.11-.04.22-.07.34-.09.2-.04.4-.06.6-.06s.4.02.6.06c.12.02.23.05.34.09.19.06.38.14.56.24.45.26.8.68.98 1.19.1.3.15.62.15.94 0 .32-.05.64-.15.94z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($colaborador['teams'])): 
                                        $teamsLink = OrganogramaHelpers::generateTeamsLink($colaborador['teams']);
                                    ?>
                                        <a href="<?= $teamsLink ?>" 
                                           class="card-action" 
                                           title="Teams"
                                           target="_blank" 
                                           rel="noopener">
                                            <svg viewBox="0 0 24 24" width="18" height="18">
                                                <path fill="currentColor" d="M13.6 4.8c1.3.3 2.6.7 3.8 1.2.5.2.8.7.8 1.2v5.5c0 .8-.6 1.4-1.4 1.4-.3 0-.5-.1-.7-.2-1.1-.6-2.3-1-3.6-1.3-.8-.2-1.3-.9-1.3-1.8V6c0-.8.6-1.4 1.4-1.4.5 0 .9.2 1.2.6zM9.2 6c0-.8.6-1.4 1.4-1.4h.8c.8 0 1.4.6 1.4 1.4v5.5c0 .8-.6 1.4-1.4 1.4h-.8c-.8 0-1.4-.6-1.4-1.4V6zM2 9.5c0-.8.6-1.4 1.4-1.4h.8c.8 0 1.4.6 1.4 1.4v5.5c0 .8-.6 1.4-1.4 1.4h-.8C2.6 16 2 15.4 2 14.5V9.5z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Person detail modal -->
    <div id="personModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-avatar" id="modalAvatar"></div>
                <div class="modal-info">
                    <h2 id="modalName"></h2>
                    <p id="modalTitle"></p>
                </div>
                <button class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <div class="modal-actions" id="modalActions"></div>
            </div>
        </div>
    </div>
    
    <!-- Settings panel -->
    <div id="settingsPanel" class="settings-panel">
        <div class="settings-header">
            <h3>Configurações</h3>
            <button class="settings-close" id="settingsClose">&times;</button>
        </div>
        <div class="settings-body">
            <div class="setting-group">
                <label>Tema</label>
                <select id="themeSelect" class="select-input">
                    <?php foreach ($config['theme_modes'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $params['theme'] === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="setting-group">
                <label>Zoom padrão</label>
                <input type="range" id="defaultZoom" min="0.5" max="2" step="0.1" value="<?= $params['zoom'] ?>">
                <span id="defaultZoomValue"><?= $params['zoom'] ?>×</span>
            </div>
            <div class="setting-group">
                <label>Animações</label>
                <label class="toggle-label">
                    <input type="checkbox" id="animationsToggle" checked>
                    <span class="toggle-slider"></span>
                    Ativar animações
                </label>
            </div>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p>Carregando...</p>
    </div>
    
    <!-- Scripts -->
    <script src="js/organograma.js"></script>
    <script>
        // Initialize organograma
        const organograma = new OrganogramaModerno(<?= json_encode($params) ?>);
        
        // Set current view mode
        organograma.setViewMode('<?= $params['view_mode'] ?>');
        
        <?php if ($params['view_mode'] === 'org'): ?>
            // Initialize organograma tree
            organograma.initTree();
        <?php elseif ($params['view_mode'] === 'lista'): ?>
            // Initialize list view
            organograma.initListView();
        <?php elseif ($params['view_mode'] === 'cards'): ?>
            // Initialize cards view
            organograma.initCardsView();
        <?php endif; ?>
    </script>
</body>
</html>