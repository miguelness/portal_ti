<?php
session_start();

// Lê a mensagem de sucesso do report (se existir) e remove da sessão
$incidentMessage = null;
if (isset($_SESSION['incident_success'])) {
    $incidentMessage = $_SESSION['incident_success'];
    unset($_SESSION['incident_success']);
}

// PROCESSAMENTO DO FORMULÁRIO DE INCIDENT (exemplo simplificado)
if (isset($_POST['incident_submit'])) {
    // ... (código de inserção no banco, etc.)
    $_SESSION['incident_success'] = "Relatório enviado com sucesso. Obrigado por informar!";
    header("Location: index.php");
    exit;
}

// Carrega config do banco
include_once 'admin/config.php';

// PROCESSAMENTO DO FORMULÁRIO DE SUGESTÕES
$sugestaoMessage = null;
if (isset($_SESSION['sugestao_success'])) {
    $sugestaoMessage = $_SESSION['sugestao_success'];
    unset($_SESSION['sugestao_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sugestao_submit'])) {
    $nome = !empty($_POST['sugestao_nome']) ? trim($_POST['sugestao_nome']) : null;
    $mensagem = trim($_POST['sugestao_mensagem']);
    if (!empty($mensagem)) {
        try {
            $stmtSug = $pdo->prepare("INSERT INTO sugestoes (nome, mensagem) VALUES (:nome, :mensagem)");
            $stmtSug->execute([':nome' => $nome, ':mensagem' => $mensagem]);
            $_SESSION['sugestao_success'] = "Sua sugestão foi enviada com sucesso. Obrigado!";
        } catch (PDOException $e) {
            $_SESSION['sugestao_success'] = "Erro ao enviar sugestão. Tente novamente mais tarde.";
        }
    }
    header("Location: index.php");
    exit;
}

// Consulta links do menu
$sql = "SELECT id, titulo, descricao, url, icone, cor, tamanho, parent_id, target_blank, ordem, status, is_novidade, is_treinamento, is_interno, verificar_estabilidade, link_status, tempo_resposta_ms FROM menu_links WHERE status='ativo' ORDER BY ordem ASC";
$stmt = $pdo->query($sql);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Controle de Acesso por IP ---
// Detecta o IP real do visitante
$visitorIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (strpos($visitorIp, ',') !== false) {
    $visitorIp = trim(explode(',', $visitorIp)[0]);
}
// Também trata ::1 (IPv6 localhost) como 127.0.0.1
if ($visitorIp === '::1') {
    $visitorIp = '127.0.0.1';
}

// Consulta faixas de IP internas ativas
$ipsInternos = $pdo->query("SELECT ip_inicio, ip_fim FROM ips_internos WHERE status = 'ativo'")->fetchAll(PDO::FETCH_ASSOC);

// Verifica se o visitante está na rede interna
$isInternal = false;
$visitorIpLong = ip2long($visitorIp);
if ($visitorIpLong !== false) {
    foreach ($ipsInternos as $faixa) {
        $inicioLong = ip2long($faixa['ip_inicio']);
        if ($faixa['ip_fim']) {
            $fimLong = ip2long($faixa['ip_fim']);
            if ($visitorIpLong >= $inicioLong && $visitorIpLong <= $fimLong) {
                $isInternal = true;
                break;
            }
        } else {
            // IP único
            if ($visitorIpLong === $inicioLong) {
                $isInternal = true;
                break;
            }
        }
    }
}

// Nota: links internos NÃO são removidos. Eles permanecem visíveis,
// mas ao clicar, quem estiver fora da rede interna verá um alerta.

/**
 * Função para mapear ícones para SVG (Extraída do original - não alterada)
 */
function getIconSvg($icone) {
    // Para economizar espaço neste arquivo, mapeamos dinamicamente as classes 'ti ti-*' para elementos i.
    // Usaremos a biblioteca Tabler Icons (apenas os ícones em formato webfont) para manter os ícones funcionando,
    // já que o CSS será totalmente customizado e não dependerá do framework CSS Tabler.
    return '<i class="' . htmlspecialchars($icone) . '"></i>';
}

/**
 * Renderiza o badge de saúde/estabilidade do link
 */
function renderHealthBadge($link) {
    if (empty($link['verificar_estabilidade'])) return '';
    
    $status = $link['link_status'] ?? 'unknown';
    $tempo = !empty($link['tempo_resposta_ms']) ? $link['tempo_resposta_ms'] . 'ms' : '';
    
    switch ($status) {
        case 'online':
            return '<div class="health-badge status-online" title="Tempo de resposta: ' . $tempo . '"><span class="status-dot"></span> Online</div>';
        case 'lento':
            return '<div class="health-badge status-lento" title="Resposta lenta: ' . $tempo . '"><i class="ti ti-alert-triangle"></i> Lento</div>';
        case 'offline':
            return '<div class="health-badge status-offline" title="Link inacessível ('. $tempo .')"><i class="ti ti-wifi-off"></i> Offline</div>';
        default:
            return '';
    }
}

