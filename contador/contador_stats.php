<?php
/**
 * Contador Stats - Visualização de estatísticas de cliques com Tabler
 * 
 * Esta página exibe estatísticas dos cliques registrados com interface moderna Tabler
 */

// Verificar autenticação (usando o mesmo sistema do portal)
// require_once '../admin/check_access.php';

// Incluir configuração do sistema
require_once '../config.php';

// Definir fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verificar se a tabela existe
$tableExists = $conn->query("SHOW TABLES LIKE 'contador_cliques'")->num_rows > 0;
if (!$tableExists) {
    die("A tabela contador_cliques ainda não existe. Nenhum clique foi registrado.");
}

// Processar filtros de data
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$selected_origin = isset($_GET['origin']) ? $_GET['origin'] : '';

// Consultas para estatísticas com filtros
$where_clause = "WHERE data_hora BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
if ($selected_origin) {
    $where_clause .= " AND origem = '" . $conn->real_escape_string($selected_origin) . "'";
}

$totalCliques = 0;
$clicksByDay = [];
$clicksByOrigin = [];
$clicksByHour = [];
$clicksByBrowser = [];
$latestClicks = [];
$uniqueIPs = 0;
$avgClicksPerDay = 0;

// Total de cliques
$result = $conn->query("SELECT COUNT(*) as total FROM contador_cliques $where_clause");
if ($result) {
    $totalCliques = $result->fetch_assoc()['total'];
}

// Total de IPs únicos
$result = $conn->query("SELECT COUNT(DISTINCT ip) as unique_ips FROM contador_cliques $where_clause");
if ($result) {
    $uniqueIPs = $result->fetch_assoc()['unique_ips'];
}

// Média de cliques por dia
$days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
$avgClicksPerDay = $days_diff > 0 ? round($totalCliques / $days_diff, 2) : 0;

// Cliques por dia
$sql = "SELECT DATE(data_hora) as dia, COUNT(*) as total 
        FROM contador_cliques 
        $where_clause 
        GROUP BY dia 
        ORDER BY dia DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clicksByDay[$row['dia']] = $row['total'];
    }
}

// Cliques por hora (últimas 24h)
$sql = "SELECT HOUR(data_hora) as hora, COUNT(*) as total 
        FROM contador_cliques 
        WHERE data_hora >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        GROUP BY hora 
        ORDER BY hora";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clicksByHour[$row['hora']] = $row['total'];
    }
}

// Cliques por origem
$sql = "SELECT origem, COUNT(*) as total 
        FROM contador_cliques 
        $where_clause 
        GROUP BY origem 
        ORDER BY total DESC LIMIT 10";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clicksByOrigin[$row['origem']] = $row['total'];
    }
}

// Navegadores mais usados
$sql = "SELECT 
            CASE 
                WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                WHEN user_agent LIKE '%Safari%' THEN 'Safari'
                WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                WHEN user_agent LIKE '%Opera%' THEN 'Opera'
                ELSE 'Outros'
            END as navegador,
            COUNT(*) as total
        FROM contador_cliques 
        $where_clause 
        GROUP BY navegador 
        ORDER BY total DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clicksByBrowser[$row['navegador']] = $row['total'];
    }
}

// Últimos 20 cliques
$sql = "SELECT * FROM contador_cliques $where_clause ORDER BY data_hora DESC LIMIT 20";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestClicks[] = $row;
    }
}

// Obter lista de origens únicas para o filtro
$origins = [];
$result = $conn->query("SELECT DISTINCT origem FROM contador_cliques ORDER BY origem");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $origins[] = $row['origem'];
    }
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y H:i:s', strtotime($data));
}

