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
    header("Location: index_v2026.php");
    exit;
}

// Carrega config do banco
include_once 'admin/config.php';

// Consulta links do menu
$sql = "SELECT id, titulo, descricao, url, icone, cor, tamanho, parent_id, target_blank, ordem, status FROM menu_links WHERE status='ativo' ORDER BY ordem ASC";
$stmt = $pdo->query($sql);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Paleta de cores (Extraída do original)
$tablerColors = [
    '#206bc4', '#2fb344', '#f59f00', '#d63384', '#ae3ec9', '#17a2b8', '#fd7e14',
    '#e64980', '#6c757d', '#198754', '#dc3545', '#0dcaf0', '#ffc107', '#6f42c1',
    '#20c997', '#fd7e14', '#e83e8c', '#6610f2', '#0d6efd', '#198754'
];

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
    
    <!-- CSS Customizado (Sem dependência do Bootstrap/Tabler CSS) -->
    <style>
        :root {
            /* Palette Light */
            --bg-body: #f8fafc;
            --bg-container: rgba(255, 255, 255, 0.85);
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-card-hover: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --border-color: rgba(226, 232, 240, 0.8);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            /* Shadows & Effects */
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.1);
            --shadow-glow: 0 0 20px rgba(var(--card-rgb, 100,100,100), 0.05);
            
            --header-bg: rgba(255, 255, 255, 0.85);
            --header-border: #e2e8f0;
            --modal-overlay: rgba(15, 23, 42, 0.6);
            
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
            
            --transition-smooth: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            --transition-bounce: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        [data-theme="dark"] {
            /* Palette Dark */
            --bg-body: #0b0f19;
            --bg-container: rgba(15, 23, 42, 0.65);
            --bg-card: #111827;
            --bg-card-hover: #1f2937;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #1e293b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.4);
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.5);
            --shadow-glow: 0 0 20px rgba(var(--card-rgb, 100,100,100), 0.15);
            
            --header-bg: rgba(11, 15, 25, 0.85);
            --header-border: #1e293b;
            --modal-overlay: rgba(0, 0, 0, 0.8);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-body);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
            position: relative;
        }
        
        /* Ambient Background Glow */
        body::before {
            content: '';
            position: fixed;
            top: -200px;
            left: -200px;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(32,107,196,0.15) 0%, rgba(32,107,196,0) 70%);
            z-index: -1;
            border-radius: 50%;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -200px;
            right: -200px;
            width: 1000px;
            height: 1000px;
            background: radial-gradient(circle, rgba(111,66,193,0.15) 0%, rgba(111,66,193,0) 70%);
            z-index: -1;
            border-radius: 50%;
            pointer-events: none;
        }
        [data-theme="dark"] body::before {
            background: radial-gradient(circle, rgba(32,107,196,0.25) 0%, rgba(32,107,196,0) 70%);
        }
        [data-theme="dark"] body::after {
            background: radial-gradient(circle, rgba(111,66,193,0.25) 0%, rgba(111,66,193,0) 70%);
        }

        /* Utility Classes */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .hide { display: none !important; }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-display);
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
            line-height: 1.2;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }

        /* Layout & Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.25rem 0;
            z-index: 100;
            background: var(--header-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--header-border);
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-sm);
        }
        header .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            height: 44px;
            transition: var(--transition-smooth);
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        [data-theme="dark"] .logo {
            filter: brightness(0) invert(1) drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }
        
        /* Theme Toggle Button */
        .btn-icon {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 44px;
            height: 44px;
            border-radius: 50%; /* Modern circle */
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.25rem;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .btn-icon:hover {
            transform: translateY(-2px) scale(1.1) rotate(15deg);
            box-shadow: var(--shadow-md), 0 0 15px rgba(255, 255, 255, 0.2);
            border-color: var(--text-primary);
        }
        .btn-icon:active {
            transform: scale(0.95);
        }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-top: 100px;
            margin-bottom: 4rem;
            min-height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
            align-items: center; 
        }
        
        .hero {
            width: 100%;
            max-width: 1200px;
            text-align: left;
            margin-bottom: 2rem;
            padding: 0 1rem;
            display: flex;
            align-items: baseline;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-title {
            font-size: 1.75rem;
            margin-bottom: 0;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }
        .hero-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 0;
            font-weight: 400;
        }

        /* Hero Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        /* Dashboard Grid */
        .grid-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            animation: float 6s ease-in-out infinite; /* Subtle float for the whole grid area */
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr); /* Force exactly 6 columns */
            gap: 1rem;
            margin-bottom: 4rem;
            justify-content: center;
        }
        
        /* Dashboard Card - Ultra Modern Glassmorphism */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.15rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Left align content for a sleeker look */
            text-align: left;
            position: relative;
            overflow: hidden;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            text-decoration: none;
            color: var(--text-primary);
            min-height: 110px;
            z-index: 1;
        }
        
        /* Animated Gradient Border Effect */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, var(--card-color), transparent 60%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.3;
            transition: var(--transition-smooth);
            pointer-events: none;
            z-index: 2;
        }
        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            border-color: transparent; /* Let the animated border shine */
            background: var(--bg-card-hover);
            z-index: 10;
        }
        .card:hover::before {
            opacity: 1;
            background: linear-gradient(135deg, var(--card-color), rgba(var(--card-rgb), 0.5), transparent);
        }
        
        .card-icon-wrapper {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem; 
            font-size: 1.25rem;
            background: rgba(var(--card-rgb, 100,100,100), 0.1);
            color: var(--card-color, #206bc4);
            transition: var(--transition-bounce);
            position: relative;
        }
        .card:hover .card-icon-wrapper {
            transform: scale(1.1);
            background: var(--card-color);
            color: white;
            box-shadow: 0 4px 8px rgba(var(--card-rgb, 100,100,100), 0.25);
        }

        .card-content {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-width: 0; 
        }
        .card h3 {
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.2;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .section-header {
            width: 100%;
            max-width: 1400px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0 auto 1.5rem auto;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }
        .section-title {
            font-size: 1.4rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i {
            color: #206bc4;
        }

        .news-grid {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* Force 4 columns on desktop */
            gap: 1.5rem;
        }
        .news-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-bounce);
            height: 100%;
            position: relative;
        }
        .news-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: var(--shadow-lg), 0 10px 40px rgba(0,0,0,0.1);
            border-color: rgba(32,107,196,0.4);
        }
        .news-image-wrapper {
            overflow: hidden;
            width: 100%;
            aspect-ratio: 16 / 9; /* more standard than roughly 150px */
            min-height: 180px;
            position: relative;
            background: var(--bg-body);
        }
        .news-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center; 
            border-bottom: 1px solid var(--border-color);
            transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex; /* Back to flex for placeholder centering */
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--text-secondary);
            opacity: 0.8;
            background: var(--bg-body);
        }
        .news-card:hover .news-image {
            transform: scale(1.08); 
            opacity: 1;
        }
        .news-body {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .news-category {
            display: inline-flex;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.3rem 0.75rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            align-self: flex-start;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .cat-portal { background: rgba(32,107,196,0.1); color: #206bc4; border: 1px solid rgba(32,107,196,0.1); }
        [data-theme="dark"] .cat-portal { background: rgba(32,107,196,0.2); color: #60a5fa; border-color: rgba(96,165,250,0.2); }
        .cat-maxtrade { background: rgba(111,66,193,0.1); color: #6f42c1; border: 1px solid rgba(111,66,193,0.1); }
        [data-theme="dark"] .cat-maxtrade { background: rgba(111,66,193,0.2); color: #c084fc; border-color: rgba(192,132,252,0.2); }
        
        .news-body h4 {
            font-size: 1.1rem;
            margin: 0;
            line-height: 1.4;
            font-weight: 700;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .news-body p {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .news-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .news-date {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 500;
        }
        .btn-link {
            font-family: var(--font-display);
            font-weight: 700;
            color: #206bc4;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition-bounce);
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            background: rgba(32,107,196,0.05);
        }
        [data-theme="dark"] .btn-link { color: #60a5fa; background: rgba(96,165,250,0.1); }
        .btn-link:hover {
            gap: 0.6rem;
            background: #206bc4;
            color: white;
        }
        [data-theme="dark"] .btn-link:hover { background: #60a5fa; color: #0b0f19; }

        /* Custom Modal Dialogs */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--modal-overlay);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            padding: 1rem;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: var(--bg-card);
            width: 100%;
            max-width: 900px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg), 0 0 0 1px var(--border-color);
            transform: scale(0.95) translateY(20px);
            opacity: 0;
            transition: var(--transition-bounce);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        .modal-header {
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }
        .modal-header-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.15;
            z-index: 0;
        }
        [data-theme="dark"] .modal-header-bg { opacity: 0.25; }
        .modal-title {
            z-index: 1;
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
        }
        .modal-title i, .modal-title svg {
            color: inherit;
        }
        .modal-desc {
            z-index: 1;
            font-size: 0.95rem;
            opacity: 0.9;
            font-family: var(--font-body);
            font-weight: 400;
            margin-left: 0.5rem;
            border-left: 1px solid currentColor;
            padding-left: 0.5rem;
        }
        .modal-close {
            z-index: 1;
            background: rgba(255,255,255,0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit;
            cursor: pointer;
            transition: var(--transition-smooth);
            font-size: 1.25rem;
            backdrop-filter: blur(4px);
        }
        .modal-close:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.1);
        }
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }
        
        /* Submenu Cards Flex Layout */
        .submenu-grid {
            display: flex;
            flex-wrap: wrap;
            margin: -0.5rem;
        }
        .submenu-grid > div {
            padding: 0.5rem;
            box-sizing: border-box;
            width: 16.666667%; /* default to 6 per row */
        }
        
        .submenu-grid > div[class~="col-12"], .submenu-grid > div[class~="col-sm-12"], .submenu-grid > div[class~="col-md-12"], .submenu-grid > div[class~="col-lg-12"], .submenu-grid > div[class~="col-xl-12"] { width: 100% !important; }
        .submenu-grid > div[class~="col-6"], .submenu-grid > div[class~="col-sm-6"], .submenu-grid > div[class~="col-md-6"], .submenu-grid > div[class~="col-lg-6"], .submenu-grid > div[class~="col-xl-6"] { width: 50% !important; }
        .submenu-grid > div[class~="col-4"], .submenu-grid > div[class~="col-sm-4"], .submenu-grid > div[class~="col-md-4"], .submenu-grid > div[class~="col-lg-4"], .submenu-grid > div[class~="col-xl-4"] { width: 33.333333% !important; }
        .submenu-grid > div[class~="col-3"], .submenu-grid > div[class~="col-sm-3"], .submenu-grid > div[class~="col-md-3"], .submenu-grid > div[class~="col-lg-3"], .submenu-grid > div[class~="col-xl-3"] { width: 25% !important; }
        .submenu-grid > div[class~="col-2"], .submenu-grid > div[class~="col-sm-2"], .submenu-grid > div[class~="col-md-2"], .submenu-grid > div[class~="col-lg-2"], .submenu-grid > div[class~="col-xl-2"] { width: 16.666667% !important; }
        .submenu-card {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.15rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            transition: var(--transition-bounce);
            cursor: pointer;
            height: 100%;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .submenu-card:hover {
            border-color: transparent;
            background: var(--bg-card);
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-lg), 0 0 20px rgba(var(--card-rgb, 100,100,100), 0.2);
            z-index: 2;
        }
        .submenu-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, var(--card-color), transparent 60%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: var(--transition-smooth);
            pointer-events: none;
        }
        .submenu-card:hover::before {
            opacity: 1;
        }
        .submenu-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(var(--card-rgb, 100,100,100), 0.1);
            color: var(--card-color, #206bc4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            transition: var(--transition-smooth);
        }
        .submenu-card:hover .submenu-icon {
            background: var(--card-color, #206bc4);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(var(--card-rgb, 100,100,100), 0.25);
        }
        .submenu-card h4 {
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .submenu-card p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.2;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }

        /* Responsive Settings */
        @media (max-width: 1200px) {
            .grid, .news-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 900px) {
            .grid, .news-grid { grid-template-columns: repeat(3, 1fr); }
            .hero { flex-direction: column; align-items: flex-start; gap: 0.25rem; margin-bottom: 1.5rem; }
            .submenu-grid > .col-md-6 { width: 50% !important; }
            .submenu-grid > .col-md-4 { width: 33.333333% !important; }
        }
        @media (max-width: 600px) {
            .grid, .news-grid { grid-template-columns: repeat(2, 1fr); }
            .card-icon-wrapper { width: 32px; height: 32px; font-size: 1.2rem; }
            .card h3 { font-size: 0.85rem; }
            .card p { font-size: 0.7rem; }
            .submenu-grid > [class*="col-"] { width: 100% !important; }
        }
        @media (max-width: 480px) {
            .grid, .news-grid { grid-template-columns: 1fr; }
            .main-wrapper { padding: 0 1rem; }
            .news-image-wrapper { min-height: 200px; }
        }

        /* Notification System - Top Right */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 380px;
            max-width: calc(100vw - 40px);
            pointer-events: none;
        }

        .notification-item {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 1rem;
            pointer-events: all;
            position: relative;
            overflow: hidden;
            transition: var(--transition-bounce);
            animation: slideInRight 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-height: 70px; /* Initially collapsed */
            cursor: pointer;
            display: flex;
            flex-direction: column;
            border-left: 4px solid #206bc4;
        }

        .notification-item.expanded {
            max-height: 600px;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .notification-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 38px;
        }

        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(32,107,196,0.1);
            color: #206bc4;
            flex-shrink: 0;
            font-size: 1.2rem;
        }

        .notification-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .notification-item.expanded .notification-title {
            white-space: normal;
            overflow: visible;
        }

        .notification-close {
            background: rgba(0,0,0,0.05);
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-close:hover {
            background: #dc3545;
            color: white;
            transform: rotate(90deg);
        }

        .notification-body {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-top: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            height: 0;
            overflow: hidden;
        }

        .notification-item.expanded .notification-body {
            opacity: 1;
            pointer-events: all;
            height: auto;
            overflow: visible;
        }

        .notification-item.alert-success { border-left-color: #198754; }
        .notification-item.alert-success .notification-icon { background: rgba(25, 135, 84, 0.1); color: #198754; }
        
        .notification-item.alert-warning { border-left-color: #ffc107; }
        .notification-item.alert-warning .notification-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

        .notification-item.alert-error { border-left-color: #dc3545; }
        .notification-item.alert-error .notification-icon { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        .notification-item:not(.expanded):hover {
            transform: translateX(-5px);
            background: var(--bg-card-hover);
        }

        /* Adjustments for images inside notifications */
        .notification-body img {
            max-width: 100%;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid var(--border-color);
        }

        .btn-dismiss-forever {
            display: none;
            align-items: center;
            gap: 0.5rem;

            margin-bottom: 15px;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            background: rgba(32,107,196,0.1);
            color: #206bc4;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid rgba(32,107,196,0.2);
            cursor: pointer;
            transition: var(--transition-smooth);
            width: fit-content;
        }

        .notification-item.completed .btn-dismiss-forever {
            display: inline-flex;
        }

        .btn-dismiss-forever:hover {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-color: rgba(220, 53, 69, 0.2);
        }


        /* Notification Interaction - Jump & Timer */
        .notification-item.pulse-attention:not(.expanded) {
            animation: slideInRight 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), 
                       pulseJump 2.5s infinite ease-in-out 1s;
        }

        @keyframes pulseJump {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .notification-close.locked {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .notification-timer-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(0,0,0,0.05);
            display: none;
        }

        .notification-item.expanded .notification-timer-container:not(.completed) {
            display: block;
        }

        .notification-timer-bar {
            height: 100%;
            width: 0%;
            background: #206bc4;
            transition: width 4s linear;
        }

        .notification-item.expanded:not(.completed) .notification-timer-bar {
            width: 100%;
        }

        .notification-item.completed .notification-close {
            opacity: 1;
            pointer-events: all;
            transform: scale(1);
        }
        
        .notification-item.alert-success .notification-timer-bar { background: #198754; }
        .notification-item.alert-info .notification-timer-bar { background: #206bc4; }
        .notification-item.alert-warning .notification-timer-bar { background: #ffc107; }
        .notification-item.alert-error .notification-timer-bar { background: #dc3545; }

        /* Hide legacy Bootstrap modals that may be included but are not used in this modern layout */
        .modal.fade:not(.modal-overlay) {
            display: none !important;
        }
    </style>


</head>
<body>

    <!-- Header -->
    <header>
        <div class="container header-content">
            <a href="index_v2026.php">
                <img src="assets/img/avatars/logo-cores.png" alt="Portal do Grupo Barão" class="logo">
            </a>
            <button class="btn-icon" id="themeToggle" title="Alternar Tema">
                <i class="ti ti-sun" id="themeIcon"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-wrapper container">
        
        <div class="hero">
            <h1 class="hero-title">Portal do Grupo Barão</h1>
            <p class="hero-subtitle">• Ferramentas corporativas</p>
        </div>

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
                    
                <?php if ($hasChildren || empty($url)): 
                    $modalID = "modal-" . $modalCount;
                    $modalCount++;
                ?>
                    <div class="card" 
                         style="--card-color: <?php echo $cor; ?>; --card-rgb: <?php echo $rgb; ?>;"
                         onclick="openModal('<?php echo $modalID; ?>')">
                        <div class="card-icon-wrapper">
                            <?php echo getIconSvg($icone); ?>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($titulo); ?></h3>
                            <p><?php echo htmlspecialchars($descricao); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $url; ?>" target="<?php echo $target; ?>" class="card"
                       style="--card-color: <?php echo $cor; ?>; --card-rgb: <?php echo $rgb; ?>;">
                        <div class="card-icon-wrapper">
                            <?php echo getIconSvg($icone); ?>
                        </div>
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
                            <?php if (!empty($childURL)): ?>
                                <a href="<?php echo $childURL; ?>" target="<?php echo $childTarget; ?>" 
                                   class="submenu-card" style="--card-color: <?php echo $childCor; ?>; --card-rgb: <?php echo $crgb; ?>;">
                                    <div class="submenu-icon">
                                        <?php echo getIconSvg($childIcone); ?>
                                    </div>
                                    <h4><?php echo htmlspecialchars($childTitulo); ?></h4>
                                    <p><?php echo htmlspecialchars($childDescricao); ?></p>
                                </a>
                            <?php else: ?>
                                <div class="submenu-card" style="--card-color: <?php echo $childCor; ?>; --card-rgb: <?php echo $crgb; ?>;">
                                    <div class="submenu-icon">
                                        <?php echo getIconSvg($childIcone); ?>
                                    </div>
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
            }
        }

        // Image Preview Handler
        function openImageModal(src) {
            const img = document.getElementById('previewImage');
            if(img) {
                img.src = src;
                openModal('imagePreviewModal');
            }
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                // If click is directly on the overlay background, close it
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    activeModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
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
            if (theme === 'dark') {
                themeIcon.className = 'ti ti-moon';
            } else {
                themeIcon.className = 'ti ti-sun';
            }
        }
    </script>
</body>
</html>
