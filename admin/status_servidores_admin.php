<?php
/**
 * admin/status_servidores_admin.php
 * Tela de visualização administrativa completa do status dos sistemas e servidores.
 * Exibe tanto servidores públicos quanto internos.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$requiredAccess = 'Super Administrador';
require_once 'check_access.php';
require_once 'config.php';

// Query sem o filtro is_public = 1
$stmt = $pdo->query("SELECT * FROM monitoramento_servidores WHERE verificar_estabilidade = 1 ORDER BY tipo DESC, nome ASC");
$servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por tipo
$grupos = [
    'Sistemas e Links Próprios' => [],
    'Serviços de Terceiros e Parceiros' => []
];
foreach ($servidores as $s) {
    if ($s['tipo'] === 'interno') $grupos['Sistemas e Links Próprios'][] = $s;
    else $grupos['Serviços de Terceiros e Parceiros'][] = $s;
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
<html lang="pt-br">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Painel de Status (TI) | Grupo Barão</title>
  
  <script>
    (function() {
      const themeStorageKey = 'portalTheme';
      const isDark = localStorage.getItem(themeStorageKey) === 'dark';
      if (isDark) {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
      } else {
        document.documentElement.setAttribute('data-bs-theme', 'light');
      }
    })();
  </script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap');

    :root {
        --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }
    
    body { 
        background-color: #f8fafc; 
        font-family: 'Inter', sans-serif;
        color: #0f172a;
        background-image: radial-gradient(at 0% 0%, hsla(217,100%,94%,0.8) 0, transparent 50%), 
                          radial-gradient(at 50% 0%, hsla(213,100%,96%,0.8) 0, transparent 50%), 
                          radial-gradient(at 100% 0%, hsla(210,100%,95%,0.8) 0, transparent 50%);
        background-attachment: fixed;
    }
    [data-bs-theme="dark"] body { 
        background-color: #0b0f19; 
        color: #f8fafc;
        background-image: radial-gradient(at 0% 0%, hsla(217,50%,15%,0.8) 0, transparent 50%), 
                          radial-gradient(at 100% 0%, hsla(210,40%,12%,0.8) 0, transparent 50%);
    }

    h1, h2, h3, h4, h5, h6, .page-title {
        font-family: 'Outfit', sans-serif;
        letter-spacing: -0.02em;
    }

    .status-card { 
        border-radius: 16px; 
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    [data-bs-theme="dark"] .status-card {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4);
    }
    .status-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    }

    .pulse-online { color: #10b981; filter: drop-shadow(0 0 4px rgba(16, 185, 129, 0.5)); animation: pulse-green 2.5s infinite; }
    .pulse-offline { color: #ef4444; filter: drop-shadow(0 0 4px rgba(239, 68, 68, 0.5)); animation: pulse-red 2.5s infinite; }
    
    @keyframes pulse-green { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
    @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
    
    .avatar {
        border-radius: 12px;
        background: rgba(32,107,196,0.1) !important;
        color: #206bc4 !important;
    }
    [data-bs-theme="dark"] .avatar {
        background: rgba(32,107,196,0.2) !important;
        color: #74a8f6 !important;
    }

    .chart-container {
        height: 60px;
        margin-top: 15px;
        margin-bottom: -10px;
        width: 100%;
        position: relative;
        z-index: 1;
    }
    
    .navbar { background: transparent !important; border-bottom: none !important; }
    .page-header { margin-bottom: 2rem; position: relative; z-index: 10; }

    #digital-clock {
        font-family: 'Outfit', sans-serif;
        font-size: 1.25rem;
        font-weight: 600;
        color: #206bc4;
        background: rgba(32,107,196,0.1);
        padding: 0.4rem 1rem;
        border-radius: 50px;
        margin-right: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    [data-bs-theme="dark"] #digital-clock {
        color: #74a8f6;
        background: rgba(116,168,246,0.1);
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
  <div class="page">
    <header class="navbar navbar-expand-md d-print-none">
      <div class="container-xl">
        <h1 class="navbar-brand navbar-brand-autodark d-none-block-768">
          <a href="index.php">
            <img src="../assets/logo/logo-cores.png" height="30" alt="Grupo Barão" class="navbar-brand-image">
          </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last align-items-center">
          <div id="digital-clock" class="d-none d-sm-flex">
             <i class="ti ti-clock"></i> <span id="clock-text">--:--:--</span>
          </div>
          <a href="#" class="nav-link px-0 me-3" id="btn-fullscreen" title="Modo Monitoramento (Tela Cheia)" data-bs-toggle="tooltip">
            <i class="ti ti-maximize fs-2"></i>
          </a>
          <a href="#" class="nav-link px-0 me-3 d-none" id="btn-theme-dark" title="Habilitar modo escuro" data-bs-toggle="tooltip">
            <i class="ti ti-moon fs-2"></i>
          </a>
          <a href="#" class="nav-link px-0 me-3 d-none" id="btn-theme-light" title="Habilitar modo claro" data-bs-toggle="tooltip">
            <i class="ti ti-sun fs-2"></i>
          </a>
          <a href="index.php" class="btn btn-outline-primary shadow-sm rounded-pill font-weight-medium btn-back">
            <i class="ti ti-arrow-left me-1"></i> Dashboard Admin
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
                <i class="ti ti-terminal-2 me-2"></i> Monitoramento Completo (TI)
              </h2>
              <p class="text-muted mt-1">Visão completa de todos os servidores e serviços monitorados.</p>
            </div>
            <div class="col-auto">
               <span class="badge bg-blue-lt" id="last-update-time">Atualizado em: <?= date('d/m/Y H:i') ?></span>
               <a href="servidores_admin.php" class="btn btn-sm btn-primary ms-2"><i class="ti ti-settings me-1"></i> Gerenciar</a>
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
                      <div class="d-flex align-items-center mb-1">
                        <span class="avatar bg-blue-lt me-3"><i class="ti ti-server"></i></span>
                        <div class="text-truncate">
                          <div class="font-weight-bold text-truncate" title="<?= htmlspecialchars($s['nome']) ?>"><?= htmlspecialchars($s['nome']) ?></div>
                          <div class="text-muted small">
                             <?= $s['is_public'] ? '<span class="badge bg-green-lt">Compartilhado</span>' : '<span class="badge bg-blue-lt">Uso Interno TI</span>' ?>
                          </div>
                        </div>
                      </div>
                      <div class="text-muted text-truncate mb-3 small" style="margin-left: 48px;">
                         <code><?= htmlspecialchars($s['ip_ou_url']) ?></code>
                      </div>
                      
                      <div class="mt-3 d-flex align-items-center justify-content-between">
                        <div id="status-container-<?= $s['id'] ?>">
                          <?php if ($s['status'] === 'online'): ?>
                            <span class="text-success d-flex align-items-center"><i class="ti ti-circle-filled pulse-online me-1"></i> Online</span>
                          <?php elseif ($s['status'] === 'lento'): ?>
                            <span class="text-warning d-flex align-items-center"><i class="ti ti-alert-triangle-filled me-1"></i> Instável</span>
                          <?php elseif ($s['status'] === 'offline'): ?>
                            <span class="text-danger d-flex align-items-center"><i class="ti ti-circle-x-filled pulse-offline me-1"></i> Offline</span>
                          <?php else: ?>
                            <span class="text-muted d-flex align-items-center"><i class="ti ti-clock me-1"></i>...</span>
                          <?php endif; ?>
                        </div>
                        <div class="text-muted small font-weight-bold" id="ms-value-<?= $s['id'] ?>">
                          <?= $s['tempo_resposta_ms'] ?>ms
                        </div>
                      </div>

                      <div class="chart-container" id="chart-<?= $s['id'] ?>"></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>

        </div>
      </div>

      <footer class="footer footer-transparent d-print-none py-4">
        <div class="container-xl text-center text-muted">
           © <?= date('Y') ?> Grupo Barão - Console de Administração TI
        </div>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
        const themeStorageKey = 'portalTheme';
        function applyTheme(isDark) {
            const btnDark = document.getElementById('btn-theme-dark');
            const btnLight = document.getElementById('btn-theme-light');
            if (isDark) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                if(btnDark) btnDark.classList.add('d-none');
                if(btnLight) btnLight.classList.remove('d-none');
            } else {
                document.documentElement.setAttribute('data-bs-theme', 'light');
                if(btnLight) btnLight.classList.add('d-none');
                if(btnDark) btnDark.classList.remove('d-none');
            }
        }
        applyTheme(localStorage.getItem(themeStorageKey) === 'dark');

        document.getElementById('btn-theme-dark').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.setItem(themeStorageKey, 'dark');
            applyTheme(true);
            setTimeout(() => window.location.reload(), 50);
        });
        document.getElementById('btn-theme-light').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.setItem(themeStorageKey, 'light');
            applyTheme(false);
            setTimeout(() => window.location.reload(), 50);
        });

        document.getElementById('btn-fullscreen').addEventListener('click', (e) => {
            e.preventDefault();
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });

        function updateClock() {
            const now = new Date();
            const clockEl = document.getElementById('clock-text');
            if (clockEl) clockEl.innerText = now.toLocaleTimeString('pt-BR', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        const logsData = <?= json_encode($logs24h) ?>;
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const charts = {};
        
        Object.keys(logsData).forEach(sid => {
            const container = document.getElementById(`chart-${sid}`);
            if (!container) return;
            const options = {
                chart: { type: 'area', height: 60, sparkline: { enabled: true } },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.1 } },
                series: [{ name: 'Resposta (ms)', data: logsData[sid] }],
                colors: ['#206bc4'],
                tooltip: { 
                    theme: isDark ? 'dark' : 'light', 
                    x: { 
                        show: true,
                        format: 'dd/MM HH:mm' 
                    } 
                },
                xaxis: { 
                    type: 'datetime',
                    labels: {
                        datetimeUTC: false,
                        format: 'dd/MM HH:mm'
                    }
                }
            };
            charts[sid] = new ApexCharts(container, options);
            charts[sid].render();
        });

        function refreshStatus() {
            fetch('../api/get_server_status.php')
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const { servidores, logs, atualizado_em } = res.data;
                        document.getElementById('last-update-time').innerText = 'Atualizado em: ' + atualizado_em;
                        servidores.forEach(s => {
                            const container = document.getElementById(`status-container-${s.id}`);
                            const msVal = document.getElementById(`ms-value-${s.id}`);
                            if (container) {
                                if (s.status === 'online') container.innerHTML = `<span class="text-success d-flex align-items-center"><i class="ti ti-circle-filled pulse-online me-1"></i> Online</span>`;
                                else if (s.status === 'lento') container.innerHTML = `<span class="text-warning d-flex align-items-center"><i class="ti ti-alert-triangle-filled me-1"></i> Instável</span>`;
                                else if (s.status === 'offline') container.innerHTML = `<span class="text-danger d-flex align-items-center"><i class="ti ti-circle-x-filled pulse-offline me-1"></i> Offline</span>`;
                            }
                            if (msVal) msVal.innerText = s.tempo_resposta_ms + 'ms';
                            if (logs[s.id] && charts[s.id]) charts[s.id].updateSeries([{ data: logs[s.id] }]);
                        });
                    }
                })
                .catch(err => console.error(err));
        }
        setInterval(refreshStatus, 30000);
    });
  </script>
</body>
</html>
