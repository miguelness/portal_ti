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
                <h2 class="page-title">
                    Dashboard
                </h2>
                <div class="text-muted mt-1">Painel de controle administrativo</div>
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
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="ti ti-dashboard icon-lg text-primary"></i>
                        </div>
                        <h3 class="card-title">Bem-vindo ao Painel Administrativo</h3>
                        <p class="text-muted">
                            Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?>! 
                            Use o menu superior para navegar pelas funcionalidades disponíveis.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Cards de estatísticas rápidas -->
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Posts Ativos</div>
                        </div>
                        <div class="h1 mb-3">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM noticias WHERE status = 'ativo'");
                                echo $stmt->fetchColumn();
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </div>
                        <div class="d-flex mb-2">
                            <div class="flex-fill">
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Usuários Cadastrados</div>
                        </div>
                        <div class="h1 mb-3">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                echo $stmt->fetchColumn();
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </div>
                        <div class="d-flex mb-2">
                            <div class="flex-fill">
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: 60%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Alertas Ativos</div>
                        </div>
                        <div class="h1 mb-3">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM alertas WHERE status = 'ativo'");
                                echo $stmt->fetchColumn();
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </div>
                        <div class="d-flex mb-2">
                            <div class="flex-fill">
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-warning" style="width: 45%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Reports Pendentes</div>
                        </div>
                        <div class="h1 mb-3">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pendente'");
                                echo $stmt->fetchColumn();
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </div>
                        <div class="d-flex mb-2">
                            <div class="flex-fill">
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-danger" style="width: 30%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean();

include 'admin_layout.php';