// Função para detectar navegador
function detectBrowser($userAgent) {
    if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
    if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
    if (strpos($userAgent, 'Safari') !== false) return 'Safari';
    if (strpos($userAgent, 'Edge') !== false) return 'Edge';
    if (strpos($userAgent, 'Opera') !== false) return 'Opera';
    return 'Outros';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Estatísticas de Cliques - Tabler</title>
    
    <!-- CSS Tabler -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Flatpickr para filtros de data -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <span class="nav-link-title fs-3">📊 Estatísticas de Cliques</span>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item d-none d-md-flex me-3">
                        <a href="contador_export_excel.php" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                <path d="M8 12h8" />
                                <path d="M8 16h8" />
                                <path d="M8 20h8" />
                            </svg>
                            Exportar Excel
                        </a>
                    </div>
                    <div class="nav-item me-3">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetModal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M4 7l16 0" />
                                <path d="M10 11l0 6" />
                                <path d="M14 11l0 6" />
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                            </svg>
                            Zerar Contador
                        </button>
                    </div>
                    <div class="nav-item">
                        <a href="../index.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-home" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                            </svg>
                            Portal
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Body -->
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Filtros</h3>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Data Inicial</label>
                                            <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Data Final</label>
                                            <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Origem</label>
                                            <select class="form-select" name="origin">
                                                <option value="">Todas as Origens</option>
                                                <?php foreach ($origins as $origin): ?>
                                                <option value="<?= htmlspecialchars($origin) ?>" <?= $selected_origin === $origin ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($origin) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                                    <path d="M21 21l-6 -6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cards de Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm stats-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-primary text-white avatar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mouse" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M6 3m0 3a3 3 0 0 1 3 -3h6a3 3 0 0 1 3 3v6a3 3 0 0 1 -3 3h-6a3 3 0 0 1 -3 -3z" />
                                                    <path d="M12 7v-2" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                <?= number_format($totalCliques, 0, ',', '.') ?>
                                                <span class="float-end font-weight-medium text-green">+<?= $avgClicksPerDay ?>/dia</span>
                                            </div>
                                            <div class="text-secondary">Total de Cliques</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm stats-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-green text-white avatar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                <?= number_format($uniqueIPs, 0, ',', '.') ?>
                                            </div>
                                            <div class="text-secondary">IPs Únicos</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm stats-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-orange text-white avatar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-world" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                    <path d="M3.6 9h16.8" />
                                                    <path d="M3.6 15h16.8" />
                                                    <path d="M11.5 3a17 17 0 0 0 0 18" />
                                                    <path d="M12.5 3a17 17 0 0 1 0 18" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                <?= count($clicksByOrigin) ?>
                                            </div>
                                            <div class="text-secondary">Origens</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm stats-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-blue text-white avatar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M12 7v5l3 3" />
                                                    <path d="M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0 -18z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                <?= !empty($latestClicks) ? date('d/m H:i', strtotime($latestClicks[0]['data_hora'])) : 'N/A' ?>
                                            </div>
                                            <div class="text-secondary">Último Clique</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Cliques por Dia</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="clicksByDayChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Cliques por Hora (24h)</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="clicksByHourChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Cliques por Origem</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="clicksByOriginChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Navegadores</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="clicksByBrowserChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Últimos Cliques -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Últimos Cliques</h3>
                                </div>
                                <div class="card-body border-bottom py-3">
                                    <div class="d-flex">
                                        <div class="text-secondary">
                                            Mostrando <span class="text-primary"><?= min(20, count($latestClicks)) ?></span> de <span class="text-primary"><?= $totalCliques ?></span> registros
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter text-nowrap datatable">
                                        <thead>
                                            <tr>
                                                <th>Data/Hora</th>
                                                <th>IP</th>
                                                <th>Origem</th>
                                                <th>Navegador</th>
                                                <th>Referência</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestClicks as $click): ?>
                                            <tr>
                                                <td><?= formatarData($click['data_hora']) ?></td>
                                                <td><code><?= htmlspecialchars($click['ip']) ?></code></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($click['origem']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= detectBrowser($click['user_agent']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-truncate d-block" style="max-width: 200px;" title="<?= htmlspecialchars($click['referer']) ?>">
                                                        <?= htmlspecialchars(strlen($click['referer']) > 50 ? substr($click['referer'], 0, 50) . '...' : $click['referer']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
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

    <!-- Modal de Confirmação para Zerar Contador -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resetModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-5">Tem certeza que deseja zerar o contador de cliques?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação excluirá permanentemente todos os registros de cliques e não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="contador_reset.php" method="post">
                        <input type="hidden" name="confirm" value="yes">
                        <button type="submit" class="btn btn-danger">Sim, Zerar Contador</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts Tabler -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <!-- Scripts para gráficos -->
    <script>
        // Configuração global dos gráficos
        Chart.defaults.font.family = 'inherit';
        Chart.defaults.color = '#6c7a91';

        // Cores do Tabler
        const tablerColors = {
            primary: '#206bc4',
            green: '#2fb344',
            orange: '#f76707',
            blue: '#206bc4',
            red: '#d63939',
            purple: '#6f42c1',
            teal: '#0ca678'
        };

        // Gráfico de cliques por dia
        const clicksByDayCtx = document.getElementById('clicksByDayChart').getContext('2d');
        new Chart(clicksByDayCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach ($clicksByDay as $day => $count) {
                        $labels[] = "'" . date('d/m', strtotime($day)) . "'";
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: 'Cliques',
                    data: [<?php echo implode(',', $clicksByDay); ?>],
                    borderColor: tablerColors.primary,
                    backgroundColor: tablerColors.primary + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Gráfico de cliques por hora
        const clicksByHourCtx = document.getElementById('clicksByHourChart').getContext('2d');
        new Chart(clicksByHourCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $hourLabels = [];
                    for ($i = 0; $i < 24; $i++) {
                        $hourLabels[] = "'" . $i . "h'";
                    }
                    echo implode(',', $hourLabels);
                ?>],
                datasets: [{
                    label: 'Cliques',
                    data: [<?php 
                        $hourData = [];
                        for ($i = 0; $i < 24; $i++) {
                            $hourData[] = isset($clicksByHour[$i]) ? $clicksByHour[$i] : 0;
                        }
                        echo implode(',', $hourData);
                    ?>],
                    backgroundColor: tablerColors.green + '80',
                    borderColor: tablerColors.green,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Gráfico de pizza - cliques por origem
        const clicksByOriginCtx = document.getElementById('clicksByOriginChart').getContext('2d');
        new Chart(clicksByOriginCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    $originLabels = [];
                    foreach ($clicksByOrigin as $origin => $count) {
                        $originLabels[] = "'" . htmlspecialchars($origin) . "'";
                    }
                    echo implode(',', $originLabels);
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', $clicksByOrigin); ?>],
                    backgroundColor: [
                        tablerColors.primary,
                        tablerColors.green,
                        tablerColors.orange,
                        tablerColors.blue,
                        tablerColors.red,
                        tablerColors.purple,
                        tablerColors.teal
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de barras - navegadores
        const clicksByBrowserCtx = document.getElementById('clicksByBrowserChart').getContext('2d');
        new Chart(clicksByBrowserCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $browserLabels = [];
                    foreach ($clicksByBrowser as $browser => $count) {
                        $browserLabels[] = "'" . $browser . "'";
                    }
                    echo implode(',', $browserLabels);
                ?>],
                datasets: [{
                    label: 'Cliques',
                    data: [<?php echo implode(',', $clicksByBrowser); ?>],
                    backgroundColor: [
                        tablerColors.primary + '80',
                        tablerColors.green + '80',
                        tablerColors.orange + '80',
                        tablerColors.blue + '80',
                        tablerColors.red + '80'
                    ],
                    borderColor: [
                        tablerColors.primary,
                        tablerColors.green,
                        tablerColors.orange,
                        tablerColors.blue,
                        tablerColors.red
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
    
    <!-- Exibir mensagens de alerta -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar parâmetros na URL
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success') && urlParams.get('success') === 'reset_complete') {
                // Usar alert do Tabler
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible position-fixed top-0 end-0 m-3';
                alertDiv.style.zIndex = '9999';
                alertDiv.innerHTML = `
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                        </div>
                        <div class="ms-2">
                            <h4 class="alert-title">Sucesso!</h4>
                            <div class="text-secondary">Contador zerado com sucesso!</div>
                        </div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                `;
                document.body.appendChild(alertDiv);
                
                // Remover o parâmetro da URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            if (urlParams.has('error')) {
                const error = urlParams.get('error');
                let message = '';
                
                if (error === 'not_confirmed') {
                    message = 'Operação cancelada: Confirmação necessária.';
                } else if (error === 'no_table') {
                    message = 'Erro: Tabela de contador não encontrada.';
                } else if (error === 'reset_failed') {
                    message = 'Erro ao zerar contador: ' + urlParams.get('message');
                }
                
                if (message) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible position-fixed top-0 end-0 m-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 9v4" />
                                    <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                    <path d="M12 16h.01" />
                                </svg>
                            </div>
                            <div class="ms-2">
                                <h4 class="alert-title">Erro!</h4>
                                <div class="text-secondary">${message}</div>
                            </div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Remover o parâmetro da URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    </script>
</body>
</html>