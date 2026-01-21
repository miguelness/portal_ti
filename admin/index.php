<?php
require_once 'config.php';
require_once 'check_access.php';

$pageTitle = 'Dashboard';

ob_start(); ?>
<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    Visão Geral
                </div>
                <h2 class="page-title">
                    Dashboard Administrativo
                </h2>
            </div>
            <!-- Page title actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="../index.php" class="btn btn-primary d-none d-sm-inline-block">
                        <i class="ti ti-world me-2"></i>
                        Ver Portal
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
            <!-- Card de boas-vindas -->
            <div class="col-12">
                <div class="card card-md">
                    <div class="card-stamp card-stamp-lg">
                        <div class="card-stamp-icon bg-primary">
                            <i class="ti ti-ghost"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-10">
                                <h3 class="h1">Bem-vindo de volta, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?>!</h3>
                                <div class="markdown text-muted">
                                    Este é o seu painel de controle administrativo do Portal TI Grupo Barão. 
                                    Aqui você pode gerenciar conteúdos, usuários e monitorar as atividades do sistema.
                                </div>
                                <div class="mt-3">
                                    <a href="links_admin.php" class="btn btn-primary" rel="noopener">Gerenciar Menu</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cards de estatísticas rápidas -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <i class="ti ti-news"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM noticias WHERE status = 'ativo'");
                                        echo $stmt->fetchColumn();
                                    } catch (Exception $e) { echo "0"; }
                                    ?>
                                </div>
                                <div class="text-muted">
                                    Posts Ativos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar">
                                    <i class="ti ti-users"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                        echo $stmt->fetchColumn();
                                    } catch (Exception $e) { echo "0"; }
                                    ?>
                                </div>
                                <div class="text-muted">
                                    Usuários
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-warning text-white avatar">
                                    <i class="ti ti-alert-triangle"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM alertas WHERE status = 'ativo'");
                                        echo $stmt->fetchColumn();
                                    } catch (Exception $e) { echo "0"; }
                                    ?>
                                </div>
                                <div class="text-muted">
                                    Alertas Ativos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-danger text-white avatar">
                                    <i class="ti ti-message-report"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pendente'");
                                        echo $stmt->fetchColumn();
                                    } catch (Exception $e) { echo "0"; }
                                    ?>
                                </div>
                                <div class="text-muted">
                                    Reports Pendentes
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity placeholders or useful info -->
            <div class="col-md-6 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Ações Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="admin_postagem.php" class="btn btn-outline-primary btn-icon w-100 flex-column p-3 h-100">
                                    <i class="ti ti-news mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Ver Posts</span>
                                </a>
                            </div>
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="users_admin.php" class="btn btn-outline-info btn-icon w-100 flex-column p-3 h-100">
                                    <i class="ti ti-users mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Usuários</span>
                                </a>
                            </div>
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="alerts_admin.php" class="btn btn-outline-warning btn-icon w-100 flex-column p-3 h-100">
                                    <i class="ti ti-bell mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Alertas</span>
                                </a>
                            </div>
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="colaboradores.php" class="btn btn-outline-success btn-icon w-100 flex-column p-3 h-100">
                                    <i class="ti ti-id mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Colaboradores</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sistema</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted">Versão do Sistema</div>
                            <div class="h3 mb-0">v2.0.0</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted">Ambiente</div>
                            <div class="h3 mb-0">Produção / XAMPP</div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted">Status do Servidor</div>
                            <div class="h3 mb-0 text-success">Online</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean();

include 'admin_layout.php';
