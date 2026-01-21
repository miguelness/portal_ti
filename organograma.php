<?php
/**
 * Organograma Interativo - Página Pública (estável)
 */
$pageTitle = 'Organograma';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - Portal</title>

  <!-- Tabler CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
  <!-- Ícones Tabler (webfont correta) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

  <!-- D3.js -->
  <script src="https://d3js.org/d3.v7.min.js"></script>

  <style>
    .org-chart-container{
      width:100%; height:80vh; overflow:auto; border:1px solid #e6e7e9;
      border-radius:8px; background:#f8f9fa; position:relative; cursor:grab;
      min-height:400px; box-sizing:border-box;
    }
    .org-chart-container svg{ max-width:100%; height:auto; }
    #orgChart:active{ cursor:grabbing; }

    .node{ cursor:pointer; transform-box:fill-box; transform-origin:50% 50%; }
    /* Removemos o scale do hover para não deslocar o mouse e evitar flicker */
    .node:hover{ /* sem transform: scale */ }
    .node rect{
      fill:#fff; stroke:#206bc4; stroke-width:2; rx:8;
      filter:drop-shadow(0 2px 4px rgba(0,0,0,.1));
      transition: filter .2s ease, stroke-width .2s ease;
    }
    .node:hover rect{ filter:brightness(1.06); stroke-width:2.5px; }

    .node.level-1 rect{ fill:#206bc4; stroke:#1a5490; }
    .node.level-1 text{ fill:#fff; }
    .node.level-2 rect{ fill:#4dabf7; stroke:#339af0; }
    .node.level-3 rect{ fill:#74c0fc; stroke:#4dabf7; }

    .node text{
      font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      font-size:12px; text-anchor:middle; pointer-events:none;
    }
    .node .name{ font-weight:600; font-size:13px; }
    .node .title{ font-size:11px; fill:#666; }
    .node.level-1 .title{ fill:rgba(255,255,255,.85); }

    .link{ fill:none; stroke:#206bc4; stroke-width:2; stroke-opacity:.6; }

    /* Tooltip estável (fixed) */
    .tooltip{
      position:fixed; background:#fff; border:1px solid #e6e7e9; border-radius:8px;
      padding:12px; box-shadow:0 4px 12px rgba(0,0,0,.15); pointer-events:none;
      z-index:1000; max-width:300px; opacity:0; transition:opacity .2s ease;
      transform:translateZ(0);
    }
    .tooltip.show{ opacity:1; }

    .search-container{
      position:sticky; top:0; z-index:100; background:#fff; padding:1rem; border-bottom:1px solid #e6e7e9;
    }
    .zoom-controls{ position:absolute; top:20px; right:20px; z-index:200; }
    .legend{
      position:absolute; bottom:20px; left:20px; background:#fff; padding:12px; border-radius:8px;
      border:1px solid #e6e7e9; box-shadow:0 2px 8px rgba(0,0,0,.1);
    }
    .legend-item{ display:flex; align-items:center; margin-bottom:8px; }
    .legend-color{ width:16px; height:16px; border-radius:4px; margin-right:8px; }

    /* Kill-switch do overlay */
    #loadingSpinner.hidden{ display:none!important; visibility:hidden!important; opacity:0!important; pointer-events:none!important; }

    @media (max-width:768px){
      .org-chart-container{ height:60vh; }
      .search-container{ padding:.5rem; }
      .legend{ position:relative; margin-top:1rem; font-size:.875rem; }
    }
    @media (max-width:576px){
      .org-chart-container{ height:50vh; }
      .search-container{ padding:.25rem; }
      .legend{ font-size:.75rem; padding:8px; }
      .legend-item{ margin-bottom:4px; }
      .legend-color{ width:12px; height:12px; margin-right:6px; }
    }
  </style>
</head>
<body>
<div class="page">
  <header class="navbar navbar-expand-md navbar-light d-print-none">
    <div class="container-xl">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
        <a href="index.php">Portal</a>
      </h1>
      <div class="navbar-nav flex-row order-md-last">
        <a href="index.php" class="btn btn-outline-primary"><i class="ti ti-home me-1"></i> Início</a>
      </div>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header d-print-none">
      <div class="container-xl">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="page-pretitle">ESTRUTURA ORGANIZACIONAL</div>
            <h2 class="page-title"><i class="ti ti-hierarchy me-2"></i> Organograma Interativo</h2>
          </div>
        </div>
      </div>
    </div>

    <div class="page-body">
      <div class="container-xl">
        <!-- Controles -->
        <div class="search-container">
          <div class="row g-2">
            <div class="col-md-4">
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Buscar colaborador...">
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" id="departmentFilter">
                <option value="">Todos os departamentos</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" id="levelFilter">
                <option value="">Todos os níveis</option>
                <option value="1">Nível 1 - Diretoria</option>
                <option value="2">Nível 2 - Gerência</option>
                <option value="3">Nível 3 - Coordenação</option>
                <option value="4">Nível 4+ - Operacional</option>
              </select>
            </div>
            <div class="col-md-3">
              <div class="btn-group w-100">
                <button type="button" class="btn btn-outline-primary" id="expandAll"><i class="ti ti-arrows-maximize"></i> Expandir</button>
                <button type="button" class="btn btn-outline-secondary" id="collapseAll"><i class="ti ti-arrows-minimize"></i> Recolher</button>
                <button type="button" class="btn btn-outline-info" id="resetView"><i class="ti ti-refresh"></i> Reset</button>
              </div>
              <small class="text-muted d-block mt-2">
                <i class="ti ti-info-circle me-1"></i> Use Ctrl+scroll para zoom, clique e arraste para navegar
              </small>
            </div>
          </div>
        </div>

        <!-- Organograma -->
        <div class="card">
          <div class="card-body p-0">
            <div class="org-chart-container" id="orgChart">
              <!-- Zoom -->
              <div class="zoom-controls">
                <div class="btn-group-vertical">
                  <button type="button" class="btn btn-sm btn-outline-primary" id="zoomIn"><i class="ti ti-plus"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-primary" id="zoomOut"><i class="ti ti-minus"></i></button>
                </div>
              </div>
              <!-- Legenda -->
              <div class="legend">
                <h6 class="mb-2">Legenda</h6>
                <div class="legend-item"><div class="legend-color" style="background:#206bc4;"></div><small>Diretoria</small></div>
                <div class="legend-item"><div class="legend-color" style="background:#4dabf7;"></div><small>Gerência</small></div>
                <div class="legend-item"><div class="legend-color" style="background:#74c0fc;"></div><small>Coordenação</small></div>
                <div class="legend-item"><div class="legend-color" style="background:#fff;border:1px solid #206bc4;"></div><small>Operacional</small></div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Tooltip -->
<div class="tooltip" id="tooltip" style="display:none;"></div>

<!-- Tabler JS -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>

<!-- Loading (começa OCULTO) -->
<div id="loadingSpinner"
     class="d-flex justify-content-center align-items-center position-fixed"
     style="top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.9);z-index:9999;display:none;">
  <div class="text-center">
    <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;">
      <span class="visually-hidden">Carregando...</span>
    </div>
    <div class="h5 text-muted">Carregando organograma...</div>
    <div class="text-muted">Por favor, aguarde</div>
  </div>
</div>

<!-- Script do Organograma -->
<script src="js/organograma.js"></script>

<!-- Inicialização com killswitch do overlay -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const spinner = document.getElementById('loadingSpinner');
  const orgContainer = document.getElementById('orgChart');

  const showLoading = () => { if (spinner) spinner.style.display = 'flex'; };
  const reallyHideSpinner = () => {
    if (!spinner) return;
    spinner.style.display = 'none';
    spinner.classList.add('hidden');
    spinner.setAttribute('aria-hidden', 'true');
    spinner.setAttribute('inert', '');
    document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove());
    setTimeout(() => { try{ spinner.remove(); }catch(e){} }, 50);
  };

  window.addEventListener('error',  reallyHideSpinner);
  window.addEventListener('unhandledrejection', reallyHideSpinner);

  function showError(message) {
    if (orgContainer) {
      orgContainer.innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100">
          <div class="text-center">
            <div class="alert alert-danger">
              <i class="ti ti-alert-circle me-2"></i>
              <strong>Erro ao carregar o organograma</strong><br>
              ${message || 'Erro inesperado.'}
            </div>
            <button class="btn btn-primary" onclick="location.reload()">Tentar novamente</button>
          </div>
        </div>`;
    }
  }

  async function fetchWithFallback() {
    const urls = ['api/organograma.php', '/api/organograma.php'];
    let lastErr;
    for (const url of urls) {
      try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
      } catch (e) { lastErr = e; }
    }
    throw lastErr || new Error('Falha ao consultar API');
  }

  showLoading();

  fetchWithFallback()
    .then(data => {
      if (!(data && data.success && data.data)) throw new Error('Resposta inválida da API.');

      const departmentFilter = document.getElementById('departmentFilter');
      if (departmentFilter && Array.isArray(data.departamentos)) {
        data.departamentos.forEach(dept => {
          const opt = document.createElement('option');
          opt.value = dept; opt.textContent = dept;
          departmentFilter.appendChild(opt);
        });
      }

      window.orgChart = new OrganogramChart('#orgChart', data.data);
      if (typeof setupOrganogramControls === 'function') {
        setupOrganogramControls(window.orgChart);
      }
    })
    .catch(err => { console.error('[Organograma] ERRO:', err); showError(err.message); })
    .finally(() => { reallyHideSpinner(); });

  setTimeout(reallyHideSpinner, 4000); // failsafe extra
});
</script>
</body>
</html>
