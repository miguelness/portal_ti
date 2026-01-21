<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?= htmlspecialchars($pageTitle ?? 'Painel Administrativo') ?> - Grupo Barão</title>
    
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- CSS personalizado -->
    <?= $extraCSS ?? '' ?>
    
    <style>
        .navbar-brand img {
            height: 32px;
        }
        
        .avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        .modal-backdrop {
            z-index: 1040 !important;
        }
        
        .modal {
            z-index: 1050 !important;
        }
        
        .modal-dialog {
            z-index: 1060 !important;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="index.php">
                        <img src="../assets/logo/logo-branco.png" alt="Grupo Barão" class="navbar-brand-image">
                    </a>
                </h1>
                
                <div class="navbar-nav flex-row d-lg-none">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" style="background-image: url(../assets/img/avatars/default.png)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></div>
                                <div class="mt-1 small text-muted"><?= htmlspecialchars($_SESSION['cargo'] ?? 'Usuário') ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu">
                            <a href="logout.php" class="dropdown-item">Sair</a>
                        </div>
                    </div>
                </div>
                
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-home"></i>
                                </span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>
                        
                        <?php if (hasAccess('Gestão de Menu', $user_accesses)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="links.php">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-link"></i>
                                </span>
                                <span class="nav-link-title">Gestão de Menu</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasAccess(['Feeds TI', 'Feeds RH'], $user_accesses)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="posts.php">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-news"></i>
                                </span>
                                <span class="nav-link-title">Postagens</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasAccess(['Gestão de Colaboradores', 'Documentos RH'], $user_accesses)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-rh" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-users"></i>
                                </span>
                                <span class="nav-link-title">RH</span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <?php if (hasAccess('Gestão de Colaboradores', $user_accesses)): ?>
                                        <a class="dropdown-item" href="colaboradores.php">
                                            <i class="ti ti-users me-2"></i>
                                            Colaboradores
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasAccess('Documentos RH', $user_accesses)): ?>
                                        <a class="dropdown-item" href="documentos_rh.php">
                                            <i class="ti ti-file-text me-2"></i>
                                            Documentos RH
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasAccess(['Gestão de Usuários', 'Acessos'], $user_accesses)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-admin" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-settings"></i>
                                </span>
                                <span class="nav-link-title">Administração</span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <?php if (hasAccess('Gestão de Usuários', $user_accesses)): ?>
                                        <a class="dropdown-item" href="usuarios.php">
                                            <i class="ti ti-user me-2"></i>
                                            Usuários
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasAccess('Acessos', $user_accesses)): ?>
                                        <a class="dropdown-item" href="acessos.php">
                                            <i class="ti ti-lock me-2"></i>
                                            Acessos
                                        </a>
                                        <a class="dropdown-item" href="alertas.php">
                                            <i class="ti ti-alert-circle me-2"></i>
                                            Alertas
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </aside>
        
        <!-- Header -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" style="background-image: url(../assets/img/avatars/default.png)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></div>
                                <div class="mt-1 small text-muted"><?= htmlspecialchars($_SESSION['cargo'] ?? 'Usuário') ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="logout.php" class="dropdown-item">
                                <i class="ti ti-logout me-2"></i>
                                Sair
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page wrapper -->
        <div class="page-wrapper">
            <!-- Page header -->
            <?php if (isset($pageHeader)): ?>
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <?= $pageHeader ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <?= $extraJS ?? '' ?>
</body>
</html>