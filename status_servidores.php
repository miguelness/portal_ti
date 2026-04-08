<?php
/**
 * status_servidores.php
 * Tela de visualização pública/colaborador do status dos sistemas e servidores.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'admin/config.php';

$stmt = $pdo->query("SELECT * FROM monitoramento_servidores WHERE verificar_estabilidade = 1 ORDER BY tipo DESC, nome ASC");
$servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por tipo
$grupos = [
    'Interno' => [],
    'Externo' => []
];
foreach ($servidores as $s) {
    if ($s['tipo'] === 'interno') $grupos['Interno'][] = $s;
    else $grupos['Externo'][] = $s;
}

// Buscar logs das últimas 24 horas para os gráficos
$logs24h = [];
try {
    $stmtLogs = $pdo->query("SELECT servidor_id, tempo_ms, verificado_em FROM monitoramento_logs WHERE verificado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY verificado_em ASC");
    while ($row = $stmtLogs->fetch(PDO::FETCH_ASSOC)) {
        $logs24h[$row['servidor_id']][] = [
            'x' => strtotime($row['verificado_em']) * 1000,
            'y' => (int)$row['tempo_ms']
        ];
    }
} catch (Exception $e) {}
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Status dos Sistemas | Grupo Barão</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <style>
    body { background: #f4f6fa; }
    [data-bs-theme="dark"] body { background: #0d0e12; }
    .status-card { border-radius: 12px; transition: transform 0.2s; }
    .status-card:hover { transform: translateY(-3px); }
    .pulse-online { color: #2fb344; animation: pulse-green 2s infinite; }
    .pulse-offline { color: #d63939; animation: pulse-red 2s infinite; }
    @keyframes pulse-green { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
    
    .chart-container {
        height: 60px;
        margin-top: 15px;
        margin-bottom: -10px;
        width: 100%;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
  <div class="page">
    <header class="navbar navbar-expand-md d-print-none">
      <div class="container-xl">
        <h1 class="navbar-brand navbar-brand-autodark d-none-block-768">
          <a href="index-tabler-modern.php">
            <img src="assets/logo/logo-cores.png" height="30" alt="Grupo Barão" class="navbar-brand-image">
          </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last">
          <a href="index-tabler-modern.php" class="btn btn-outline-primary btn-sm rounded-pill">
            <i class="ti ti-arrow-left me-1"></i> Voltar ao Portal
          </a>
        </div>
      </div>
    </header>

    <div class="page-wrapper">
      <div class="page-header d-print-none">
        <div class="container-xl">
          <div class="row g-2 align-items-center">
            <div class="col">
              <h2 class="page-title">
                <i class="ti ti-access-point me-2"></i> Status dos Sistemas e Servidores
              </h2>
              <p class="text-muted mt-1">Acompanhe em tempo real a disponibilidade de nossos serviços.</p>
            </div>
            <div class="col-auto">
               <span class="badge bg-blue-lt">Atualizado em: <?= date('d/m/Y H:i') ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="page-body">
        <div class="container-xl">
          
          <?php foreach ($grupos as $titulo => $lista): if (empty($lista)) continue; ?>
            <h3 class="mb-3 mt-4 text-uppercase tracking-wider text-muted"><?= $titulo ?></h3>
            <div class="row row-cards">
              <?php foreach ($lista as $s): ?>
                <div class="col-md-6 col-lg-3">
                  <div class="card status-card border-0 shadow-sm">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-3">
                        <span class="avatar bg-blue-lt me-3"><i class="ti ti-server"></i></span>
                        <div>
                          <div class="font-weight-bold"><?= htmlspecialchars($s['nome']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($s['tipo'] === 'interno' ? 'Rede Local' : 'Externo / Web') ?></div>
                        </div>
                      </div>
                      
                      <div class="mt-3 d-flex align-items-center justify-content-between">
                        <div>
                          <?php if ($s['status'] === 'online'): ?>
                            <span class="text-success d-flex align-items-center"><i class="ti ti-circle-filled pulse-online me-1"></i> Operacional</span>
                          <?php elseif ($s['status'] === 'lento'): ?>
                            <span class="text-warning d-flex align-items-center"><i class="ti ti-alert-triangle-filled me-1"></i> Instabilidade</span>
                          <?php elseif ($s['status'] === 'offline'): ?>
                            <span class="text-danger d-flex align-items-center"><i class="ti ti-circle-x-filled pulse-offline me-1"></i> Offline</span>
                          <?php else: ?>
                            <span class="text-muted d-flex align-items-center"><i class="ti ti-clock me-1"></i> Aguardando...</span>
                          <?php endif; ?>
                        </div>
                        <div class="text-muted small">
                          <?= $s['tempo_resposta_ms'] ?>ms
                        </div>
                      </div>

                      <!-- Gráfico de Oscilação (Últimas 24h) -->
                      <div class="chart-container" id="chart-<?= $s['id'] ?>"></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>

          <?php if (empty($servidores)): ?>
            <div class="empty">
              <div class="empty-icon"><i class="ti ti-mood-empty"></i></div>
              <p class="empty-title">Nenhum serviço monitorado</p>
              <p class="empty-subtitle text-muted">A TI ainda não cadastrou sistemas para monitoramento público.</p>
            </div>
          <?php endif; ?>

          <div class="alert alert-info mt-5 border-0 shadow-sm">
            <div class="d-flex">
              <div><i class="ti ti-info-circle me-2 icon"></i></div>
              <div>
                Nesta página, os status são atualizados automaticamente a cada poucos minutos. Caso perceba algum problema não listado aqui, entre em contato com o suporte através de um <strong>Abrir Chamado</strong>.
              </div>
            </div>
          </div>

        </div>
      </div>

      <footer class="footer footer-transparent d-print-none py-4">
        <div class="container-xl text-center text-muted">
           © <?= date('Y') ?> Grupo Barão - Monitoramento de Infraestrutura
        </div>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
        const logsData = <?= json_encode($logs24h) ?>;
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        
        Object.keys(logsData).forEach(sid => {
            const container = document.getElementById(`chart-${sid}`);
            if (!container) return;

            const seriesData = logsData[sid];
            
            const options = {
                chart: {
                    type: 'area',
                    height: 60,
                    sparkline: { enabled: true },
                    animations: { enabled: false }
                },
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                series: [{
                    name: 'Resposta (ms)',
                    data: seriesData
                }],
                colors: [isDark ? '#206bc4' : '#206bc4'],
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    x: {
                        show: true,
                        format: 'dd/MM HH:mm:ss'
                    },
                    y: {
                        title: { formatter: () => 'Resposta: ' },
                        formatter: (val) => val + ' ms'
                    },
                    marker: { show: true }
                },
                xaxis: {
                    type: 'datetime'
                }
            };

            const chart = new ApexCharts(container, options);
            chart.render();
        });
    });
  </script>
</body>
</html>
