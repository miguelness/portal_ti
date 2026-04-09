<?php
/**
 * Template base para páginas administrativas
 * Modernizado utilizando layout boxed do Tabler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once __DIR__ . '/../utils/scheduler_runner.php';

// Roda o agendador de tarefas (Web Cron)
if (isset($pdo)) {
    runScheduler($pdo);
}
// ou deve estar disponível na sessão. Não sobrescrevê-la aqui para evitar perda de dados.

if (!function_exists('hasAccess')) {
    function hasAccess($access, $user_accesses) {
        if (!is_array($user_accesses)) return false;
        if (in_array('Super Administrador', $user_accesses)) {
            return true;
        }
        return in_array($access, $user_accesses);
    }
}

$nomeUser = $_SESSION['nome'] ?? 'Usuário';
$cargoUser = $_SESSION['cargo'] ?? 'Usuário';
// Se $user_accesses não estiver definido (caso o check_access não tenha sido chamado antes), pega da sessão
if (!isset($user_accesses)) {
    $user_accesses = $_SESSION['acessos'] ?? [];
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= htmlspecialchars($pageTitle ?? 'Painel Administrativo') ?> - TI Grupo Barão</title>
    
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
        .navbar-brand-image {
            height: 2rem;
            width: auto;
        }
    </style>
    
    <?= $extraCSS ?? '' ?>
</head>
<body class="layout-boxed">
    <div class="page">
        <!-- Navbar Superior -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-codepen pe-0 pe-md-3">
                    <a href="index.php" class="text-decoration-none d-flex align-items-center">
                        <img src="../assets/logo/logo-cores.png" alt="TI Grupo Barão" class="navbar-brand-image me-2">
                        <span class="d-none d-sm-inline text-reset">Painel TI</span>
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="d-none d-md-flex me-3">
                        <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <i class="ti ti-moon"></i>
                        </a>
                        <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <i class="ti ti-sun"></i>
                        </a>
                        
                        <!-- Agendamentos Status/QuickLink -->
                        <?php if (hasAccess('Super Administrador', $user_accesses)): ?>
                        <div class="nav-item d-none d-md-flex me-3">
                            <a href="agendamentos_admin.php" class="nav-link px-0" title="Agendamentos de Scripts" data-bs-toggle="tooltip">
                                <i class="ti ti-clock-play"></i>
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- Notificações -->
                        <div class="nav-item dropdown d-none d-md-flex me-3">
                            <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications" id="btn-notifications">
                                <i class="ti ti-bell"></i>
                                <span class="badge bg-red d-none" id="notification-count"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Notificações</h3>
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable" id="notification-list">
                                        <div class="list-group-item">
                                            <div class="text-muted text-center p-2">Nenhuma notificação nova</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm"><?= strtoupper(substr($nomeUser, 0, 1)) ?></span>
                            <div class="d-none d-xl-block ps-2 text-start">
                                <div><?= htmlspecialchars($nomeUser) ?></div>
                                <div class="mt-1 small text-muted"><?= htmlspecialchars($cargoUser) ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="../index.php" class="dropdown-item">Portal Principal</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Navbar de Navegação -->
        <header class="navbar-expand-md navbar-light border-bottom">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item <?= ($pageTitle == 'Dashboard') ? 'active' : '' ?>">
                                <a class="nav-link" href="index.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-home"></i></span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>

                            <?php if (hasAccess('Gestão de Menu', $user_accesses)): ?>
                            <li class="nav-item <?= ($pageTitle == 'Menu') ? 'active' : '' ?>">
                                <a class="nav-link" href="links_admin.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-list-details"></i></span>
                                    <span class="nav-link-title">Menu</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (hasAccess('Super Administrador', $user_accesses)): ?>
                            <li class="nav-item <?= ($pageTitle == 'Gerenciar Treinamentos e Políticas') ? 'active' : '' ?>">
                                <a class="nav-link" href="treinamentos_admin.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-video"></i></span>
                                    <span class="nav-link-title">Treinamentos</span>
                                </a>
                            </li>
                            <li class="nav-item <?= ($pageTitle == 'Gestão de IPs Internos') ? 'active' : '' ?>">
                                <a class="nav-link" href="ips_admin.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-network"></i></span>
                                    <span class="nav-link-title">IPs Internos</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (hasAccess('Feeds', $user_accesses) || hasAccess('Feeds RH', $user_accesses)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-rss"></i></span>
                                    <span class="nav-link-title">Feeds</span>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="admin_postagem.php">Gerenciar Posts</a>
                                    <?php if (hasAccess('Moderar Comentários', $user_accesses)): ?>
                                    <a class="dropdown-item" href="comments_moderation.php">Comentários</a>
                                    <?php endif; ?>
                                    <?php if (hasAccess('Estatisticas de Artigos', $user_accesses)): ?>
                                    <a class="dropdown-item" href="article_stats.php">Estatísticas</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endif; ?>

                            <?php if (hasAccess('Reports', $user_accesses)): ?>
                            <li class="nav-item <?= ($pageTitle == 'Reports') ? 'active' : '' ?>">
                                <a class="nav-link" href="reports_admin.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-chart-bar"></i></span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (hasAccess('Super Administrador', $user_accesses) || hasAccess('Sugestões', $user_accesses) || hasAccess('Gestão de Usuários', $user_accesses)): ?>
                            <li class="nav-item <?= ($pageTitle == 'Caixa de Sugestões') ? 'active' : '' ?>">
                                <a class="nav-link" href="sugestoes_admin.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-message-2-share"></i></span>
                                    <span class="nav-link-title">Sugestões</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (hasAccess('Documentos RH', $user_accesses) || hasAccess('Colaboradores', $user_accesses) || hasAccess('Gestão de Colaboradores', $user_accesses)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-users"></i></span>
                                    <span class="nav-link-title">RH</span>
                                </a>
                                <div class="dropdown-menu">
                                    <?php if (hasAccess('Documentos RH', $user_accesses)): ?>
                                    <a class="dropdown-item" href="rh_documents_admin.php">Documentos</a>
                                    <?php endif; ?>
                                    <?php if (hasAccess('Colaboradores', $user_accesses) || hasAccess('Gestão de Colaboradores', $user_accesses)): ?>
                                    <a class="dropdown-item" href="colaboradores.php">Gerenciar</a>
                                    <a class="dropdown-item" href="colaboradores_import.php">Importar Planilha</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endif; ?>

                            <?php if (hasAccess('Gestão de Usuários', $user_accesses) || hasAccess('Acessos', $user_accesses) || hasAccess('Organograma', $user_accesses)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-layout" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-settings"></i></span>
                                    <span class="nav-link-title">Admin</span>
                                </a>
                                <div class="dropdown-menu">
                                    <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
                                    <a class="dropdown-item" href="users_admin.php">Usuários</a>
                                    <?php endif; ?>
                                    <?php if (hasAccess('Organograma', $user_accesses)): ?>
                                    <a class="dropdown-item" href="organograma_admin.php">Organograma</a>
                                    <?php endif; ?>
                                    <?php if (hasAccess('Acessos', $user_accesses)): ?>
                                    <a class="dropdown-item" href="accesses_admin.php">Acessos</a>
                                    <a class="dropdown-item" href="alerts_admin.php">Alertas</a>
                                    <?php endif; ?>
                                    <?php if (hasAccess('Super Administrador', $user_accesses)): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="servidores_admin.php">Gestão de Servidores</a>
                                    <a class="dropdown-item" href="status_servidores_admin.php" target="_blank">Monitoramento Full (TI)</a>
                                    <a class="dropdown-item" href="agendamentos_admin.php">Agendamentos (Cron)</a>
                                    <a class="dropdown-item" href="../status_servidores.php" target="_blank">Status Público (Site)</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <?= $content ?>
            
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-lg-auto ms-lg-auto">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">Grupo Barão</li>
                            </ul>
                        </div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; <?= date('Y') ?>
                                    <a href="." class="link-secondary">TI Grupo Barão</a>.
                                    Todos os direitos reservados.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Libs JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme Script
            const themeStorageKey = 'portalTheme';
            const defaultTheme = 'light';
            let selectedTheme;

            const urlParams = new URLSearchParams(window.location.search);
            const themeParam = urlParams.get('theme');

            if (themeParam) {
                localStorage.setItem(themeStorageKey, themeParam);
                selectedTheme = themeParam;
            } else {
                const storedTheme = localStorage.getItem(themeStorageKey);
                selectedTheme = storedTheme ? storedTheme : defaultTheme;
            }

            if (selectedTheme === 'dark') {
                document.body.setAttribute('data-bs-theme', 'dark');
            } else {
                document.body.removeAttribute('data-bs-theme');
            }

            // Stacking context fix for modals
            document.querySelectorAll('.modal').forEach(function(modal) {
                if (modal.parentElement && modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
            });

            // Notificações Logic
            function loadNotifications() {
                fetch('api_notifications.php?action=list_unread')
                    .then(res => res.json())
                    .then(res => {
                        if (res.success && res.data.length > 0) {
                            $('#notification-count').text(res.data.length).removeClass('d-none');
                            let html = '';
                            res.data.forEach(n => {
                                html += `
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span class="status-dot status-dot-animated bg-red d-block"></span></div>
                                            <div class="col text-truncate">
                                                <a href="users_admin.php" class="text-body d-block" onclick="markAsRead(${n.id})">${n.title}</a>
                                                <div class="d-block text-muted text-truncate mt-n1">
                                                    ${n.message}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#notification-list').html(html);
                        } else {
                            $('#notification-count').addClass('d-none');
                            $('#notification-list').html('<div class="list-group-item"><div class="text-muted text-center p-2">Nenhuma notificação nova</div></div>');
                        }
                    });
            }

            window.markAsRead = function(id) {
                fetch('api_notifications.php?action=mark_read&id=' + id);
            }

            // Carrega inicialmente e a cada 60 segundos
            loadNotifications();
            setInterval(loadNotifications, 60000);
            // Heartbeat para Agendamentos (Web Cron)
            <?php if (hasAccess('Super Administrador', $user_accesses) && getSysConfig('web_cron_heartbeat', '1') === '1'): ?>
            function runSchedulerHeartbeat() {
                const token = '<?= $_ENV['CRON_TOKEN'] ?? '' ?>';
                if (token) {
                    fetch('../api/scheduler_runner.php?token=' + token)
                        .then(res => res.text())
                        .then(data => {
                            console.log('Scheduler heartbeat processed');
                        })
                        .catch(err => console.error('Scheduler error:', err));
                }
            }
            // Executa ao carregar e a cada 5 minutos se a aba estiver aberta
            runSchedulerHeartbeat();
            setInterval(runSchedulerHeartbeat, 300000); 
            <?php endif; ?>
        });
    </script>
    
    <?= $extraJS ?? '' ?>
</body>
</html>
