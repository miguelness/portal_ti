<?php
/**
 * Template base para páginas administrativas
 * Baseado no layout do index.php com parallax background e estrutura visual consistente
 * 
 * Variáveis esperadas:
 * - $pageTitle: Título da página
 * - $content: Conteúdo HTML da página
 * - $extraCSS: CSS adicional (opcional)
 * - $extraJS: JavaScript adicional (opcional)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui configuração e verificação de acesso
require_once 'config.php';

// Função para verificar acessos
if (!function_exists('hasAccess')) {
    function hasAccess($access, $user_accesses) {
        if (in_array('Super Administrador', $user_accesses)) {
            return true;
        }
        return in_array($access, $user_accesses);
    }
}

// Carrega informações do usuário
$nomeUser = $_SESSION['nome'] ?? 'Usuário';
$cargoUser = $_SESSION['cargo'] ?? 'Usuário';

// Cores do Tabler para consistência visual
$tablerColors = [
    '#206bc4', '#79a6dc', '#4299e1', '#0ea5e9', '#06b6d4', '#14b8a6', 
    '#10b981', '#84cc16', '#eab308', '#f59e0b', '#f97316', '#ef4444',
    '#ec4899', '#d946ef', '#8b5cf6', '#6366f1', '#3b82f6'
];
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?= htmlspecialchars($pageTitle ?? 'Painel Administrativo') ?> - TI Grupo Barão</title>
    
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- CSS personalizado adicional -->
    <?= $extraCSS ?? '' ?>
    
    <style>
        /* Parallax Background - baseado no index.php */
        .parallax-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: -1;
            opacity: 0.1;
        }
        
        /* Header Section - baseado no index.php */
        .header-section {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            padding: 1rem 0;
        }
        
        .header-section.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            z-index: -1;
        }
        
        /* Main Container - baseado no index.php */
        .main-container {
            margin-top: 100px;
            min-height: calc(100vh - 100px);
            padding: 2rem 0;
            position: relative;
            z-index: 1;
        }
        
        /* Logo Container */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-container img {
            height: 40px;
            width: auto;
            transition: all 0.3s ease;
        }
        
        .header-section.scrolled .logo-container img {
            height: 35px;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            background: none;
            border: none;
            color: #64748b;
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .theme-toggle:hover {
            background: rgba(100, 116, 139, 0.1);
            color: #334155;
        }
        
        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            text-align: right;
            display: none;
        }
        
        @media (min-width: 768px) {
            .user-info {
                display: block;
            }
        }
        
        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }
        
        .user-role {
            color: #64748b;
            font-size: 0.8rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }
        
        /* Navigation Menu */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin: 0 2rem;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link:hover {
            color: #334155;
            background: rgba(100, 116, 139, 0.1);
        }
        
        .nav-link.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        /* Content Area */
        .content-area {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Dark Theme Support */
        [data-bs-theme="dark"] .header-section {
            background: rgba(30, 41, 59, 0.95);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-bs-theme="dark"] .header-section.scrolled {
            background: rgba(30, 41, 59, 0.98);
        }
        
        [data-bs-theme="dark"] .content-area {
            background: rgba(30, 41, 59, 0.9);
        }
        
        [data-bs-theme="dark"] .user-name {
            color: #f1f5f9;
        }
        
        [data-bs-theme="dark"] .nav-link {
            color: #94a3b8;
        }
        
        [data-bs-theme="dark"] .nav-link:hover {
            color: #f1f5f9;
            background: rgba(148, 163, 184, 0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .main-container {
                margin-top: 80px;
                padding: 1rem 0;
            }
            
            .content-area {
                margin: 0 1rem 1rem;
                padding: 1.5rem;
            }
        }
        
        /* Dropdown Menu Fixes */
        .dropdown-menu {
            z-index: 1030 !important;
            position: absolute !important;
        }
        
        .nav-item.dropdown {
            position: relative;
        }
        
        .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        
        /* SOLUÇÃO DEFINITIVA PARA MODAIS - RESET COMPLETO */
        
        /* Reset completo de todos os estilos de modal */
        .modal,
        .modal *,
        .modal-backdrop {
            pointer-events: initial !important;
            z-index: initial !important;
            position: initial !important;
        }
        
        /* Configuração correta do modal */
        .modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            z-index: 1055 !important;
            width: 100% !important;
            height: 100% !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            outline: 0 !important;
            pointer-events: none !important;
        }
        
        .modal.show {
            display: block !important;
            pointer-events: auto !important;
        }
        
        /* Backdrop configurado corretamente */
        .modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            z-index: 1050 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            pointer-events: auto !important;
        }
        
        /* Dialog e content totalmente funcionais */
        .modal-dialog {
            position: relative !important;
            width: auto !important;
            margin: 1.75rem !important;
            pointer-events: none !important;
            z-index: 1056 !important;
        }
        
        .modal.show .modal-dialog {
            pointer-events: auto !important;
        }
        
        .modal-content {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            pointer-events: auto !important;
            background-color: #fff !important;
            background-clip: padding-box !important;
            border: 1px solid rgba(0, 0, 0, 0.2) !important;
            border-radius: 0.5rem !important;
            outline: 0 !important;
            z-index: 1057 !important;
        }
        
        /* Todos os elementos internos funcionais */
        .modal-header,
        .modal-body,
        .modal-footer {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1058 !important;
        }
        
        /* Formulários e inputs totalmente funcionais */
        .modal input,
        .modal textarea,
        .modal select,
        .modal button,
        .modal .form-control,
        .modal .form-select,
        .modal .btn,
        .modal .btn-close {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1059 !important;
            background-color: transparent !important;
        }
        
        /* Body quando modal aberto */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }
        
        /* Garantir que backdrop seja clicável para fechar */
        .modal-backdrop {
            cursor: pointer !important;
        }
        
        /* Centralização correta */
        .modal-dialog-centered {
            display: flex !important;
            align-items: center !important;
            min-height: calc(100% - 3.5rem) !important;
        }
        
        /* Legacy styles for compatibility */
        .navbar-brand img {
            height: 32px;
            width: auto;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .btn-group-actions .btn {
            margin-right: 0.5rem;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        

    </style>
</head>
<body>
    <!-- Parallax Background -->
    <div class="parallax-bg"></div>
    
    <!-- Header Section -->
    <div class="header-section" id="header">
        <div class="container-xl">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Logo -->
                <div class="logo-container">
                    <a href="index.php">
                        <img src="../assets/logo/logo-cores.png" alt="TI Grupo Barão">
                    </a>
                </div>
                
                <!-- Navigation Menu -->
                <div class="nav-menu d-none d-md-flex">
                    <div class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="ti ti-home"></i>
                            Dashboard
                        </a>
                    </div>
                    
                    <?php if (hasAccess('Gestão de Menu', $user_accesses)): ?>
                    <div class="nav-item">
                        <a href="links_admin.php" class="nav-link">
                            <i class="ti ti-list-details"></i>
                            Menu
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Feeds', $user_accesses) || hasAccess('Feeds RH', $user_accesses)): ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-rss"></i>
                            Feeds
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="admin_postagem.php">
                                <i class="ti ti-rss me-2"></i>Gerenciar Posts
                            </a>
                            <?php if (hasAccess('Moderar Comentários', $user_accesses)): ?>
                            <a class="dropdown-item" href="comments_moderation.php">
                                <i class="ti ti-message-circle me-2"></i>Moderar Comentários
                            </a>
                            <?php endif; ?>
                            <?php if (hasAccess('Estatisticas de Artigos', $user_accesses)): ?>
                            <a class="dropdown-item" href="article_stats.php">
                                <i class="ti ti-chart-bar me-2"></i>Estatísticas
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Reports', $user_accesses)): ?>
                    <div class="nav-item">
                        <a href="reports_admin.php" class="nav-link">
                            <i class="ti ti-chart-bar"></i>
                            Reports
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Documentos RH', $user_accesses) || hasAccess('Gestão de Usuários', $user_accesses)): ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-users"></i>
                            RH
                        </a>
                        <div class="dropdown-menu">
                            <?php if (hasAccess('Documentos RH', $user_accesses)): ?>
                            <a class="dropdown-item" href="rh_documents_admin.php">
                                <i class="ti ti-file-description me-2"></i>Documentos
                            </a>
                            <?php endif; ?>
                            <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
                            <a class="dropdown-item" href="colaboradores.php">
                                <i class="ti ti-users me-2"></i>Gerenciar
                            </a>
                            <a class="dropdown-item" href="colaboradores_import.php">
                                <i class="ti ti-file-import me-2"></i>Importar Planilha
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasAccess('Gestão de Usuários', $user_accesses) || hasAccess('Acessos', $user_accesses)): ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-settings"></i>
                            Admin
                        </a>
                        <div class="dropdown-menu">
                            <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
                            <a class="dropdown-item" href="users_admin.php">
                                <i class="ti ti-users me-2"></i>Usuários
                            </a>
                            <?php endif; ?>
                            <?php if (hasAccess('Acessos', $user_accesses)): ?>
                            <a class="dropdown-item" href="accesses_admin.php">
                                <i class="ti ti-lock-access me-2"></i>Acessos
                            </a>
                            <a class="dropdown-item" href="alerts_admin.php">
                                <i class="ti ti-alert-circle me-2"></i>Alertas
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- User Menu -->
                <div class="user-menu">
                    <!-- Theme Toggle -->
                    <button class="theme-toggle" id="themeToggle" title="Alternar tema">
                        <i id="themeIcon" class="ti ti-sun"></i>
                    </button>
                    
                    <!-- User Info -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-info me-2">
                                <div class="user-name"><?= htmlspecialchars($nomeUser) ?></div>
                                <div class="user-role"><?= htmlspecialchars($cargoUser) ?></div>
                            </div>
                            <div class="user-avatar">
                                <?= strtoupper(substr($nomeUser, 0, 1)) ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="../index.php" class="dropdown-item">
                                <i class="ti ti-home me-2"></i>Portal Principal
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="ti ti-logout me-2"></i>Sair
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <div class="container-xl">
            <div class="content-area">
                <?= $content ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Inicialização manual dos dropdowns do Bootstrap -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar todos os dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
    
    <!-- Script para toggle de tema -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const html = document.documentElement;
            const btn = document.getElementById('themeToggle');
            const ico = document.getElementById('themeIcon');
            const saved = localStorage.getItem('admin-theme') || 'light';
            
            html.setAttribute('data-bs-theme', saved);
            if (saved === 'dark') {
                ico.classList.replace('ti-sun', 'ti-moon');
            }
            
            btn?.addEventListener('click', () => {
                const current = html.getAttribute('data-bs-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-bs-theme', next);
                localStorage.setItem('admin-theme', next);
                ico.classList.replace(
                    next === 'dark' ? 'ti-sun' : 'ti-moon',
                    next === 'dark' ? 'ti-moon' : 'ti-sun'
                );
            });
        });
    </script>
    
    <!-- JavaScript adicional -->
    <?= $extraJS ?? '' ?>
</body>
</html>