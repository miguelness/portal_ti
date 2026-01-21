<?php
/**
 * Página pública de colaboradores
 * Grupo Barão - Portal TI
 */

session_start();
require_once 'admin/config.php';

// Configurações da página
$page_title = "Colaboradores";
$page_description = "Lista de colaboradores do Grupo Barão";
$page_keywords = "colaboradores, contatos, grupo barão, portal";

// Buscar estatísticas básicas
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE status = 'ativo'");
    $total_colaboradores = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT empresa) as total FROM colaboradores WHERE empresa IS NOT NULL AND empresa != '' AND status = 'ativo'");
    $total_empresas = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT setor) as total FROM colaboradores WHERE setor IS NOT NULL AND setor != '' AND status = 'ativo'");
    $total_setores = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_colaboradores = 0;
    $total_empresas = 0;
    $total_setores = 0;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= htmlspecialchars($page_title) ?> - Portal TI</title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>"/>
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>"/>
    
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler-payments.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    
    <style>
        @media (prefers-reduced-motion: no-preference) {
            :root {
                scroll-behavior: smooth;
            }
        }
        
        .colaborador-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
            color: white;
            margin-right: 0.5rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .contact-link {
            color: inherit;
            text-decoration: none;
        }
        
        .contact-link:hover {
            color: var(--tblr-primary);
            text-decoration: underline;
        }
        
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.375rem;
            border: 1px solid var(--tblr-border-color);
            padding: 0.5rem 0.75rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border-radius: 0.375rem;
            border: 1px solid var(--tblr-border-color);
            padding: 0.375rem 0.75rem;
        }
        
        .btn-group-sm > .btn, .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .stats-card {
            transition: transform 0.2s ease-in-out;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href=".">
                        <img src="./static/logo.svg" width="110" height="32" alt="Portal TI" class="navbar-brand-image">
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" style="background-image: url(./static/avatars/000m.jpg)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div>Portal TI</div>
                                <div class="mt-1 small text-muted">Grupo Barão</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="." class="dropdown-item">Início</a>
                            <a href="admin/" class="dropdown-item">Painel Administrativo</a>
                        </div>
                    </div>
                </div>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href=".">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg>
                                    </span>
                                    <span class="nav-link-title">Início</span>
                                </a>
                            </li>
                            <li class="nav-item active">
                                <a class="nav-link" href="colaboradores.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                    </span>
                                    <span class="nav-link-title">Colaboradores</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="page-wrapper">
            <!-- Page header -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Portal TI</div>
                            <h2 class="page-title">Colaboradores</h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M5.5 5h13a1 1 0 0 1 .5 1.5l-5 5.5l0 7l-4 -3l0 -4l-5 -5.5a1 1 0 0 1 .5 -1.5"/></svg>
                                    Filtros Avançados
                                </a>
                                <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal" data-bs-target="#modal-filtros" aria-label="Filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M5.5 5h13a1 1 0 0 1 .5 1.5l-5 5.5l0 7l-4 -3l0 -4l-5 -5.5a1 1 0 0 1 .5 -1.5"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    <!-- Estatísticas -->
                    <div class="row row-deck row-cards mb-4">
                        <div class="col-sm-6 col-lg-4">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Total de Colaboradores</div>
                                        <div class="ms-auto lh-1">
                                            <div class="dropdown">
                                                <a class="dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Últimos 7 dias</a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item active" href="#">Últimos 7 dias</a>
                                                    <a class="dropdown-item" href="#">Últimos 30 dias</a>
                                                    <a class="dropdown-item" href="#">Últimos 3 meses</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="h1 mb-3"><?= number_format($total_colaboradores, 0, ',', '.') ?></div>
                                    <div class="d-flex mb-2">
                                        <div>Colaboradores ativos</div>
                                        <div class="ms-auto">
                                            <span class="text-green d-inline-flex align-items-center lh-1">
                                                100%
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8"/><path d="M14 7l7 0l0 7"/></svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Empresas</div>
                                    </div>
                                    <div class="h1 mb-3"><?= number_format($total_empresas, 0, ',', '.') ?></div>
                                    <div class="d-flex mb-2">
                                        <div>Empresas do grupo</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Setores</div>
                                    </div>
                                    <div class="h1 mb-3"><?= number_format($total_setores, 0, ',', '.') ?></div>
                                    <div class="d-flex mb-2">
                                        <div>Diferentes setores</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabela de colaboradores -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Lista de Colaboradores</h3>
                                    <div class="card-actions">
                                        <a href="#" class="btn btn-outline-primary btn-sm" id="btn-refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                                            Atualizar
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-vcenter" id="colaboradores-table">
                                            <thead>
                                                <tr>
                                                    <th>Colaborador</th>
                                                    <th>Ramal</th>
                                                    <th>Empresa</th>
                                                    <th>Setor</th>
                                                    <th>E-mail</th>
                                                    <th>Telefone</th>
                                                    <th>Teams</th>
                                                    <th>Status</th>
                                                    <th class="w-1">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dados carregados via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Filtros Avançados -->
    <div class="modal modal-blur fade" id="modal-filtros" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros Avançados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Empresa</label>
                                <select class="form-select" id="filtro-empresa">
                                    <option value="">Todas as empresas</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Setor</label>
                                <select class="form-select" id="filtro-setor">
                                    <option value="">Todos os setores</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="filtro-status">
                                    <option value="">Todos os status</option>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Busca Geral</label>
                                <input type="text" class="form-control" id="filtro-busca" placeholder="Nome, ramal ou e-mail...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</a>
                    <a href="#" class="btn btn-outline-secondary" id="btn-limpar-filtros">Limpar Filtros</a>
                    <a href="#" class="btn btn-primary" id="btn-aplicar-filtros">Aplicar Filtros</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes do Colaborador -->
    <div class="modal modal-blur fade" id="modal-detalhes" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Colaborador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalhes-content">
                    <!-- Conteúdo carregado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Libs JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js" defer></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Função para gerar avatar com iniciais
            function gerarAvatar(nome) {
                const iniciais = nome.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const cores = ['#1f2937', '#7c3aed', '#dc2626', '#ea580c', '#d97706', '#65a30d', '#059669', '#0891b2', '#2563eb', '#7c2d12'];
                const cor = cores[nome.length % cores.length];
                return `<span class="colaborador-avatar" style="background-color: ${cor}">${iniciais}</span>`;
            }
            
            // Função para formatar status
            function formatarStatus(status) {
                const badges = {
                    'ativo': '<span class="badge bg-success status-badge">Ativo</span>',
                    'inativo': '<span class="badge bg-secondary status-badge">Inativo</span>'
                };
                return badges[status] || '<span class="badge bg-secondary status-badge">Indefinido</span>';
            }
            
            // Função para formatar contatos
            function formatarContato(valor, tipo = 'email') {
                if (!valor) return '-';
                
                switch (tipo) {
                    case 'email':
                        return `<a href="mailto:${valor}" class="contact-link">${valor}</a>`;
                    case 'telefone':
                        return `<a href="tel:${valor}" class="contact-link">${valor}</a>`;
                    case 'teams':
                        // Se o valor for um ID de convite do Teams (sem protocolo)
                        if (valor && !valor.includes('://') && !valor.includes('http')) {
                            return `
                                <button type="button" class="btn btn-primary btn-sm js-open-teams" 
                                        data-native="msteams://teams.microsoft.com/l/invite/${valor}" 
                                        data-web="https://teams.live.com/l/invite/${valor}" 
                                        title="Clique para abrir no aplicativo Microsoft Teams">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                        <path d="M3 7h10v10h-10z"/>
                                        <path d="M6 10h4"/>
                                        <path d="M8 10v4"/>
                                        <path d="M15.5 10.5c0 -1 -2.5 -1.5 -2.5 -1.5s2.5 -.5 2.5 -1.5c0 -1 0 -1 0 -1s-2.5 -.5 -2.5 -1.5c0 -.5 .5 -1 1 -1s1 .5 1 1c0 .5 0 .5 0 .5"/>
                                        <path d="M17 8v8"/>
                                        <path d="M19 10v4"/>
                                    </svg>
                                    Teams
                                </button>
                            `;
                        }
                        // Fallback para valores antigos
                        return `<span class="text-muted">${valor}</span>`;
                    default:
                        return valor;
                }
            }
            
            // Inicializar DataTable
            const table = $('#colaboradores-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: 'api/colaboradores.php',
                    type: 'GET',
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: 'nome',
                        render: function(data, type, row) {
                            return gerarAvatar(data) + data;
                        }
                    },
                    { data: 'ramal' },
                    { data: 'empresa' },
                    { data: 'setor' },
                    {
                        data: 'email',
                        render: function(data, type, row) {
                            return formatarContato(data, 'email');
                        }
                    },
                    {
                        data: 'telefone',
                        render: function(data, type, row) {
                            return formatarContato(data, 'telefone');
                        }
                    },
                    {
                        data: 'teams',
                        render: function(data, type, row) {
                            return formatarContato(data, 'teams');
                        }
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {
                            return formatarStatus(data);
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-detalhes" data-id="${row.id}" title="Ver detalhes">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                                        </svg>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']],
                drawCallback: function(settings) {
                    // Reativar tooltips após redraw
                    $('[title]').tooltip();
                }
            });
            
            // Carregar dados para filtros
            function carregarFiltros() {
                // Carregar empresas
                $.get('api/auxiliares.php?tipo=empresas', function(data) {
                    const select = $('#filtro-empresa');
                    select.empty().append('<option value="">Todas as empresas</option>');
                    data.data.forEach(function(item) {
                        select.append(`<option value="${item.empresa}">${item.empresa} (${item.total})</option>`);
                    });
                });
                
                // Carregar setores
                $.get('api/auxiliares.php?tipo=setores', function(data) {
                    const select = $('#filtro-setor');
                    select.empty().append('<option value="">Todos os setores</option>');
                    data.data.forEach(function(item) {
                        select.append(`<option value="${item.setor}">${item.setor} (${item.total})</option>`);
                    });
                });
            }
            
            // Aplicar filtros
            $('#btn-aplicar-filtros').click(function() {
                const filtros = {
                    empresa: $('#filtro-empresa').val(),
                    setor: $('#filtro-setor').val(),
                    status: $('#filtro-status').val(),
                    search: $('#filtro-busca').val()
                };
                
                // Atualizar URL da API com filtros
                table.ajax.url('api/colaboradores.php?' + $.param(filtros)).load();
                $('#modal-filtros').modal('hide');
            });
            
            // Limpar filtros
            $('#btn-limpar-filtros').click(function() {
                $('#filtro-empresa, #filtro-setor, #filtro-status').val('');
                $('#filtro-busca').val('');
                table.ajax.url('api/colaboradores.php').load();
                $('#modal-filtros').modal('hide');
            });
            
            // Atualizar tabela
            $('#btn-refresh').click(function() {
                table.ajax.reload();
            });
            
            // Ver detalhes do colaborador
            $(document).on('click', '.btn-detalhes', function() {
                const id = $(this).data('id');
                
                $.get(`api/colaboradores.php?id=${id}`, function(data) {
                    let contatosHtml = '';
                    if (data.contatos && data.contatos.length > 0) {
                        contatosHtml = '<h6>Contatos Adicionais:</h6><ul class="list-unstyled">';
                        data.contatos.forEach(function(contato) {
                            const principal = contato.principal ? ' <span class="badge bg-primary">Principal</span>' : '';
                            contatosHtml += `<li><strong>${contato.tipo}:</strong> ${contato.valor}${principal}</li>`;
                        });
                        contatosHtml += '</ul>';
                    }
                    
                    const observacoes = data.observacoes ? `<h6>Observações:</h6><p>${data.observacoes}</p>` : '';
                    
                    $('#detalhes-content').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informações Básicas:</h6>
                                <p><strong>Nome:</strong> ${data.nome}</p>
                                <p><strong>Ramal:</strong> ${data.ramal}</p>
                                <p><strong>Empresa:</strong> ${data.empresa}</p>
                                <p><strong>Setor:</strong> ${data.setor}</p>
                                <p><strong>Status:</strong> ${formatarStatus(data.status)}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Contatos:</h6>
                                <p><strong>E-mail:</strong> ${formatarContato(data.email, 'email')}</p>
                                <p><strong>Telefone:</strong> ${data.telefone ? formatarContato(data.telefone, 'telefone') : '-'}</p>
                                <p><strong>Teams:</strong> ${data.teams ? formatarContato(data.teams, 'teams') : '-'}</p>
                                ${contatosHtml}
                            </div>
                        </div>
                        ${observacoes}
                        <hr>
                        <small class="text-muted">
                            Criado em: ${new Date(data.created_at).toLocaleString('pt-BR')}<br>
                            Última atualização: ${new Date(data.updated_at).toLocaleString('pt-BR')}
                        </small>
                    `);
                    
                    $('#modal-detalhes').modal('show');
                }).fail(function() {
                    alert('Erro ao carregar detalhes do colaborador.');
                });
            });
            
            // Carregar filtros na inicialização
            carregarFiltros();
            
            // Inicializar tooltips
            $('[title]').tooltip();
            
            // Handler para botões do Teams
            $(document).on('click', '.js-open-teams', function(e) {
                e.preventDefault();
                const nativeUrl = $(this).data('native');
                const webUrl = $(this).data('web');
                
                // Função para detectar se o app foi aberto
                let appOpened = false;
                
                // Tentar abrir no app nativo
                try {
                    // Método 1: Usar window.location para protocolo personalizado
                    window.location.href = nativeUrl;
                    appOpened = true;
                } catch (error) {
                    console.log('Método 1 falhou, tentando método 2');
                }
                
                // Método 2: Criar link temporário e clicar
                if (!appOpened) {
                    try {
                        const link = document.createElement('a');
                        link.href = nativeUrl;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        appOpened = true;
                    } catch (error) {
                        console.log('Método 2 falhou, tentando método 3');
                    }
                }
                
                // Método 3: Usar iframe como fallback
                if (!appOpened) {
                    try {
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = nativeUrl;
                        document.body.appendChild(iframe);
                        
                        setTimeout(() => {
                            try {
                                document.body.removeChild(iframe);
                            } catch (e) {}
                        }, 2000);
                    } catch (error) {
                        console.log('Método 3 falhou');
                    }
                }
                
                // Detectar se o usuário voltou à página (indicando que o app não abriu)
                let startTime = Date.now();
                let hasFocus = true;
                
                const checkFocus = () => {
                    if (document.hasFocus()) {
                        if (!hasFocus && (Date.now() - startTime) < 3000) {
                            // O usuário voltou rapidamente, provavelmente o app não abriu
                            if (confirm('Não foi possível abrir o aplicativo do Teams. Deseja abrir no navegador?')) {
                                window.open(webUrl, '_blank');
                            }
                        }
                        hasFocus = true;
                    } else {
                        hasFocus = false;
                    }
                };
                
                // Verificar foco após um pequeno delay
                setTimeout(() => {
                    window.addEventListener('focus', checkFocus);
                    setTimeout(() => {
                        window.removeEventListener('focus', checkFocus);
                    }, 5000);
                }, 500);
            });
        });
    </script>
</body>
</html>