/**
 * Monta a árvore (pais/filhos)
 */
function buildTree(array $elements, $parentId = 0) {
    $branch = [];
    foreach ($elements as $element) {
        $elParent = $element['parent_id'] ?: 0;
        if ($elParent == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}
$menuTree = buildTree($links, 0);

// Consulta últimas 2 notícias do Portal
$sqlNews = "SELECT * FROM noticias WHERE categoria = 'Portal' AND status = 'ativo' ORDER BY data_publicacao DESC LIMIT 2";
$stmtNews = $pdo->query($sqlNews);
$noticias = $stmtNews->fetchAll(PDO::FETCH_ASSOC);

// Consulta alertas ativos (limitando a até três alertas)
$sqlAlerts = "SELECT * FROM alerts WHERE status = 'ativo' ORDER BY display_order ASC, created_at DESC LIMIT 3";
$stmtAlerts = $pdo->query($sqlAlerts);
$alerts = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);

// Consulta últimas 2 notícias da categoria Maxtrade
$sqlMaxtrade = "SELECT * FROM noticias 
                WHERE categoria = 'Maxtrade' AND status = 'ativo' 
                ORDER BY data_publicacao DESC LIMIT 2";
$stmtMaxtrade = $pdo->query($sqlMaxtrade);
$maxtradeNews = $stmtMaxtrade->fetchAll(PDO::FETCH_ASSOC);

// Consulta vídeos de treinamento e políticas (com menu_link_id para filtrar por grupo)
$sqlVideos = "SELECT id, titulo, descricao, url_video, ordem, status, menu_link_id FROM videos_treinamento WHERE status = 'ativo' ORDER BY ordem ASC, id DESC";
$treinamentos = $pdo->query($sqlVideos)->fetchAll(PDO::FETCH_ASSOC);

$sqlAnx = "SELECT * FROM videos_anexos ORDER BY criado_em ASC";
$todosAnx = $pdo->query($sqlAnx)->fetchAll(PDO::FETCH_ASSOC);
$anexosTr = [];
foreach($todosAnx as $ax) { $anexosTr[$ax['video_id']][] = $ax; }

// Paleta de cores (Extraída do original)
$tablerColors = [
    '#206bc4', '#2fb344', '#f59f00', '#d63384', '#ae3ec9', '#17a2b8', '#fd7e14',
    '#e64980', '#6c757d', '#198754', '#dc3545', '#0dcaf0', '#ffc107', '#6f42c1',
    '#20c997', '#fd7e14', '#e83e8c', '#6610f2', '#0d6efd', '#198754'
];

// Consulta servidores para monitoramento compacto
$serversMonitor = [];
try {
    $stmtSrv = $pdo->query("SELECT nome, status, tempo_resposta_ms FROM monitoramento_servidores WHERE exibir_dashboard = 1 AND status_registro = 'ativo' ORDER BY nome ASC");
    $serversMonitor = $stmtSrv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabela pode não existir ainda ou erro na query
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal do Grupo Barão</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <!-- Apenas Fontes e Ícones -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    <link rel="stylesheet" href="assets/css/portal-premium.css">


</head>
<body>

    <!-- Header Premium -->
    <header class="portal-header">
        <div class="header-content">
            <div class="d-flex align-items-center gap-3">
                <a href="index.php">
                    <img src="assets/img/avatars/logo-cores.png" alt="Grupo Barão" class="logo">
                </a>
                
                <!-- System Status Pulse -->
                <?php if (!empty($serversMonitor)): ?>
                <div class="server-status-header d-none d-md-flex align-items-center gap-2 ms-4" style="border-left: 1px solid var(--p-border); padding-left: 1rem;">
                    <?php foreach (array_slice($serversMonitor, 0, 4) as $srv): 
                        $dotClass = 'status-offline';
                        if ($srv['status'] === 'online') $dotClass = 'status-online';
                        elseif ($srv['status'] === 'lento') $dotClass = 'status-lento';
                    ?>
                        <div class="status-pulse-wrapper" title="<?php echo htmlspecialchars($srv['nome']) . ': ' . ucfirst($srv['status']); ?>">
                            <span class="status-dot <?php echo $dotClass; ?>"></span>
                        </div>
                    <?php endforeach; ?>
                    <a href="status_servidores.php" class="text-muted small ms-1" style="font-size: 11px; font-weight: 600; text-decoration: none;">STATUS TI</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="header-actions d-flex align-items-center gap-3">
                <!-- Search Trigger -->
                <button class="btn-icon" id="searchTrigger" title="Buscar Sistemas (Alt+S)">
                    <i class="ti ti-search"></i>
                </button>
                
                <button class="btn-icon" id="themeToggle" title="Alternar Tema">
                    <i class="ti ti-sun" id="themeIcon"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-wrapper">
        
        <!-- Hero Premium Section -->
        <section class="hero-premium animate-in">
            <div class="container text-center">
                <div class="hero-badge">GRUPO BARÃO • PORTAL CORPORATIVO</div>
                <h1 class="display-title">Simplificando o seu <span>dia a dia</span></h1>
                <p class="hero-desc">Acesse todas as ferramentas e sistemas de forma rápida e segura.</p>
                
                <!-- Quick Search Bar -->
                <div class="search-container-hero">
                    <div class="search-box">
                        <i class="ti ti-search search-icon"></i>
                        <input type="text" id="quickSearch" placeholder="Procure por um sistema ou ferramenta..." autocomplete="off">
                        <kbd class="search-kbd">ALT + S</kbd>
                    </div>
                </div>
            </div>
        </section>


        <!-- System Links Grid -->
        <div class="grid-container">
            <div class="grid">
                <?php
                $modalCount = 1;
                $colorIndex = 0;
                $renderedItems = 0;
                foreach ($menuTree as $parentLink):
                    if ($renderedItems >= 12) break; // Limit to exactly 2 rows of 6 items

                    $titulo = $parentLink['titulo'];
                    $descricao = $parentLink['descricao'];
                    $url = $parentLink['url'];
                    $cor = $parentLink['cor'] ?: $tablerColors[$colorIndex % count($tablerColors)];
                    $icone = !empty($parentLink['icone']) ? trim($parentLink['icone']) : 'ti ti-apps';
                    $target = $parentLink['target_blank'] ? "_blank" : "_self";
                    $hasChildren = !empty($parentLink['children']);
                    
                    // Extrai valores RGB da cor
                    $corHex = $cor ?: '#206bc4'; // fallback
                    // Remove everything except hex characters to prevent deprecation warnings in PHP 8+
                    $hexToClean = ltrim($corHex, '#');
                    $hex = preg_replace('/[^0-9a-fA-F]/', '', $hexToClean);
                    
                    // Ensure valid hex length
                    if (strlen($hex) == 3) {
                        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                    } elseif (strlen($hex) != 6) {
                        $hex = '206bc4';
                    }
                    
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                    $rgb = "$r, $g, $b";
                    
                    $colorIndex++;
                ?>

                <?php 
                    $isTreinamento = !empty($parentLink['is_treinamento']);
                    $glowClass = (!empty($parentLink['is_novidade'])) ? 'card-novidade' : '';
                    $isInternoLink = !empty($parentLink['is_interno']);
                    // Se o link é interno e o visitante NÃO é interno, intercepta o click
                    $bloqueado = ($isInternoLink && !$isInternal);
                ?>

                <?php if ($bloqueado): ?>
                    <!-- Link bloqueado para acesso externo -->
                    <div class="card <?php echo $glowClass; ?>" 
                         style="--card-color: <?php echo $cor; ?>; --card-rgb: <?php echo $rgb; ?>; opacity: 0.7; filter: grayscale(30%);"
                         onclick="showInternoAlert()">
                        <div class="card-icon-wrapper">
                            <?php echo getIconSvg($icone); ?>
                        </div>
                        <?php echo renderHealthBadge($parentLink); ?>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($titulo); ?></h3>
                            <p><?php echo htmlspecialchars($descricao); ?></p>
                        </div>
                    </div>

                <?php elseif ($isTreinamento): ?>
                    <div class="card <?php echo $glowClass; ?>" 
                         style="--card-color: <?php echo $cor; ?>; --card-rgb: <?php echo $rgb; ?>;"
                         onclick="openTreinamentosModal(<?php echo (int)$parentLink['id']; ?>)">
                        <div class="card-icon-wrapper">
                            <?php echo getIconSvg($icone); ?>
                        </div>
                        <?php echo renderHealthBadge($parentLink); ?>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($titulo); ?></h3>
                            <p><?php echo htmlspecialchars($descricao); ?></p>
                        </div>
                    </div>

                <?php elseif ($hasChildren || empty($url)): 
                    $modalID = "modal-" . $modalCount;
                    $modalCount++;
                ?>
                    <div class="card <?php echo $glowClass; ?>" 
                         style="--card-color: <?php echo $cor; ?>; --card-rgb: <?php echo $rgb; ?>;"
                         onclick="openModal('<?php echo $modalID; ?>')">
                        <div class="card-icon-wrapper">
                            <?php echo getIconSvg($icone); ?>
                        </div>
                        <?php echo renderHealthBadge($parentLink); ?>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($titulo); ?></h3>
                            <p><?php echo htmlspecialchars($descricao); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $url; ?>" target="<?php echo $target; ?>" class="card <?php echo $glowClass; ?>"
                       style="--card-color: <?php echo $cor; ?>; --card-rgb: <?php echo $rgb; ?>;">
                        <div class="card-icon-wrapper">
                            <?php echo getIconSvg($icone); ?>
                        </div>
                        <?php echo renderHealthBadge($parentLink); ?>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($titulo); ?></h3>
                            <p><?php echo htmlspecialchars($descricao); ?></p>
                        </div>
                    </a>
                <?php endif; ?>
                
                <?php 
                    $renderedItems++;
                endforeach; ?>
            </div>
        </div>

        <!-- News Section -->
        <div class="section-header">
            <h2 class="section-title"><i class="ti ti-news"></i> Updates & Notícias</h2>
        </div>
        
        <div class="news-grid">
            <!-- Portal News -->
            <?php if (!empty($noticias)): ?>
                <?php foreach ($noticias as $noticia): ?>
                    <div class="news-card" onclick="window.open('blog_post.php?id=<?php echo $noticia['id']; ?>&from=index', '_blank')" style="cursor: pointer;">
                        <div class="news-image-wrapper">
                            <?php if (!empty($noticia['imagem'])): ?>
                                <img src="<?php echo htmlspecialchars($noticia['imagem']); ?>" class="news-image" alt="Notícia">
                            <?php else: ?>
                                <div class="news-image"><i class="ti ti-photo"></i></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="news-body">
                            <span class="news-category cat-portal">Blog do TI</span>
                            <h4><?php echo htmlspecialchars($noticia['titulo']); ?></h4>
                            <p><?php echo mb_strimwidth(strip_tags($noticia['conteudo'] ?? ''), 0, 120, '...'); ?></p>
                            
                            <div class="news-footer">
                                <span class="news-date"><i class="ti ti-calendar"></i> <?php echo date('d/m/Y', strtotime($noticia['data_publicacao'])); ?></span>
                                <a href="blog_post.php?id=<?php echo $noticia['id']; ?>&from=index" target="_blank" class="btn-link">Ler mais <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Maxtrade News -->
            <?php if (!empty($maxtradeNews)): ?>
                <?php foreach ($maxtradeNews as $news): ?>
                    <div class="news-card" onclick="window.open('blog_post.php?id=<?php echo $news['id']; ?>&from=index', '_blank')" style="cursor: pointer;">
                        <div class="news-image-wrapper">
                            <?php if (!empty($news['imagem'])): ?>
                                <img src="<?php echo htmlspecialchars($news['imagem']); ?>" class="news-image" alt="Notícia">
                            <?php else: ?>
                                <div class="news-image"><i class="ti ti-photo"></i></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="news-body">
                            <span class="news-category cat-maxtrade">Maxtrade</span>
                            <h4><?php echo htmlspecialchars($news['titulo']); ?></h4>
                            <p><?php echo mb_strimwidth(strip_tags($news['conteudo'] ?? ''), 0, 120, '...'); ?></p>
                            
                            <div class="news-footer">
                                <span class="news-date"><i class="ti ti-calendar"></i> <?php echo date('d/m/Y', strtotime($news['data_publicacao'])); ?></span>
                                <a href="blog_post.php?id=<?php echo $news['id']; ?>&from=index" target="_blank" class="btn-link">Ler mais <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date('Y'); ?> Grupo Barão • Sistema Integrado Corporativo</p>
        </div>
    </footer>

    <!-- Modals Rendering -->
    <?php
    $modalCount = 1;
    $colorIndex = 0;
    
    // Map legacy color names to HEX
    $legacyColorMap = [
        'Preto' => '#1e293b',
        'black' => '#1e293b',
        'Azul' => '#206bc4',
        'blue' => '#206bc4',
        'Azul Claro' => '#4299e1',
        'Verde' => '#2fb344',
        'green' => '#2fb344',
        'Vermelho' => '#dc3545',
        'red' => '#dc3545',
        'Amarelo' => '#f59f00',
        'yellow' => '#f59f00',
        'Laranja' => '#fd7e14',
        'orange' => '#fd7e14',
        'Roxo' => '#6f42c1',
        'purple' => '#6f42c1',
        'Rosa' => '#d63384',
        'pink' => '#d63384',
        'Cinza' => '#6c757d',
        'gray' => '#6c757d',
        'Branco' => '#ffffff',
        'white' => '#ffffff'
    ];
    
    foreach ($menuTree as $parentLink):
        if (!empty($parentLink['children'])):
            $modalID = "modal-" . $modalCount;
            $modalCount++;
            $cor = $parentLink['cor'] ?: $tablerColors[$colorIndex % count($tablerColors)];
            
            // Resolve named colors or default to fallback
            $corValue = isset($legacyColorMap[$cor]) ? $legacyColorMap[$cor] : $cor;
            $corHex = $corValue ?: '#206bc4'; 
            
            $hexToClean = ltrim($corHex, '#');
            $hex = preg_replace('/[^0-9a-fA-F]/', '', $hexToClean);
            if (strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            } elseif (strlen($hex) != 6) {
                // If it entirely failed to parse (e.g., random string), fallback to a standard dark slate
                $hex = '1e293b'; 
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $rgb = "$r, $g, $b";
            $colorIndex++;
            
            // Contrast calculation (YIQ) format for legibility
            $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
            // If background is light (yiq >= 150), use dark text. Otherwise, use white text.
            $textColor = ($yiq >= 150) ? '#1e293b' : '#ffffff';
            $finalBackgroundColor = '#' . $hex;
    ?>
    <div class="modal-overlay" id="<?php echo $modalID; ?>">
        <div class="modal-content">
            <div class="modal-header" style="background-color: <?php echo $finalBackgroundColor; ?>;">
                <div class="modal-header-bg" style="background-color: <?php echo $finalBackgroundColor; ?>;"></div>
                <h2 class="modal-title" style="color: <?php echo $textColor; ?> !important;">
                    <?php echo getIconSvg($parentLink['icone'] ?: 'ti ti-apps'); ?>
                    <?php echo htmlspecialchars($parentLink['titulo']); ?>
                    <?php if (!empty($parentLink['descricao'])): ?>
                        <span class="modal-desc"><?php echo htmlspecialchars($parentLink['descricao']); ?></span>
                    <?php endif; ?>
                </h2>
                <button class="modal-close" onclick="closeModal('<?php echo $modalID; ?>')">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="submenu-grid">
                    <?php foreach ($parentLink['children'] as $child):
                        $childTitulo = $child['titulo'];
                        $childDescricao = $child['descricao'];
                        $childURL = $child['url'];
                        $childCor = $child['cor'] ?: $cor;
                        $childIcone = $child['icone'] ?: 'ti ti-link';
                        $childTarget = $child['target_blank'] ? "_blank" : "_self";
                        $tamanhoClass = !empty($child['tamanho']) ? $child['tamanho'] : 'col-lg-2 col-md-4';
                        $childGlow = (!empty($child['is_novidade'])) ? 'card-novidade' : '';
                        
                        $childCorHex = $childCor ?: '#206bc4';
                        $cHexToClean = ltrim($childCorHex, '#');
                        $cHex = preg_replace('/[^0-9a-fA-F]/', '', $cHexToClean);
                        if (strlen($cHex) == 3) {
                            $cHex = $cHex[0].$cHex[0].$cHex[1].$cHex[1].$cHex[2].$cHex[2];
                        } elseif (strlen($cHex) != 6) {
                            $cHex = '206bc4';
                        }
                        $cr = hexdec(substr($cHex, 0, 2));
                        $cg = hexdec(substr($cHex, 2, 2));
                        $cb = hexdec(substr($cHex, 4, 2));
                        $crgb = "$cr, $cg, $cb";
                    ?>
                        <div class="<?php echo htmlspecialchars($tamanhoClass); ?>">
                            <?php 
                                $childBloqueado = (!empty($child['is_interno']) && !$isInternal);
                            ?>
                            <?php if ($childBloqueado): ?>
                                <div class="submenu-card <?php echo $childGlow; ?>" 
                                     style="--card-color: <?php echo $childCor; ?>; --card-rgb: <?php echo $crgb; ?>; opacity: 0.5; filter: grayscale(40%); cursor: pointer;"
                                     onclick="closeModal('<?php echo $modalID; ?>'); setTimeout(function(){ showInternoAlert(); }, 350);">
                                    <div class="submenu-icon">
                                        <?php echo getIconSvg($childIcone); ?>
                                    </div>
                                    <?php echo renderHealthBadge($child); ?>
                                    <h4><?php echo htmlspecialchars($childTitulo); ?></h4>
                                    <p><?php echo htmlspecialchars($childDescricao); ?></p>
                                </div>
                            <?php elseif (!empty($childURL)): ?>
                                <a href="<?php echo $childURL; ?>" target="<?php echo $childTarget; ?>" 
                                   class="submenu-card <?php echo $childGlow; ?>" style="--card-color: <?php echo $childCor; ?>; --card-rgb: <?php echo $crgb; ?>;">
                                    <div class="submenu-icon">
                                        <?php echo getIconSvg($childIcone); ?>
                                    </div>
                                    <?php echo renderHealthBadge($child); ?>
                                    <h4><?php echo htmlspecialchars($childTitulo); ?></h4>
                                    <p><?php echo htmlspecialchars($childDescricao); ?></p>
                                </a>
                            <?php else: ?>
                                <div class="submenu-card <?php echo $childGlow; ?>" style="--card-color: <?php echo $childCor; ?>; --card-rgb: <?php echo $crgb; ?>;">
                                    <div class="submenu-icon">
                                        <?php echo getIconSvg($childIcone); ?>
                                    </div>
                                    <?php echo renderHealthBadge($child); ?>
                                    <h4><?php echo htmlspecialchars($childTitulo); ?></h4>
                                    <p><?php echo htmlspecialchars($childDescricao); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
        endif;
    endforeach;
    ?>



    <!-- Notification System Container -->
    <div class="notification-container" id="notificationContainer">
        <?php if ($incidentMessage): ?>
            <div class="notification-item alert-success pulse-attention" onclick="handleNotificationClick(this)">
                <div class="notification-header">
                    <div class="notification-icon"><i class="ti ti-circle-check"></i></div>
                    <span class="notification-title">Sucesso</span>
                    <button class="notification-close locked" onclick="event.stopPropagation(); this.closest('.notification-item').remove();">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="notification-body">
                    <?php echo htmlspecialchars($incidentMessage); ?>
                </div>
                <div class="notification-timer-container">
                    <div class="notification-timer-bar"></div>
                </div>
            </div>
        <?php endif; ?>


        <?php if (!empty($alerts)): ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="notification-item alert-info pulse-attention" 
                     data-alert-id="<?php echo $alert['id']; ?>"
                     onclick="handleNotificationClick(this)">
                    <div class="notification-header">
                        <div class="notification-icon"><i class="ti ti-bell"></i></div>
                        <span class="notification-title"><?php echo htmlspecialchars($alert['title']); ?></span>
                        <button class="notification-close locked" onclick="event.stopPropagation(); this.closest('.notification-item').remove();">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <div class="notification-body">
                        <button class="btn-dismiss-forever" onclick="dismissAlertForever(event, '<?php echo $alert['id']; ?>')">
                            <i class="ti ti-eye-off"></i> Silenciar este alerta permanentemente
                        </button>

                        <div class="alert-message-content">
                            <?php echo $alert['message']; ?>
                        </div>

                        <?php if (!empty($alert['image'])): ?>
                            <img src="uploads_alertas/<?php echo htmlspecialchars($alert['image']); ?>" 
                                 alt="Imagem do Alerta"
                                 onclick="event.stopPropagation(); openImageModal(this.src)">
                        <?php endif; ?>
                        <?php if (!empty($alert['file_path'])): ?>
                            <div style="margin-top: 10px;">
                                <a href="uploads_alertas/<?php echo htmlspecialchars($alert['file_path']); ?>" 
                                   download 
                                   class="btn-link"
                                   onclick="event.stopPropagation();"
                                   style="display: inline-flex; width: fit-content;">
                                    <i class="ti ti-download"></i> Baixar anexo
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="notification-timer-container">
                        <div class="notification-timer-bar"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- Image Preview Modal -->
    <div class="modal-overlay" id="imagePreviewModal" style="background: rgba(0,0,0,0.85);">
        <div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: transparent; box-shadow: none; border: none; align-items: center; justify-content: center; overflow: visible;">
            <button class="modal-close" onclick="closeModal('imagePreviewModal')" style="position: absolute; top: -40px; right: 0; background: rgba(255,255,255,0.2); color: white;">
                <i class="ti ti-x"></i>
            </button>
            <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 80vh; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        </div>
    </div>

    <!-- External Report Modal -->
    <?php include 'report_modal.php'; ?>

    <!-- Scripts -->

    <script>
        // Notification Logic

        function handleNotificationClick(el) {
            // Se já foi lido por 4s, apenas alterna expansão normalmente
            if (el.classList.contains('completed')) {
                el.classList.toggle('expanded');
                return;
            }

            // Ativa expansão
            el.classList.add('expanded');
            el.classList.remove('pulse-attention');

            // Inicia timer se ainda não começou
            if (!el.dataset.timerStarted) {
                el.dataset.timerStarted = "true";
                
                // Força o reflow para a animação CSS da barra de progresso iniciar
                const bar = el.querySelector('.notification-timer-bar');
                void bar.offsetWidth; 

                setTimeout(() => {
                    el.classList.add('completed');
                    el.querySelector('.notification-close').classList.remove('locked');
                    const timerContainer = el.querySelector('.notification-timer-container');
                    if(timerContainer) timerContainer.classList.add('completed');
                }, 4000); // 4 segundos
            }
        }

        function dismissAlertForever(event, alertId) {
            event.stopPropagation();
            
            // Salva no localStorage
            let dismissed = JSON.parse(localStorage.getItem('dismissedAlerts') || '[]');
            if (!dismissed.includes(alertId)) {
                dismissed.push(alertId);
            }
            localStorage.setItem('dismissedAlerts', JSON.stringify(dismissed));
            
            // Remove o elemento da tela com efeito
            const item = document.querySelector(`.notification-item[data-alert-id="${alertId}"]`);
            if (item) {
                item.style.transform = 'translateX(100%)';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 300);
            }
        }

        // Check for dismissed alerts on load
        document.addEventListener('DOMContentLoaded', function() {
            const dismissed = JSON.parse(localStorage.getItem('dismissedAlerts') || '[]');
            dismissed.forEach(id => {
                const item = document.querySelector(`.notification-item[data-alert-id="${id}"]`);
                if (item) item.remove();
            });
        });

    <?php if(isset($treinamentos)): ?>
    </script>
    <div class="modal-overlay modal-treinamento" id="modalTreinamentos">
        <div class="modal-content-wrapper fade-in">
            <div class="treinamento-header">
                <h3 class="m-0"><i class="ti ti-school me-2 text-primary"></i> Centro de Treinamentos e Políticas Corporativas</h3>
                <button class="close-modal" onclick="closeModal('modalTreinamentos')"><i class="ti ti-x"></i></button>
            </div>
            <div class="treinamento-body" id="treinamentoRenderer">
                <!-- Preenchido via JS -->
            </div>
        </div>
    </div>
    <script>
        const treinamentosData = <?= json_encode($treinamentos) ?>;
        const anexosData = <?= json_encode($anexosTr) ?>;
        let currentFilteredVideos = []; // Vídeos filtrados para o grupo atual
        
        function renderTreinamento(id) {
            const tr = currentFilteredVideos.find(t => t.id == id);
            if(!tr) return;
            
            const anexos = anexosData[id] || [];
            let anexosHtml = '';
            if(anexos.length > 0) {
                anexosHtml = `<h4 class="mb-3" style="font-weight: 600; color: var(--text-primary);"><i class="ti ti-paperclip me-1"></i> Documentos em Anexo</h4><div class="d-flex flex-wrap gap-2">`;
                anexos.forEach(a => {
                    anexosHtml += `<a href="uploads_treinamentos/${a.caminho_arquivo}" target="_blank" class="btn btn-sm" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-primary); text-decoration:none; padding:10px 16px; border-radius:8px; display:inline-flex; align-items:center; transition: all 0.2s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='var(--bg-body)'"><i class="ti ti-file-download me-2" style="font-size:1.2rem; color:#d63384;"></i> <span style="font-weight:500;">${a.nome_documento}</span></a>`;
                });
                anexosHtml += `</div>`;
            } else {
                anexosHtml = `<div class="text-muted"><i class="ti ti-info-circle me-1"></i> Este treinamento não possui documentos em anexo.</div>`;
            }
            
            let playlistHtml = '';
            currentFilteredVideos.forEach(t => {
                const isActive = t.id == id ? 'active' : '';
                playlistHtml += `
                    <div class="treinamento-item ${isActive}" onclick="renderTreinamento(${t.id})">
                        <div class="treinamento-item-icon"><i class="ti ti-player-play-filled"></i></div>
                        <div>
                            <div class="treinamento-item-title">${t.titulo}</div>
                            <div class="treinamento-item-desc">${t.descricao ? t.descricao.substring(0, 80) + '...' : ''}</div>
                        </div>
                    </div>
                `;
            });
            
            // Tratamento inteligente de URLs
            let finalUrl = tr.url_video;
            
            // Corrige YouTube (watch -> embed)
            if (finalUrl.includes('youtube.com/watch?v=')) {
                let videoId = new URL(finalUrl).searchParams.get('v');
                if (videoId) finalUrl = `https://www.youtube.com/embed/${videoId}`;
            } 
            // Corrige YouTube (youtu.be -> embed)
            else if (finalUrl.includes('youtu.be/')) {
                let videoId = finalUrl.split('youtu.be/')[1].split('?')[0];
                finalUrl = `https://www.youtube.com/embed/${videoId}`;
            }
            // Corrige Vimeo genérico (vimeo.com/xxxxx -> player.vimeo.com/video/xxxxx)
            else if (finalUrl.includes('vimeo.com/') && !finalUrl.includes('player.vimeo.com')) {
                let vimeoParts = finalUrl.split('vimeo.com/');
                let idPart = vimeoParts[1].split('?')[0].split('/')[0];
                let videoId = idPart.replace(/\D/g, '');
                
                if (videoId) {
                    finalUrl = `https://player.vimeo.com/video/${videoId}`;
                }
            }
            
            const html = `
                <div class="treinamento-player-col">
                    <div class="treinamento-video-container">
                        <iframe src="${finalUrl}" allowfullscreen allow="autoplay; encrypted-media"></iframe>
                    </div>
                    <div class="treinamento-anexos">
                        ${anexosHtml}
                    </div>
                </div>
                <div class="treinamento-playlist-col">
                    <div class="treinamento-playlist-header"><i class="ti ti-list me-1"></i> Conteúdos (${currentFilteredVideos.length})</div>
                    <div class="treinamento-playlist-items">
                        ${playlistHtml}
                    </div>
                </div>
            `;
            document.getElementById('treinamentoRenderer').innerHTML = html;
        }
        
        function openTreinamentosModal(menuLinkId) {
            // Filtra os vídeos pelo menu_link_id do cartão clicado
            currentFilteredVideos = treinamentosData.filter(t => t.menu_link_id == menuLinkId);
            
            if(currentFilteredVideos.length > 0) {
                renderTreinamento(currentFilteredVideos[0].id);
            } else {
                document.getElementById('treinamentoRenderer').innerHTML = '<div class="p-5 w-100 text-center text-muted"><i class="ti ti-video-off fs-1 mb-3"></i><br><h4>Ainda não há treinamentos cadastrados nesta categoria.</h4></div>';
            }
            openModal('modalTreinamentos');
        }
    <?php endif; ?>


    <!-- Modal de Alerta: Acesso Interno -->
    </script>
    <div class="modal-overlay" id="modalInternoAlert">
        <div class="modal-content" style="max-width: 420px; border-radius: 20px; overflow: hidden; padding: 0;">
            <div style="background: linear-gradient(135deg, #d63939 0%, #e25050 100%); padding: 2.5rem 2rem 1.5rem; text-align: center; color: white;">
                <i class="ti ti-lock" style="font-size: 3.5rem; margin-bottom: 0.75rem; display: block; opacity: 0.9;"></i>
                <h3 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Acesso Restrito</h3>
            </div>
            <div style="padding: 2rem; text-align: center; background: var(--bg-card);">
                <p style="font-size: 1.05rem; color: var(--text-primary); margin: 0 0 0.5rem; font-weight: 600;">
                    Este recurso é de <strong>uso interno</strong>.
                </p>
                <p style="font-size: 0.88rem; color: var(--text-secondary); margin: 0 0 1.75rem; line-height: 1.6;">
                    O acesso a este link está disponível apenas dentro da rede corporativa. 
                    Conecte-se à rede interna da empresa ou utilize a VPN para acessar.
                </p>
                <button onclick="closeModal('modalInternoAlert')" 
                        style="background: linear-gradient(135deg, #d63939 0%, #e25050 100%); color: white; border: none; padding: 0.75rem 2.5rem; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(214,57,57,0.3);"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(214,57,57,0.4)'" 
                        onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 12px rgba(214,57,57,0.3)'">
                    <i class="ti ti-check me-1"></i> Entendi
                </button>
            </div>
        </div>
    </div>
    
    <!-- Botão Flutuante de Sugestões -->
    <button class="btn-floating-sugestao" onclick="openModal('modalSugestoes')" title="Deixe sua sugestão">
        <i class="ti ti-message-2-share"></i>
    </button>

    <!-- Modal de Sugestões -->
    <div class="modal-overlay <?= $sugestaoMessage ? 'active' : '' ?>" id="modalSugestoes">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h3 style="margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ti ti-message-2-share" style="color: #6f42c1;"></i> Deixe sua Sugestão
                </h3>
                <button onclick="closeModal('modalSugestoes')" style="background: none; border: none; font-size: 1.5rem; color: var(--text-secondary); cursor: pointer; padding: 0;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem 2rem;">
                
                <?php if ($sugestaoMessage): ?>
                <div style="background: rgba(43, 182, 115, 0.1); border-left: 4px solid #2bb673; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: start; gap: 0.75rem;">
                    <i class="ti ti-circle-check" style="color: #2bb673; font-size: 1.5rem;"></i>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.25rem; color: #2bb673; font-weight: 600; font-family: var(--font-body); font-size: 0.95rem;">Sucesso!</h4>
                        <div style="color: var(--text-secondary); font-size: 0.85rem; font-family: var(--font-body);"><?= htmlspecialchars($sugestaoMessage) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Sua opinião é muito importante! Você pode enviar uma sugestão de forma anônima ou preencher seu nome.
                </p>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <div style="margin-bottom: 1rem;">
                        <label for="sugestao_nome" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Nome (Opcional)</label>
                        <input type="text" id="sugestao_nome" name="sugestao_nome" placeholder="Seu nome" 
                               style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-primary); font-family: var(--font-body); font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="sugestao_mensagem" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Mensagem *</label>
                        <textarea id="sugestao_mensagem" name="sugestao_mensagem" rows="4" placeholder="Escreva sua sugestão aqui..." required
                                  style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-primary); font-family: var(--font-body); font-size: 0.9rem; resize: vertical;"></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" onclick="closeModal('modalSugestoes')" style="background: none; border: none; padding: 0.75rem 1.5rem; font-size: 0.9rem; color: var(--text-secondary); cursor: pointer; font-weight: 600; margin-right: 0.5rem;">Cancelar</button>
                        <button type="submit" name="sugestao_submit" style="background: linear-gradient(135deg, #6f42c1 0%, #ae3ec9 100%); color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(111, 66, 193, 0.3); transition: all 0.2s;">Enviar Sugestão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Spotlight Quick Search
        const quickSearch = document.getElementById('quickSearch');
        const cards = document.querySelectorAll('.grid .card');

        quickSearch.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            
            cards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const desc = card.querySelector('p')?.textContent.toLowerCase() || '';
                
                if (title.includes(term) || desc.includes(term)) {
                    card.style.display = 'flex';
                    card.classList.add('animate-in');
                } else {
                    card.style.display = 'none';
                    card.classList.remove('animate-in');
                }
            });
        });

        // Focus search with Alt+S
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key.toLowerCase() === 's') {
                quickSearch.focus();
                quickSearch.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        function showInternoAlert() {
            openModal('modalInternoAlert');
        }

        // Modal Handlers
        function openModal(id) {
            const overlay = document.getElementById(id);
            if(overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(id) {
            const overlay = document.getElementById(id);
            if(overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                if (id === 'modalTreinamentos') {
                    const renderer = document.getElementById('treinamentoRenderer');
                    if(renderer) renderer.innerHTML = '';
                }
            }
        }

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) closeModal(activeModal.id);
            }
        });

        // Theme Toggle Logic
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme') || 'light';
        body.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', function() {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            themeIcon.className = theme === 'dark' ? 'ti ti-moon' : 'ti ti-sun';
        }
    </script>
</body>
</html>

