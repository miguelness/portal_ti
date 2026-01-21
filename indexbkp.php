<?php
session_start();

// Verificar se há mensagem de incidente na sessão
$incidentMessage = '';
if (isset($_SESSION['incident_message'])) {
    $incidentMessage = $_SESSION['incident_message'];
    unset($_SESSION['incident_message']);
}

// Processar formulário de incidente simplificado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['incident_type'])) {
    $incident_type = $_POST['incident_type'];
    $description = $_POST['description'] ?? '';
    
    // Aqui você pode adicionar a lógica para salvar no banco de dados
    // Por enquanto, apenas definimos uma mensagem de sucesso
    $_SESSION['incident_message'] = 'Incidente reportado com sucesso!';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Incluir configuração do banco de dados
include 'admin/config.php';

// Função para buscar links do menu
function getMenuLinks($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM menu_links WHERE status = 'ativo' ORDER BY ordem ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para construir árvore do menu
function buildMenuTree($links, $parent_id = null) {
    $tree = [];
    foreach ($links as $link) {
        if ($link['parent_id'] == $parent_id) {
            $children = buildMenuTree($links, $link['id']);
            if ($children) {
                $link['children'] = $children;
            }
            $tree[] = $link;
        }
    }
    return $tree;
}

// Buscar links do menu
$menuLinks = getMenuLinks($pdo);
$menuTree = buildMenuTree($menuLinks);

// Buscar últimas 4 notícias do Portal
$stmt = $pdo->prepare("SELECT * FROM noticias WHERE categoria = 'Portal' AND status = 'ativo' ORDER BY data_publicacao DESC LIMIT 4");
$stmt->execute();
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar até 3 alertas ativos
$stmt = $pdo->prepare("SELECT * FROM alerts WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar últimas 2 notícias do Maxtrade
$stmt = $pdo->prepare("SELECT * FROM noticias WHERE categoria = 'Maxtrade' AND status = 'ativo' ORDER BY data_publicacao DESC LIMIT 2");
$stmt->execute();
$noticiasMaxtrade = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Meta tags dinâmicas
$pageTitle = "Portal Grupo Barão";
$pageDescription = "Portal corporativo do Grupo Barão - Acesso a sistemas, notícias e recursos internos";
$pageImage = "assets/logo/logo-cores.png";
?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="auto">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    
    <!-- Meta tags para SEO e redes sociais -->
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>"/>
    <meta name="keywords" content="Grupo Barão, portal corporativo, sistemas internos"/>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"/>
    <meta property="og:title" content="<?php echo $pageTitle; ?>"/>
    <meta property="og:description" content="<?php echo $pageDescription; ?>"/>
    <meta property="og:image" content="<?php echo $pageImage; ?>"/>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image"/>
    <meta property="twitter:title" content="<?php echo $pageTitle; ?>"/>
    <meta property="twitter:description" content="<?php echo $pageDescription; ?>"/>
    <meta property="twitter:image" content="<?php echo $pageImage; ?>"/>
    
    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon"/>
    
    <!-- CSS do Tabler -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@2.44.0/icons-sprite.svg" rel="preload" as="image"/>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Cores baseadas na logomarca do Grupo Barão */
            --tblr-primary: #7B4397;
            --tblr-secondary: #3498DB;
            --tblr-success: #27AE60;
            --tblr-warning: #F1C40F;
            --tblr-danger: #E74C3C;
            --tblr-info: #3498DB;
            --tblr-dark: #2C3E50;
            --tblr-light: #F8F9FA;
            
            /* Gradientes corporativos */
            --gradient-primary: linear-gradient(135deg, #7B4397 0%, #3498DB 100%);
            --gradient-secondary: linear-gradient(135deg, #F1C40F 0%, #E67E22 100%);
            --gradient-dark: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
            
            /* Tipografia */
            --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-heading: 'Poppins', sans-serif;
        }

        [data-bs-theme="dark"] {
            --tblr-body-bg: #1a1d23;
            --tblr-body-color: #e6e7e9;
            --tblr-card-bg: #232730;
            --tblr-border-color: #2d3748;
        }

        body {
            font-family: var(--tblr-font-sans-serif);
            background: var(--tblr-body-bg);
            min-height: 100vh;
        }

        .navbar-brand {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.5rem;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-image {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .hero-section {
            background: #f8f9fa;
            color: #212529;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e9ecef;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .stats-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: none;
        }
        
        .stats-card:hover {
            border-color: #ced4da;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stats-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background-color: #f8f9fa;
            color: #6c757d;
            border-radius: 4px;
        }
        
        .stats-number {
            font-size: 14px;
            line-height: 1.3;
            margin-bottom: 2px;
            color: #212529;
            font-weight: 500;
        }
        
        .stats-label {
            font-size: 12px;
            line-height: 1.2;
            color: #6c757d;
        }
        
        /* Responsive adjustments for 6 columns */
            @media (min-width: 1400px) {
                .col-xl-2 {
                    flex: 0 0 auto;
                    width: 16.66666667%;
                }
            }
            
            /* Sober color scheme */
            body {
                background-color: #f8f9fa;
            }
            
            .container-xl {
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                padding: 2rem;
                margin-top: 1rem;
                margin-bottom: 1rem;
            }

        .news-card {
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            box-shadow: none;
            background: #ffffff;
            height: 100%;
        }

        .news-card:hover {
            border-color: #ced4da;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .news-image {
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .theme-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1050;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--gradient-primary);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .alert-banner {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            border-radius: 0;
        }

        .alert-card {
            border-left: 3px solid #dc3545;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .submenu-item {
            transition: all 0.2s ease;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .submenu-item:hover {
            background: var(--tblr-primary);
            color: white;
            transform: translateX(8px);
        }

        .stats-card {
            background: var(--gradient-primary);
            color: white;
            border: none;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0 1.5rem;
            }
            
            .theme-toggle {
                bottom: 1rem;
                right: 1rem;
                width: 48px;
                height: 48px;
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>

<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Alternar tema">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sun" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
            <circle cx="12" cy="12" r="4"/>
            <path d="m3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>
        </svg>
    </button>

    <!-- Header -->
    <header class="navbar navbar-expand-md navbar-light sticky-top" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
        <div class="container-xl">
            <div class="navbar-brand logo-container">
                <img src="assets/logo/logo-cores.png" alt="Grupo Barão" class="logo-image">
                <span>Portal Grupo Barão</span>
            </div>
            
            <div class="navbar-nav flex-row order-md-last">
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                        <span class="avatar avatar-sm" style="background: var(--gradient-primary);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="7" r="4"/>
                                <path d="m6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                            </svg>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="admin/" class="dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="4" width="6" height="6" rx="1"/>
                                <rect x="14" y="4" width="6" height="6" rx="1"/>
                                <rect x="4" y="14" width="6" height="6" rx="1"/>
                                <rect x="14" y="14" width="6" height="6" rx="1"/>
                            </svg>
                            Painel Administrativo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container-xl">
            <div class="hero-content text-center">
                <h1 class="display-4 fw-bold mb-3 fade-in">Bem-vindo ao Portal Grupo Barão</h1>
                <p class="lead mb-4 fade-in">Seu centro de acesso a sistemas, informações e recursos corporativos</p>
                
                <?php if (!empty($alertas)): ?>
                    <div class="alert alert-banner fade-in" role="alert">
                        <div class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m12 8l0 4"/>
                                <path d="m12 16l.01 0"/>
                            </svg>
                            <strong>Atenção:</strong> Há <?php echo count($alertas); ?> alerta(s) importante(s). 
                            <a href="#" class="text-white text-decoration-underline ms-2" data-bs-toggle="modal" data-bs-target="#alertModal">Ver detalhes</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="page-wrapper">
        <div class="page-body">
            <div class="container-xl">
                
                <!-- Menu Cards -->
                <div class="row g-3 mb-4">
                    <?php foreach ($menuTree as $index => $item): ?>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="card stats-card h-100" 
                                 <?php if (!empty($item['children'])): ?>
                                     data-bs-toggle="modal" data-bs-target="#submenuModal<?php echo $item['id']; ?>"
                                 <?php else: ?>
                                     onclick="window.open('<?php echo htmlspecialchars($item['url']); ?>', '_blank')"
                                 <?php endif; ?>>
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon rounded me-3">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                 <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                                 <?php echo $item['icone'] ?? '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>'; ?>
                                             </svg>
                                         </div>
                                        <div class="flex-1">
                                            <div class="stats-number fw-bold text-dark"><?php echo htmlspecialchars($item['titulo']); ?></div>
                                            <div class="stats-label text-muted small"><?php echo !empty($item['children']) ? count($item['children']) . ' opções' : 'Acesso direto'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- News Section -->
                <?php if (!empty($noticias)): ?>
                <div class="row g-4 mb-5">
                    <div class="col-12">
                        <h2 class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <path d="M16 6h3a1 1 0 0 1 1 1v11a2 2 0 0 1 -4 0v-13a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1v12a3 3 0 0 0 3 3h11"/>
                                <line x1="8" y1="8" x2="12" y2="8"/>
                                <line x1="8" y1="12" x2="12" y2="12"/>
                                <line x1="8" y1="16" x2="12" y2="16"/>
                            </svg>
                            Últimas Notícias
                        </h2>
                    </div>
                    
                    <?php foreach ($noticias as $noticia): ?>
                        <div class="col-lg-3 col-md-6">
                            <div class="card news-card fade-in">
                                <?php if (!empty($noticia['imagem'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($noticia['imagem']); ?>" 
                                         class="news-image" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3 class="card-title h6 mb-2"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
                                    <p class="text-muted small mb-3"><?php echo substr(strip_tags($noticia['conteudo']), 0, 100) . '...'; ?></p>
                                    <a href="blog_post.php?id=<?php echo $noticia['id']; ?>" class="btn btn-primary btn-sm">
                                        Ler mais
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                            <line x1="12" y1="5" x2="19" y2="12"/>
                                            <line x1="12" y1="19" x2="19" y2="12"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="col-12 text-center mt-4">
                        <a href="blog.php" class="btn btn-outline-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12" y2="16"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                            Ver Mais Notícias
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Submenu Modals -->
    <?php foreach ($menuTree as $item): ?>
        <?php if (!empty($item['children'])): ?>
            <div class="modal fade" id="submenuModal<?php echo $item['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                    <?php echo $item['icone'] ?? '<rect x="4" y="4" width="6" height="6" rx="1"/>'; ?>
                                </svg>
                                <?php echo htmlspecialchars($item['titulo']); ?>
                            </h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <?php foreach ($item['children'] as $child): ?>
                                    <div class="col-md-6">
                                        <a href="<?php echo htmlspecialchars($child['url']); ?>" 
                                           target="_blank" 
                                           class="d-block p-3 submenu-item text-decoration-none">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                                        <?php echo $child['icone'] ?? '<circle cx="12" cy="12" r="3"/>'; ?>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($child['titulo']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($child['descricao']); ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Alert Modal -->
    <?php if (!empty($alertas) || !empty($noticiasMaxtrade)): ?>
        <div class="modal fade" id="alertModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m12 8l0 4"/>
                                <path d="m12 16l.01 0"/>
                            </svg>
                            Alertas e Atualizações
                        </h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php foreach ($alertas as $alerta): ?>
                            <div class="alert alert-warning mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="m12 8l0 4"/>
                                            <path d="m12 16l.01 0"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="alert-title"><?php echo htmlspecialchars($alerta['title']); ?></h4>
                                        <div class="text-muted"><?php echo htmlspecialchars($alerta['message']); ?></div>
                                        <?php if (!empty($alerta['file_path'])): ?>
                                            <a href="uploads_alertas/<?php echo htmlspecialchars($alerta['file_path']); ?>" 
                                               class="btn btn-sm btn-primary mt-2" target="_blank">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                                </svg>
                                                Baixar Arquivo
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($noticiasMaxtrade as $noticia): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h5>
                                    <p class="card-text"><?php echo substr(strip_tags($noticia['conteudo']), 0, 200) . '...'; ?></p>
                                    <a href="blog_post.php?id=<?php echo $noticia['id']; ?>" class="btn btn-primary btn-sm">Ler mais</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Success Modal -->
    <?php if (!empty($incidentMessage)): ?>
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h4 class="modal-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m9 12l2 2l4 -4"/>
                            </svg>
                            Sucesso
                        </h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0"><?php echo htmlspecialchars($incidentMessage); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Include report modal -->
    <?php include 'report_modal.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <script>
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // Load saved theme or default to light
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('.icon');
            if (theme === 'dark') {
                icon.innerHTML = `
                    <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                    <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>
                `;
            } else {
                icon.innerHTML = `
                    <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                    <circle cx="12" cy="12" r="4"/>
                    <path d="m3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>
                `;
            }
        }
        
        // Show success modal if there's a message
        <?php if (!empty($incidentMessage)): ?>
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        <?php endif; ?>
        
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add loading states to external links
        document.querySelectorAll('a[target="_blank"]').forEach(link => {
            link.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Carregando...
                `;
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
        
        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
