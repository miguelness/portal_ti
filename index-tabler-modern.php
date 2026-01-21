<?php
// Portal standalone alternativo (Tabler) – sem chrome administrativo
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Portal Grupo Barão</title>
  <!-- Tabler core e ícones -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <style>
    body { background: radial-gradient(1200px 400px at 50% -40%, #3758f9 0%, #7c3aed 50%, #8b5cf6 70%, transparent 72%) fixed; }
    .portal-hero { position: sticky; top:0; z-index: 10; backdrop-filter: saturate(120%) blur(6px); background: linear-gradient(180deg, rgba(255,255,255,.18), rgba(255,255,255,.08)); border-bottom: 1px solid rgba(0,0,0,.06); }
    [data-bs-theme="dark"] .portal-hero { background: linear-gradient(180deg, rgba(0,0,0,.25), rgba(0,0,0,.15)); border-bottom-color: rgba(255,255,255,.08); }
    .hero-logo { height: 36px; filter: drop-shadow(0 1px 0 rgba(0,0,0,.08)); }
    .theme-toggle { position: absolute; right: 16px; top: 12px; }

    .portal-wrap { padding: 20px; }
    .portal-card { border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.06); padding: 18px; min-height: 120px; transition: transform .18s ease, box-shadow .18s ease; }
    .portal-card:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,.12); }
    .portal-card:focus { outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.25), 0 12px 30px rgba(0,0,0,.12); }
    .portal-card .icon { width: 32px; height: 32px; display: inline-flex; align-items:center; justify-content:center; border-radius: 9px; margin-bottom: 8px; }
    .portal-card .title { font-weight: 700; }
    .portal-card .subtitle { color: var(--tblr-muted); font-size: .82rem; }
    .accent-blue { border-top: 4px solid #60a5fa; }
    .accent-purple { border-top: 4px solid #a78bfa; }
    .accent-cyan { border-top: 4px solid #22d3ee; }
    .accent-green { border-top: 4px solid #34d399; }
    .accent-yellow { border-top: 4px solid #f59e0b; }
    .accent-pink { border-top: 4px solid #ec4899; }

    .section-title { font-weight: 700; text-align: center; margin: 12px 0 20px; }
    .section-container { background: rgba(255,255,255,.55); border-radius: 14px; box-shadow: 0 8px 22px rgba(0,0,0,.08); border: 1px solid rgba(0,0,0,.06); padding: 18px; }
    [data-bs-theme="dark"] .section-container { background: rgba(18,18,24,.55); border-color: rgba(255,255,255,.08); }

    .section-badge { background: rgba(99,102,241,.18); color:#4f46e5; border: 1px solid rgba(79,70,229,.25); }
    .section-badge.maxtrade { background: rgba(16,185,129,.18); color:#0f766e; border-color: rgba(15,118,110,.25); }
    [data-bs-theme="dark"] .section-badge { background: rgba(99,102,241,.28); color:#c7d2fe; border-color: rgba(199,210,254,.25); }
    [data-bs-theme="dark"] .section-badge.maxtrade { background: rgba(16,185,129,.28); color:#9bf3cf; border-color: rgba(155,243,207,.25); }

    .post-card { border-radius: 12px; border: 1px solid rgba(0,0,0,.06); box-shadow: 0 4px 16px rgba(0,0,0,.06); }
    .post-card .card-img-top { height: 140px; border-bottom: 1px solid rgba(0,0,0,.06); }
    .post-card .date { font-size: .75rem; font-weight: 600; color: var(--tblr-muted); }
    .post-card .btn { font-weight: 600; }

    .site-footer { text-align:center; color: var(--tblr-muted); font-size:.9rem; padding: 16px; margin: 18px 20px; border-top: 1px solid rgba(0,0,0,.06); background: rgba(255,255,255,.45); border-radius: 12px; }
    [data-bs-theme="dark"] .site-footer { background: rgba(18,18,24,.45); border-top-color: rgba(255,255,255,.08); }
  </style>
</head>
<body>
  <!-- Cabeçalho/hero -->
  <div class="portal-hero">
    <div class="container-xl position-relative">
      <div class="py-3 d-flex justify-content-center align-items-center">
        <img class="hero-logo" src="assets/logo/logo-cores.png" alt="Grupo Barão">
      </div>
      <button id="themeToggle" class="btn btn-icon btn-outline-secondary theme-toggle" title="Alternar tema">
        <i id="themeIcon" class="ti"></i>
      </button>
    </div>
  </div>

  <div class="portal-wrap">
    <div class="container-xl">
      <!-- Grid de atalhos em container estilizado -->
      <div class="section-container mb-3">
      <div class="row g-3">
        <!-- Cada card segue o padrão portal-card + accent-* para variar a cor do topo -->
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-blue d-block" href="#updates" data-group="group-maxtrade" aria-label="MaxTrade Barão Distribuidor" tabindex="0" role="button">
            <span class="icon bg-blue-lt text-blue"><i class="ti ti-box"></i></span>
            <div class="title">MaxTrade</div>
            <div class="subtitle">Barão Distribuidor</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-purple d-block" href="#updates" data-group="group-maxtrade" aria-label="MaxTrade ToyMania" tabindex="0" role="button">
            <span class="icon bg-purple-lt text-purple"><i class="ti ti-box"></i></span>
            <div class="title">MaxTrade</div>
            <div class="subtitle">ToyMania</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-cyan d-block" href="#updates" data-group="group-sistemas" aria-label="Sistemas da Logística" tabindex="0" role="button">
            <span class="icon bg-cyan-lt text-cyan"><i class="ti ti-layout-grid"></i></span>
            <div class="title">Sistemas</div>
            <div class="subtitle">da Logística</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-cyan d-block" href="#updates" data-group="group-sistemas" aria-label="Sistemas do Representante" tabindex="0" role="button">
            <span class="icon bg-cyan-lt text-cyan"><i class="ti ti-layout-grid"></i></span>
            <div class="title">Sistemas</div>
            <div class="subtitle">do Representante</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-green d-block" href="#updates" data-group="group-rh" aria-label="RH Grupo Barão" tabindex="0" role="button">
            <span class="icon bg-green-lt text-green"><i class="ti ti-users"></i></span>
            <div class="title">RH</div>
            <div class="subtitle">Grupo Barão</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-blue d-block" href="#updates" data-group="group-ti" aria-label="TI Suporte Grupo Barão" tabindex="0" role="button">
            <span class="icon bg-blue-lt text-blue"><i class="ti ti-headset"></i></span>
            <div class="title">TI Suporte</div>
            <div class="subtitle">Grupo Barão</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-yellow d-block" href="#updates" data-group="group-predial" aria-label="Predial Grupo Barão" tabindex="0" role="button">
            <span class="icon bg-yellow-lt text-yellow"><i class="ti ti-building"></i></span>
            <div class="title">Predial</div>
            <div class="subtitle">Grupo Barão</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-pink d-block" href="#updates" data-group="group-contas" aria-label="Política do Contas a Pagar" tabindex="0" role="button">
            <span class="icon bg-pink-lt text-pink"><i class="ti ti-file-text"></i></span>
            <div class="title">Política do</div>
            <div class="subtitle">Contas a Pagar</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-purple d-block" href="#updates" data-group="group-lideranca" aria-label="Portal da Liderança" tabindex="0" role="button">
            <span class="icon bg-purple-lt text-purple"><i class="ti ti-users-group"></i></span>
            <div class="title">Portal da</div>
            <div class="subtitle">Liderança</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-blue d-block" href="#updates" data-group="group-chamado" aria-label="Chamado TI Grupo Barão" tabindex="0" role="button">
            <span class="icon bg-blue-lt text-blue"><i class="ti ti-message-report"></i></span>
            <div class="title">Chamado TI</div>
            <div class="subtitle">Grupo Barão</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-cyan d-block" href="#updates" data-group="group-seguranca" aria-label="Política de Segurança" tabindex="0" role="button">
            <span class="icon bg-cyan-lt text-cyan"><i class="ti ti-shield"></i></span>
            <div class="title">Política</div>
            <div class="subtitle">de Segurança</div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <a class="card portal-card accent-purple d-block" href="#updates" data-group="group-maxtrade" aria-label="Atualizações Maxtrade" tabindex="0" role="button">
            <span class="icon bg-purple-lt text-purple"><i class="ti ti-bolt"></i></span>
            <div class="title">Atualizações</div>
            <div class="subtitle">Maxtrade</div>
          </a>
        </div>
      </div>
      </div>

      <!-- Título Atualizações -->
      <h2 id="updates" class="section-title">Atualizações</h2>

      <!-- Hub de grupos: mostra um grupo por vez -->
      <div id="updates-hub">
        <!-- Grupo: Blog do TI (padrão visível) -->
        <div id="group-blog-ti" data-group-section>
          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="section-container">
                <div class="d-flex justify-content-center">
                  <span class="badge section-badge px-3 py-2 rounded-pill">BLOG DO TI</span>
                </div>
                <div class="row g-3 mt-1">
              <div class="col-12">
                <div class="card post-card">
                  <div class="card-img-top" style="background-image:url('uploads/img.png'); background-size:cover; background-position:center"></div>
                  <div class="card-body">
                    <div class="date">07/03/2025</div>
                    <h3 class="card-title">A Importância de Prevenir Vírus Digitais</h3>
                    <p class="text-muted">Introdução ao mundo corporativo moderno, o fluxo de informação...</p>
                    <a class="btn btn-primary w-100">Continuar Lendo</a>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="card post-card">
                  <div class="card-img-top" style="background:linear-gradient(135deg,#dbeafe,#ede9fe)"></div>
                  <div class="card-body">
                    <div class="date">07/03/2025</div>
                    <h3 class="card-title">Organize Projetos com Trello</h3>
                    <p class="text-muted">Ferramentas populares e fluxos ágeis para equipes.</p>
                    <a class="btn btn-primary w-100">Continuar Lendo</a>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <a class="btn btn-outline-secondary w-100">Ver mais do Portal</a>
              </div>
                </div>
              </div>
            </div>

            <!-- Coluna direita complementar opcional -->
            <div class="col-12 col-lg-6">
              <div class="section-container">
                <div class="d-flex justify-content-center">
                  <span class="badge section-badge px-3 py-2 rounded-pill">BLOG DO TI</span>
                </div>
                <div class="row g-3 mt-1">
                  <div class="col-12">
                    <div class="card post-card">
                      <div class="card-img-top" style="background:linear-gradient(135deg,#ecfeff,#f5f3ff)"></div>
                      <div class="card-body">
                        <div class="date">09/03/2025</div>
                        <h3 class="card-title">Dicas de Segurança</h3>
                        <p class="text-muted">Práticas para tráfego seguro e prevenção de incidentes.</p>
                        <a class="btn btn-primary w-100">Continuar Lendo</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grupo: Atualizações Maxtrade -->
        <div id="group-maxtrade" data-group-section class="d-none">
          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="section-container">
                <div class="d-flex justify-content-center">
                  <span class="badge section-badge maxtrade px-3 py-2 rounded-pill">ATUALIZAÇÕES MAXTRADE</span>
                </div>
                <div class="row g-3 mt-1">
              <div class="col-12">
                <div class="card post-card">
                  <div class="card-img-top" style="background-image:url('uploads/Untitled-1.jpg'); background-size:cover; background-position:center"></div>
                  <div class="card-body">
                    <div class="date">29/05/2025</div>
                    <h3 class="card-title">Nova Consulta de Estoque no CD</h3>
                    <p class="text-muted">Transformação digital, eficiência e organização.</p>
                    <a class="btn btn-primary w-100">Continuar Lendo</a>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="card post-card">
                  <div class="card-img-top" style="background-image:url('uploads/img.jpg'); background-size:cover; background-position:center"></div>
                  <div class="card-body">
                    <div class="date">16/05/2025</div>
                    <h3 class="card-title">Cálculo de Cubagem na ToyMania</h3>
                    <p class="text-muted">Entenda o papel da cubagem nas operações logísticas.</p>
                    <a class="btn btn-primary w-100">Continuar Lendo</a>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <a class="btn btn-outline-secondary w-100">Ver mais do Maxtrade</a>
              </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="section-container">
                <div class="d-flex justify-content-center">
                  <span class="badge section-badge maxtrade px-3 py-2 rounded-pill">ATUALIZAÇÕES MAXTRADE</span>
                </div>
                <div class="row g-3 mt-1">
                  <div class="col-12">
                    <div class="card post-card">
                      <div class="card-img-top" style="background:linear-gradient(135deg,#fef3c7,#e9d5ff)"></div>
                      <div class="card-body">
                        <div class="date">18/06/2025</div>
                        <h3 class="card-title">Melhorias no Checkout</h3>
                        <p class="text-muted">Fluxo de pagamento e UX revisados para eficiência.</p>
                        <a class="btn btn-primary w-100">Continuar Lendo</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Rodapé -->
      <div class="site-footer">© <?php echo date('Y'); ?> Grupo Barão • Todos os direitos reservados</div>
    </div>
  </div>

  <!-- JS Tabler -->
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
  <script>
    // Alternância de tema (persistente)
    const html = document.documentElement;
    const btn = document.getElementById('themeToggle');
    const ico = document.getElementById('themeIcon');
    const saved = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', saved);
    ico.className = 'ti ' + (saved==='dark' ? 'ti-moon' : 'ti-sun');
    btn.addEventListener('click', () => {
      const next = html.getAttribute('data-bs-theme')==='dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem('theme', next);
      ico.className = 'ti ' + (next==='dark' ? 'ti-moon' : 'ti-sun');
    });

    // Alternância dos grupos por clique nos cards do topo
    const groupSections = document.querySelectorAll('[data-group-section]');
    const portalCards = document.querySelectorAll('.portal-card[data-group]');
    const showGroup = id => {
      groupSections.forEach(sec => sec.classList.toggle('d-none', sec.id !== id));
      document.getElementById('updates')?.scrollIntoView({behavior:'smooth', block:'start'});
    };
    portalCards.forEach(card => {
      card.addEventListener('click', e => { e.preventDefault(); showGroup(card.dataset.group); });
      card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showGroup(card.dataset.group); }});
    });
  </script>
</body>
</html>
