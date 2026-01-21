<?php
// index.php — Organograma com cartão “vidro fosco” no hover
require_once '../admin/check_access.php';
/* ========= Conexão ========= */
$base = __DIR__;
if (file_exists($base . '/config.php')) {
  require_once $base . '/config.php';
} else {
  die('Crie config.php no mesmo diretório com $conn.');
}

/* ========= Helpers ========= */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function initials($name){
  $p = preg_split('/\s+/u', trim((string)$name));
  if (!$p || count($p) === 0) return '??';
  $f = mb_substr($p[0],0,1,'UTF-8');
  $l = mb_substr($p[count($p)-1],0,1,'UTF-8');
  return mb_strtoupper($f . ($l!==$f?$l:''), 'UTF-8');
}

/* ========= Carrega dados da tabela colaboradores ========= */
$selectedEmpresas = isset($_GET['empresas']) ? (array)$_GET['empresas'] : ['todos'];
$modoNav          = isset($_GET['modo']) ? (string)$_GET['modo'] : 'foco'; // 'livre' | 'foco'
$viewMode         = isset($_GET['view']) ? (string)$_GET['view'] : 'org'; // 'org' | 'lista'
$zoomParam        = isset($_GET['zoom']) ? (float)$_GET['zoom'] : 1.0;
// Parâmetros da Lista
$qTerm            = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$perPage          = isset($_GET['per']) ? max(15, (int)$_GET['per']) : 15; // mínimo 15
$page             = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Monta cláusula para filtrar por empresa, garantindo que "Grupo Barão" sempre entre
$whereEmpresa = '';
if (!empty($selectedEmpresas)) {
  // Se tiver "todos" marcado, não filtra
  $todosMarcado = in_array('todos', array_map('strtolower', $selectedEmpresas));
  if (!$todosMarcado) {
    // Sanitiza nomes das empresas previstos
    $validas = [];
    foreach ($selectedEmpresas as $em) {
      $em = trim((string)$em);
      if (in_array($em, ['Barão','Toymania','Alfaness','Barao'])) {
        // normaliza acento
        if ($em === 'Barao') $em = 'Barão';
        $validas[] = $conn->real_escape_string($em);
      }
    }
    if (!empty($validas)) {
      $inList = "'" . implode("','", $validas) . "'";
      // Condição para incluir sempre Grupo Barão, considerando variações de acento
      $grupoBaraoCond = "(LOWER(empresa) LIKE '%grupo%' AND (LOWER(empresa) LIKE '%barão%' OR LOWER(empresa) LIKE '%barao%'))";
      $whereEmpresa = " AND (empresa IN ($inList) OR $grupoBaraoCond)";
    }
  }
}

// Monta consulta conforme modo de visualização
$rows = [];
$children = [];
if ($viewMode !== 'lista') {
  // Modo Organograma: carrega todos os registros para montar a árvore
  $sql = "SELECT id,nome,cargo,departamento,empresa,ramal,telefone,email,teams,
                 tipo_contrato,descricao,observacoes,data_admissao,parent_id,
                 ordem_exibicao,nivel_hierarquico,foto,ativo
          FROM colaboradores
          WHERE COALESCE(ativo,1)=1" . $whereEmpresa . "
          ORDER BY COALESCE(nivel_hierarquico,1), COALESCE(ordem_exibicao,0), nome";
  $res = $conn->query($sql);
  if(!$res){ die('Erro ao buscar dados: '.$conn->error); }
  while($r = $res->fetch_assoc()){
    $r['id']        = (int)$r['id'];
    $r['parent_id'] = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
    $rows[$r['id']] = $r;
    $pid = $r['parent_id'] ?? 0;
    if(!isset($children[$pid])) $children[$pid] = [];
    $children[$pid][] = $r['id'];
  }
} else {
  // Modo Lista: busca + paginação
  $whereBusca = '';
  if ($qTerm !== '') {
    $qEsc = $conn->real_escape_string($qTerm);
    $like = "%$qEsc%";
    $whereBusca = " AND (nome LIKE '$like' OR cargo LIKE '$like' OR departamento LIKE '$like' OR empresa LIKE '$like' OR email LIKE '$like' OR telefone LIKE '$like' OR ramal LIKE '$like')";
  }
  // total de registros para paginação
  $countSql = "SELECT COUNT(*) AS total FROM colaboradores WHERE COALESCE(ativo,1)=1" . $whereEmpresa . $whereBusca;
  $countRes = $conn->query($countSql);
  if(!$countRes){ die('Erro ao contar registros: '.$conn->error); }
  $total = (int)($countRes->fetch_assoc()['total'] ?? 0);
  $totalPages = max(1, (int)ceil($total / $perPage));
  if ($page > $totalPages) $page = $totalPages;
  $offset = ($page - 1) * $perPage;

  $sql = "SELECT id,nome,cargo,departamento,empresa,ramal,telefone,email,teams,
                 tipo_contrato,descricao,observacoes,data_admissao,parent_id,
                 ordem_exibicao,nivel_hierarquico,foto,ativo
          FROM colaboradores
          WHERE COALESCE(ativo,1)=1" . $whereEmpresa . $whereBusca . "
          ORDER BY nome
          LIMIT $perPage OFFSET $offset";
  $res = $conn->query($sql);
  if(!$res){ die('Erro ao buscar dados (lista): '.$conn->error); }
  while($r = $res->fetch_assoc()){
    $r['id']        = (int)$r['id'];
    $r['parent_id'] = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
    $rows[$r['id']] = $r;
  }
}

/* ========= Render ========= */
function renderNode($id, $rows, $children, $level=0, $open=true){
  $n = $rows[$id];
  $hasKids  = !empty($children[$id]);
  $expClass = $hasKids ? 'expandable' : '';
  $openAttr = $open ? ' open' : '';

  $fotoPath = null;
  if (!empty($n['foto'])) {
    $rel = (strpos($n['foto'],'uploads/')===0) ? $n['foto'] : 'uploads/'.$n['foto'];
    if (is_file(__DIR__ . '/' . $rel)) $fotoPath = $rel;
  }

  // data-* para o cartão
  $data = [
    'id'            => $n['id'],
    'nome'          => $n['nome'] ?? '',
    'cargo'         => $n['cargo'] ?? '',
    'departamento'  => $n['departamento'] ?? '',
    'empresa'       => $n['empresa'] ?? '',
    'ramal'         => $n['ramal'] ?? '',
    'telefone'      => $n['telefone'] ?? '',
    'email'         => $n['email'] ?? '',
    'teams'         => $n['teams'] ?? '',
    'tipo'          => $n['tipo_contrato'] ?? '',
    'admissao'      => $n['data_admissao'] ?? '',
    'obs'           => $n['observacoes'] ?? '',
    'foto'          => $fotoPath ?: '',
  ];
  $dataAttr = '';
  foreach($data as $k=>$v){ $dataAttr .= ' data-'.$k.'="'.e($v).'"'; }

  echo "<li>\n";
  echo "  <details class=\"$expClass\"$openAttr>\n";
  echo "    <summary class=\"person\"$dataAttr>\n";
  echo "      <span class=\"avatar\">";
  if ($fotoPath){
    echo "<img src=\"".e($fotoPath)."\" alt=\"\">";
  } else {
    echo "<span class=\"avatar-initials\" data-name=\"".e($n['nome'])."\">".e(initials($n['nome']))."</span>";
  }
  echo "</span>\n";
  echo "      <span class=\"name\">".e($n['nome'])."</span>\n";
  echo "      <span class=\"title\">".e($n['cargo'])."</span>\n";
  echo "    </summary>\n";

  if (!empty($children[$id])){
    $ulClass = ($level===0) ? 'lvl-1' : 'lvl-2';
    echo "    <ul class=\"$ulClass\">\n";
    foreach($children[$id] as $cid){
      renderNode($cid, $rows, $children, $level+1, $level < 0);
    }
    echo "    </ul>\n";
  }
  echo "  </details>\n";
  echo "</li>\n";
}

$roots = $children[0] ?? [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Organograma • cartão vidro fosco</title>
<?php if (($viewMode ?? 'org') === 'lista'): ?>
  <!-- Tabler CSS (apenas na visualização Lista) -->
  <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
<?php endif; ?>
<style>
  :root{
    --avatar: 108px;
    --gap-x: 44px;
    --gap-y: 36px;
    --min-slot-1: 260;
    --slot-2: 220;
    --stroke: #cbd5e1;
    --stroke-strong:#b8c2d3;
    --ring-green:#22c55e;  /* nó expandível fechado */
    --ring-gray:#cbd5e1;   /* aberto e folha */
    --text-muted:#6b7280;
    --pad-bottom: 180px;   /* folga para não colar na barra de rolagem */
    --footer-h: 60px;      /* altura aproximada do rodapé fixo */
    --font-scale: 1;       /* escala de fontes conforme zoom */
  }
  *{box-sizing:border-box}
  html,body{ margin:0; background:#fff; color:#0f172a;
    font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,"Noto Sans","Helvetica Neue",sans-serif }

  /* viewport arrastável */
  .wrap{ width:100vw; height:100vh; overflow:auto; margin:20px 0 0; cursor:grab }
  .wrap.dragging{ cursor:grabbing }
  body.dragging{ user-select:none; -webkit-user-select:none; }

  .stage{ position:relative; min-width:100vw; padding-bottom:var(--pad-bottom); }
  svg#wires{ position:absolute; inset:0 auto auto 0; pointer-events:none; z-index:0; }

  .org{ position:relative; z-index:1; }
  .org ul{ margin:0; padding:0; padding-top: calc(var(--gap-y) + 16px); width:100%; }
  .org > ul, .org .lvl-1{ display:flex; justify-content:center; gap:var(--gap-x); }
  .org .lvl-2{ display:flex; justify-content:center; gap:var(--gap-x); }
  .org li{ list-style:none; padding:0; text-align:center; }

  details{ display:inline-block }
  summary{ list-style:none; cursor:pointer }
  summary::-webkit-details-marker{ display:none }

  .person{ display:flex; flex-direction:column; align-items:center; padding:6px 10px 0; margin:0 auto; max-width:max(var(--avatar), calc(var(--min-slot-1) * 1px)); }
  .avatar{ width:var(--avatar); height:var(--avatar); border-radius:50%; overflow:hidden; background:#e5e7eb;
           box-shadow:0 0 0 3px #fff, 0 6px 16px rgba(22,30,50,.14); display:flex; align-items:center; justify-content:center; }
  /* ANÉIS */
  details.expandable:not([open]) > summary .avatar{ box-shadow:0 0 0 3px #fff, 0 0 0 6px var(--ring-green), 0 8px 18px rgba(22,30,50,.18); }
  details.expandable[open] > summary .avatar,
  details:not(.expandable) > summary .avatar{ box-shadow:0 0 0 3px #fff, 0 0 0 6px var(--ring-gray), 0 8px 18px rgba(22,30,50,.18); }

  .avatar img{ width:100%; height:100%; object-fit:cover; display:block }
  .avatar-initials{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:800; font-size: calc(24px * var(--font-scale)); color:#0f172a; }
  .name{ font-size: calc(1.28rem * var(--font-scale)); font-weight:800; margin-top:10px; line-height:1.15; max-width:260px }
  .title{ color:var(--text-muted); font-size: calc(.98rem * var(--font-scale)); margin-top:2px; max-width:260px }

  /* ===== Cartão flutuante (glass) ===== */
  #hoverCard{
    position:fixed; z-index:50; width:min(420px, calc(100vw - 24px));
    backdrop-filter: blur(16px) saturate(145%);
    -webkit-backdrop-filter: blur(16px) saturate(145%);
    background: rgba(255,255,255,0.58);
    border-radius:16px; border:1px solid rgba(255,255,255,.35);
    box-shadow: 0 20px 60px rgba(17,24,39,.25);
    padding:0; display:none;
  }
  #hoverCard .brandbar{
    height:72px; border-radius:16px 16px 0 0;
    background: linear-gradient(90deg,#64748b,#94a3b8);
  }
  /* Cores por empresa */
  .brand--barao    { background: linear-gradient(90deg,#2563eb,#60a5fa) !important; }
  .brand--toymania { background: linear-gradient(90deg,#7c3aed,#c084fc) !important; }
  .brand--alfaness { background: linear-gradient(90deg,#111827,#4b5563) !important; }
  .brand--fun      { background: linear-gradient(90deg,#eab308,#fde047) !important; }

  #hoverCard .content{ padding:20px 18px 14px 18px; }
  #hoverCard .header{
    display:flex; gap:12px; align-items:center; margin-top:-28px;
  }
  #hoverCard .header > div:last-child{ margin-top:4px; }
  #hoverCard .mini-avatar{
    width:72px; height:72px; border-radius:50%;
    box-shadow:0 0 0 3px #fff, 0 6px 14px rgba(17,24,39,.22);
    overflow:hidden; flex:0 0 auto; background:#e5e7eb; display:flex; align-items:center; justify-content:center;
  }
  #hoverCard .mini-avatar img{ width:100%; height:100%; object-fit:cover; display:block; }
  #hoverCard .mini-avatar .inits{ font-weight:800; color:#0f172a; }

  #hoverCard .nm{ font-weight:800; font-size:1.05rem; line-height:1.15 }
  #hoverCard .sub{ color:#475569; font-size:.92rem; margin-top:2px }

  #hoverCard .chips{ display:flex; gap:8px; margin:10px 0 8px; flex-wrap:wrap; }
  .chip{
    display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px;
    background: rgba(255,255,255,.55); border:1px solid rgba(255,255,255,.55); font-size:.82rem; color:#0f172a;
  }

  #hoverCard .actions{ display:flex; gap:8px; margin:6px 0 10px; flex-wrap:wrap; }
  #hoverCard .actions{ align-items:center; }
  .btn-mini{
    display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; font-weight:600;
    background:#2563eb; color:#fff; border:none; text-decoration:none; line-height:1; min-height:36px;
    box-shadow: 0 2px 6px rgba(17,24,39,.12);
  }
  .btn-mini .ico{ width:18px; height:18px; display:inline-block; }
  .btn-mini[href^="tel:"]    { background:#059669; }
  .btn-mini[href^="https:"]  { background:#6b7280; }
  .btn-mini.btn-whatsapp      { background:#25D366; }
  .btn-mini svg{ width:18px; height:18px; }

  #hoverCard .grid{
    display:grid; grid-template-columns: 120px 1fr; gap:8px 12px; font-size:.9rem; margin-top:6px;
  }
  #hoverCard .grid .lbl{ color:#64748b }
  #hoverCard .divider{ height:1px; background:rgba(15,23,42,.08); margin:10px 0; }

  /* setinha */
  #hoverCard::after{
    content:""; position:absolute; top:28px; width:12px; height:12px; background:inherit; border:inherit;
    transform:rotate(45deg); z-index:-1;
  }
  /* lado esquerdo (seta aponta pra direita) */
  #hoverCard.at-left::after{ right:-6px; }
  /* lado direito (seta aponta pra esquerda) */
  #hoverCard.at-right::after{ left:-6px; }

  @media (max-width: 900px){
    :root{ --gap-x: 28px; --min-slot-1: 220; --slot-2: 200; }
    .avatar-initials{ font-size: calc(20px * var(--font-scale)); }
  }
</style>
</head>
<body>
<?php
// Helper para construir query preservando filtros atuais
$qsEmp = '';
if (!empty($selectedEmpresas)){
  foreach ($selectedEmpresas as $em){ $qsEmp .= '&empresas[]=' . urlencode($em); }
}
$qsCommon = 'modo=' . urlencode($modoNav) . '&zoom=' . urlencode((string)$zoomParam) . $qsEmp;
?>
<!-- Abas de visualização + Sair -->
<div id="tabs" style="position:fixed; left:16px; top:16px; z-index:61; display:flex; gap:8px; align-items:center;">
  <a href="index.php?view=org&<?= $qsCommon ?>" class="tab <?= $viewMode==='org'?'active':'' ?>">Organograma</a>
  <a href="index.php?view=lista&<?= $qsCommon ?>" class="tab <?= $viewMode==='lista'?'active':'' ?>">Lista</a>
  <a href="export_xlsx.php?<?= $qsCommon ?><?= !empty($qTerm) ? '&q='.urlencode($qTerm) : '' ?>" class="tab tab-download" title="Baixar planilha">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
      <polyline points="7 10 12 15 17 10"></polyline>
      <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>
    Baixar XLSX
  </a>
  <a href="logout.php" class="tab tab-logout" title="Sair">Sair</a>
</div>
<style>
  .tab{ display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; border:1px solid rgba(15,23,42,.12); background:#fff; color:#0f172a; font-weight:700; text-decoration:none; box-shadow:0 6px 16px rgba(17,24,39,.12); }
  .tab.active{ background:#0f172a; color:#fff; border-color:#0f172a; }
  .tab-logout{ background:#ef4444; color:#fff; border-color:#ef4444; }
  .tab-logout:hover{ filter: brightness(1.05); }
  .tab-download{ background:#0ea5e9; color:#fff; border-color:#0ea5e9; }
  .tab-download:hover{ filter: brightness(1.05); }
  .listWrap{ padding:80px 18px calc(18px + var(--footer-h)); }
  .listWrap .container-xl{ max-width: 100%; }
  .listWrap .table{ width:100%; table-layout: fixed; }
  /* Larguras por coluna para evitar rolagem horizontal */
  .listWrap .table thead th:nth-child(1){ width:24%; }
  .listWrap .table thead th:nth-child(2){ width:8%; }
  .listWrap .table thead th:nth-child(3){ width:10%; }
  .listWrap .table thead th:nth-child(4){ width:12%; }
  .listWrap .table thead th:nth-child(5){ width:25%; }
  .listWrap .table thead th:nth-child(6){ width:9%; }
  .listWrap .table thead th:nth-child(7){ width:12%; }
  /* Estilo de status similar ao CRUD */
  .status-dot{ width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:8px; }
  .status-active{ background-color:#2fb344; }
  .status-inactive{ background-color:#d63939; }
  .avatar-sm{ width:2rem; height:2rem; border-radius:50%; overflow:hidden; background:#e5e7eb; display:inline-block; background-position:center; background-repeat:no-repeat; background-size:cover; }
  .avatar-sm img{ width:100%; height:100%; object-fit:cover; display:block; }
                .btn-icon{ display:inline-flex; align-items:center; justify-content:center; gap:0; width:36px; height:36px; padding:0; border-radius:8px; color:#fff; text-decoration:none; box-shadow:0 2px 6px rgba(17,24,39,.12); line-height:1; position:relative; }
                .btn-icon svg{ position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:20px; height:20px; display:block; }
                /* Removidos offsets por botão; centramento absoluto garante ícone no meio exato */
                .btn-icon.btn-whatsapp{ background:#25D366; }
                .btn-icon.btn-teams{ background:#6366f1; }
                .btn-icon.btn-email{ background:#0ea5e9; }
                .btn-icon.disabled{ background:#eef2f7; color:#64748b; border:1px solid #cbd5e1; box-shadow:none; cursor:default; pointer-events:none; }
                .btn-icon.disabled svg *{ fill:#64748b !important; }
  /* Melhora contraste na Lista */
  .listWrap .card{ border:1px solid #cbd5e1; }
  .listWrap .table thead th{ background:#f8fafc; color:#0f172a; border-bottom:1px solid #cbd5e1; }
  .listWrap .table tbody tr{ border-bottom:1px solid #cbd5e1; }
  .listWrap .table tbody tr:hover{ background:#f1f5f9; }
  /* Centralizar ícones na coluna Ações */
  .listWrap .table th:last-child,
  .listWrap .table td:last-child{ text-align:center; }
  .listWrap .table td:last-child{ min-width: 140px; }
  .btn-list{ display:flex; align-items:center; justify-content:center; gap:8px; }
  /* Evitar quebra de linha na Lista e aplicar ellipsis */
  .listWrap .table th,
  .listWrap .table td{ white-space: nowrap; vertical-align: middle; font-size:.88rem; }
  .listWrap .table .d-flex{ min-width:0; }
  .listWrap .table .flex-fill{ min-width:0; }
  .listWrap .table .font-weight-medium{ white-space: nowrap; overflow:hidden; text-overflow: ellipsis; max-width:100%; display:block; font-size:.95rem; }
  .listWrap .table .text-muted{ white-space: nowrap; overflow:hidden; text-overflow: ellipsis; max-width:100%; display:block; font-size:.82rem; }
  .listWrap .table td a{ display:inline-block; white-space: nowrap; overflow:hidden; text-overflow: ellipsis; max-width:100%; }

  /* Rodapé do site */
  body{ padding-bottom: var(--footer-h); }
  .site-footer{ padding:16px 18px; text-align:center; color:#64748b; font-size:13px; border-top:1px solid rgba(15,23,42,.08); background:#f8fafc; position:fixed; left:0; right:0; bottom:0; z-index:50; }
  @media (prefers-color-scheme: dark){ .site-footer{ background:#0b1220; color:#cbd5e1; border-top-color: rgba(203,213,225,.18);} }

  /* Botão de toggle do painel (minimizado) */
  .navToggleWrap{ position:fixed; right:16px; top:16px; z-index:61; display:inline-flex; align-items:center; gap:8px; }
  #navToggle{ position:relative; width:46px; height:46px; border-radius:12px; border:1px solid rgba(15,23,42,.18); background:#0ea5e9; color:#fff; box-shadow:0 10px 24px rgba(14,165,233,.35); cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
  #navToggle svg{ width:24px; height:24px; display:block; }
  #navToggle.attn{ animation:pulseGlow 2s ease-in-out infinite; }
  .navToggleWrap.jump{ animation:jump 0.9s ease; }
  #navToggleLabel{ background:#0ea5e9; color:#fff; font-weight:600; padding:6px 10px; border-radius:10px; border:1px solid rgba(15,23,42,.18); box-shadow:0 10px 24px rgba(14,165,233,.25); }
  #navToggleLabel.attn{ animation:pulseGlow 2s ease-in-out infinite; }
  @keyframes pulseGlow{ 0%{ box-shadow:0 0 0 0 rgba(14,165,233,.6); } 50%{ box-shadow:0 0 0 12px rgba(14,165,233,.0); } 100%{ box-shadow:0 0 0 0 rgba(14,165,233,.0); } }
  @keyframes jump{ 0%{ transform:translateY(0) } 20%{ transform:translateY(-8px) } 40%{ transform:translateY(0) } 60%{ transform:translateY(-4px) } 80%{ transform:translateY(0) } 100%{ transform:translateY(0) } }
</style>
<?php if ($viewMode !== 'lista'): ?>
<!-- Painel de navegação (canto direito) -->
<div id="navToggleWrap" class="navToggleWrap" aria-label="Botão Navegação">
  <button id="navToggle" title="Mostrar painel" aria-label="Mostrar painel">
    <!-- Ícone de paralelepípedo (cubóide) -->
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <polygon points="4,8 14,5 20,8 10,11" fill="#38bdf8" opacity="0.95"></polygon>
      <polygon points="4,8 4,18 10,21 10,11" fill="#0284c7"></polygon>
      <polygon points="10,11 20,8 20,18 10,21" fill="#0ea5e9"></polygon>
    </svg>
  </button>
  <div id="navToggleLabel">Navegação</div>
</div>
<div id="navPanel" style="position:fixed; right:16px; top:16px; z-index:60;">
  <form id="navForm" method="get" action="index.php" style="
    background:rgba(255,255,255,.92); border:1px solid rgba(15,23,42,.1); border-radius:12px; padding:12px; box-shadow:0 10px 30px rgba(17,24,39,.18);
    font-family:inherit; color:#0f172a; min-width:240px">
    <div id="navHeader" style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; cursor:move;">
      <div style="font-weight:800;">Navegação</div>
      <button type="button" id="hidePanelBtn" title="Ocultar painel" style="border:none; background:transparent; font-size:18px; cursor:pointer">✖️</button>
    </div>
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
      <label style="font-size:.9rem; color:#475569;">Zoom</label>
      <input type="range" id="zoomRange" name="zoom" min="0.6" max="1.6" step="0.05" value="<?= htmlspecialchars((string)max(0.6,min(1.6,$zoomParam))) ?>" style="flex:1">
      <span id="zoomLabel" style="width:40px; text-align:right; font-size:.85rem; color:#475569">1.00×</span>
    </div>
    <div style="font-weight:700; margin:8px 0 6px;">Empresas</div>
    <div style="display:flex; flex-direction:column; gap:6px; font-size:.92rem;">
      <?php
        $sel = array_map('strtolower', $selectedEmpresas);
        $isTodos = in_array('todos', $sel) || empty($sel);
        $opts = ['Barão','Toymania','Alfaness'];
      ?>
      <label><input type="checkbox" id="chkTodos" name="empresas[]" value="todos" <?= $isTodos ? 'checked' : '' ?>> Selecionar tudo</label>
      <?php foreach($opts as $op): $ck = $isTodos || in_array(strtolower($op), $sel); ?>
        <label><input type="checkbox" class="chkEmpresa" name="empresas[]" value="<?= e($op) ?>" <?= $ck ? 'checked' : '' ?>> <?= e($op) ?></label>
      <?php endforeach; ?>
      <label style="opacity:.7"><input type="checkbox" checked disabled> Grupo Barão (sempre visível)</label>
    </div>
    <div style="font-weight:700; margin:10px 0 6px;">Modo de expansão</div>
    <div style="display:flex; flex-direction:column; gap:6px; font-size:.92rem;">
      <label><input type="radio" name="modo" value="foco" <?= $modoNav==='foco'?'checked':'' ?>> Expandir Partes</label>
      <label><input type="radio" name="modo" value="livre" <?= $modoNav==='foco'?'':'checked' ?>> Expandir Tudo</label>
    </div>
  </form>
</div>
<?php endif; ?>
<?php if ($viewMode !== 'lista'): ?>
  <div class="wrap">
    <div class="stage" id="stage">
      <svg id="wires"></svg>

      <div class="org" id="org">
        <ul class="lvl-0"><li>
          <?php
          if (!$roots){
            echo "<p style='text-align:center;color:#64748b'>Nenhum registro ativo encontrado.</p>";
          } else {
            if (count($roots) === 1) {
              renderNode($roots[0], $rows, $children, 0, true);
            } else {
              echo "<details class=\"expandable\" open>\n";
              echo "  <summary class=\"person placeholder\"></summary>\n";
              echo "  <ul class=\"lvl-1\">\n";
              foreach ($roots as $rid) renderNode($rid, $rows, $children, 0, true);
              echo "  </ul>\n";
              echo "</details>\n";
            }
          }
          ?>
        </li></ul>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="listWrap">
    <div class="container-xl">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h3 class="card-title">Lista de Colaboradores</h3>
          <div class="d-flex align-items-center" style="gap:8px;">
            <input id="listSearchInput" type="text" value="<?= e($qTerm) ?>" class="form-control" style="min-width:280px" placeholder="Buscar por nome, cargo, setor, empresa, e-mail...">
            <select id="itemsPerPageSelect" class="form-select">
              <?php foreach([15,25,50,100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $perPage==$opt?'selected':'' ?>><?= $opt ?> por página</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th>Colaborador</th>
                <th>Ramal</th>
                <th>Empresa</th>
                <th>Setor</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th class="w-1">Ações</th>
              </tr>
            </thead>
            <tbody id="listTableBody">
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td>
                    <?php
                      $fotoPath = '';
                      if (!empty($r['foto'])) {
                        $rel = (strpos($r['foto'],'uploads/')===0) ? $r['foto'] : 'uploads/'.$r['foto'];
                        if (is_file(__DIR__ . '/' . $rel)) $fotoPath = $rel;
                      }
                      $ava = $fotoPath ? $fotoPath : ('https://ui-avatars.com/api/?name='.urlencode($r['nome'] ?: 'User').'&background=random');
                    ?>
                    <div class="d-flex py-1 align-items-center">
                      <span class="avatar-sm"><img src="<?= e($ava) ?>" alt="<?= e($r['nome'] ?: '') ?>"></span>
                      <div class="flex-fill ms-2">
                        <div class="font-weight-medium"><?= e($r['nome'] ?: '—') ?></div>
                        <div class="text-muted"><?= e($r['cargo'] ?: '—') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= e($r['ramal'] ?: '—') ?></td>
                  <td><?= e($r['empresa'] ?: '—') ?></td>
                  <td><?= e($r['departamento'] ?: '—') ?></td>
                  <td><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email'] ?: '—') ?></a></td>
                  <td><?= e($r['telefone'] ?: '—') ?></td>
                  <td>
                    <?php
                      $tm = (function($t){
                        $s = trim((string)$t);
                        if ($s==='') return '';
                        if (preg_match('/^https?:/i',$s)) return $s;
                        return 'https://teams.live.com/l/invite/' . rawurlencode($s);
                      })($r['teams']);
                      $wa = (function($tel){
                        $num = preg_replace('/\D+/', '', (string)$tel);
                        if ($num==='') return '';
                        if (strpos($num,'55')!==0) $num = '55'.$num; return 'https://wa.me/'.$num;
                      })($r['telefone']);
                      $em = (function($email){
                        $s = trim((string)$email);
                        return $s!=='' ? ('mailto:'.$s) : '';
                      })($r['email']);
                    ?>
                    <div class="btn-list flex-nowrap">
                      <a class="btn-icon btn-teams <?= $tm ? '' : 'disabled' ?>" href="<?= $tm ? e($tm) : '#' ?>" <?= $tm ? 'target="_blank" rel="noopener" title="Abrir Teams" aria-label="Abrir Teams"' : 'aria-disabled="true" tabindex="-1" title="Teams indisponível" aria-label="Teams indisponível"' ?>>
                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                          <path d="M9.186 4.797a2.42 2.42 0 1 0-2.86-2.448h1.178c.929 0 1.682.753 1.682 1.682zm-4.295 7.738h2.613c.929 0 1.682-.753 1.682-1.682V5.58h2.783a.7.7 0 0 1 .682.716v4.294a4.197 4.197 0 0 1-4.093 4.293c-1.618-.04-3-.99-3.667-2.35Zm10.737-9.372a1.674 1.674 0 1 1-3.349 0 1.674 1.674 0 0 1 3.349 0m-2.238 9.488-.12-.002a5.2 5.2 0 0 0 .381-2.07V6.306a1.7 1.7 0 0 0-.15-.725h1.792c.39 0 .707.317.707.707v3.765a2.6 2.6 0 0 1-2.598 2.598z"/>
                          <path d="M.682 3.349h6.822c.377 0 .682.305.682.682v6.822a.68.68 0 0 1-.682.682H.682A.68.68 0 0 1 0 10.853V4.03c0-.377.305-.682.682-.682Zm5.206 2.596v-.72h-3.59v.72h1.357V9.66h.87V5.945z"/>
                        </svg>
                      </a>
                      <a class="btn-icon btn-whatsapp <?= $wa ? '' : 'disabled' ?>" href="<?= $wa ? e($wa) : '#' ?>" <?= $wa ? 'target="_blank" rel="noopener" title="Abrir WhatsApp" aria-label="Abrir WhatsApp"' : 'aria-disabled="true" tabindex="-1" title="WhatsApp indisponível" aria-label="WhatsApp indisponível"' ?>>
                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                        </svg>
                      </a>
                      <a class="btn-icon btn-email <?= $em ? '' : 'disabled' ?>" href="<?= $em ? e($em) : '#' ?>" <?= $em ? 'title="Enviar E-mail" aria-label="Enviar E-mail"' : 'aria-disabled="true" tabindex="-1" title="E-mail indisponível" aria-label="E-mail indisponível"' ?>>
                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                          <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                        </svg>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (isset($totalPages)): ?>
        <div class="card-footer d-flex align-items-center justify-content-between">
          <div id="recordsInfo" class="text-muted">Página <?= $page ?> de <?= $totalPages ?> • <?= $perPage ?> por página • Total <?= isset($total)?$total:count($rows) ?></div>
          <ul class="pagination m-0" id="pagination">
            <?php
              // constrói base da query
              $queryBase = 'view=lista';
              if ($qTerm!=='') $queryBase .= '&q=' . urlencode($qTerm);
              $queryBase .= '&per=' . urlencode((string)$perPage);
              if (!empty($selectedEmpresas)) { foreach($selectedEmpresas as $em){ $queryBase .= '&empresas[]=' . urlencode($em); } }
              $makeLink = function($p) use ($queryBase){ return 'index.php?' . $queryBase . '&page=' . urlencode((string)$p); };
              $prevDisabled = ($page<=1);
              $nextDisabled = ($page>=$totalPages);
              $start = max(1, $page-2);
              $end   = min($totalPages, $page+2);
            ?>
            <li class="page-item <?= $prevDisabled?'disabled':'' ?>">
              <a class="page-link" href="<?= $prevDisabled ? '#' : $makeLink($page-1) ?>">Anterior</a>
            </li>
            <?php for($p=$start; $p<=$end; $p++): ?>
              <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="<?= $makeLink($p) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $nextDisabled?'disabled':'' ?>">
              <a class="page-link" href="<?= $nextDisabled ? '#' : $makeLink($page+1) ?>">Próxima</a>
            </li>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($viewMode === 'lista'): ?>
<script>
(() => {
  const searchInput = document.getElementById('listSearchInput');
  const perSelect   = document.getElementById('itemsPerPageSelect');
  const tbody       = document.getElementById('listTableBody');
  const paginEl     = document.getElementById('pagination');
  const infoEl      = document.getElementById('recordsInfo');
  const selectedEmpresas = <?= json_encode(array_values($selectedEmpresas)) ?>;
  let currentPage = <?= isset($page)?(int)$page:1 ?>;
  let limit = parseInt(perSelect?.value || '15', 10);
  let debounceId = null;

  function waLink(tel){
    if(!tel) return '';
    const num = (''+tel).replace(/\D+/g,'');
    if(!num) return '';
    const withCountry = num.startsWith('55') ? num : ('55'+num);
    return 'https://wa.me/' + withCountry;
  }
  function teamsLink(val){
    if(!val) return '';
    const s = (''+val).trim();
    if(!s) return '';
    if(/^https?:/i.test(s)) return s;
    return 'https://teams.live.com/l/invite/' + encodeURIComponent(s);
  }
  function avatarUrl(nome,foto){
    if(foto){
      const f = String(foto);
      if(/^uploads\//.test(f)) return f;
      return 'uploads/' + f;
    }
    const n = nome || 'User';
    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(n) + '&background=random';
  }

  function renderRows(items){
    if(!tbody) return;
    if(!items || !items.length){
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3">Nenhum colaborador encontrado</td></tr>';
      return;
    }
    const rows = items.map(it => {
      const ava = avatarUrl(it.nome, it.foto);
      const wa  = waLink(it.telefone);
      const tm  = teamsLink(it.teams);
      return `
        <tr>
          <td>
            <div class="d-flex py-1 align-items-center">
              <span class="avatar-sm"><img src="${ava}" alt="${it.nome || ''}"></span>
              <div class="flex-fill ms-2">
                <div class="font-weight-medium">${it.nome || '—'}</div>
                <div class="text-muted">${it.cargo || '—'}</div>
              </div>
            </div>
          </td>
          <td>${it.ramal || '—'}</td>
          <td>${it.empresa || '—'}</td>
          <td>${it.departamento || '—'}</td>
          <td>${it.email ? `<a href="mailto:${it.email}">${it.email}</a>` : '—'}</td>
          <td>${it.telefone || '—'}</td>
          <td>
            <div class="btn-list flex-nowrap">
              <a class="btn-icon btn-teams ${tm ? '' : 'disabled'}" href="${tm ? tm : '#'}" ${tm ? 'target="_blank" rel="noopener" title="Abrir Teams" aria-label="Abrir Teams"' : 'aria-disabled="true" tabindex="-1" title="Teams indisponível" aria-label="Teams indisponível"'}>
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path d="M9.186 4.797a2.42 2.42 0 1 0-2.86-2.448h1.178c.929 0 1.682.753 1.682 1.682zm-4.295 7.738h2.613c.929 0 1.682-.753 1.682-1.682V5.58h2.783a.7.7 0 0 1 .682.716v4.294a4.197 4.197 0 0 1-4.093 4.293c-1.618-.04-3-.99-3.667-2.35Zm10.737-9.372a1.674 1.674 0 1 1-3.349 0 1.674 1.674 0 0 1 3.349 0m-2.238 9.488-.12-.002a5.2 5.2 0 0 0 .381-2.07V6.306a1.7 1.7 0 0 0-.15-.725h1.792c.39 0 .707.317.707.707v3.765a2.6 2.6 0 0 1-2.598 2.598z"/>
                  <path d="M.682 3.349h6.822c.377 0 .682.305.682.682v6.822a.68.68 0 0 1-.682.682H.682A.68.68 0 0 1 0 10.853V4.03c0-.377.305-.682.682-.682Zm5.206 2.596v-.72h-3.59v.72h1.357V9.66h.87V5.945z"/>
                </svg>
              </a>
              <a class="btn-icon btn-whatsapp ${wa ? '' : 'disabled'}" href="${wa ? wa : '#'}" ${wa ? 'target="_blank" rel="noopener" title="Abrir WhatsApp" aria-label="Abrir WhatsApp"' : 'aria-disabled="true" tabindex="-1" title="WhatsApp indisponível" aria-label="WhatsApp indisponível"'}>
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                </svg>
              </a>
              <a class="btn-icon btn-email ${it.email ? '' : 'disabled'}" href="${it.email ? 'mailto:'+it.email : '#'}" ${it.email ? 'title="Enviar E-mail" aria-label="Enviar E-mail"' : 'aria-disabled="true" tabindex="-1" title="E-mail indisponível" aria-label="E-mail indisponível"'}>
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                </svg>
              </a>
            </div>
          </td>
        </tr>`;
    }).join('');
    tbody.innerHTML = rows;
  }

  function renderPagination(p){
    if(!paginEl || !infoEl) return;
    const start = Math.min(p.offset + 1, p.total_records);
    const end   = Math.min(p.offset + p.limit, p.total_records);
    infoEl.textContent = `Mostrando ${start}–${end} de ${p.total_records} registros`;
    let html = '';
    const prevDisabled = p.current_page <= 1;
    const nextDisabled = p.current_page >= p.total_pages;
    const begin = Math.max(1, p.current_page - 2);
    const finish= Math.min(p.total_pages, p.current_page + 2);
    html += `<li class="page-item ${prevDisabled?'disabled':''}"><a class="page-link" href="#" onclick="return false;" ${prevDisabled?'':'data-page="'+(p.current_page-1)+'"'}>Anterior</a></li>`;
    for(let i=begin;i<=finish;i++){
      html += `<li class="page-item ${i===p.current_page?'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    html += `<li class="page-item ${nextDisabled?'disabled':''}"><a class="page-link" href="#" onclick="return false;" ${nextDisabled?'':'data-page="'+(p.current_page+1)+'"'}>Próxima</a></li>`;
    paginEl.innerHTML = html;
    Array.from(paginEl.querySelectorAll('a[data-page]')).forEach(a => {
      a.addEventListener('click', (ev) => {
        ev.preventDefault();
        const pNum = parseInt(a.getAttribute('data-page')||'1',10);
        doSearch(pNum);
      });
    });
  }

  function doSearch(page){
    currentPage = page || 1;
    const term = (searchInput?.value || '').trim();
    limit = parseInt(perSelect?.value || '15', 10);
    const payload = { search: term, page: currentPage, limit: limit, empresas: selectedEmpresas };
    // loading
    if(tbody){ tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3">Carregando...</td></tr>'; }
    fetch('ajax_search.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
      .then(r => r.json())
      .then(data => { if (data && data.success){ renderRows(data.colaboradores || []); renderPagination(data.pagination); } })
      .catch(err => { console.error('Busca falhou', err); })
  }

  if (searchInput){
    searchInput.addEventListener('input', () => { clearTimeout(debounceId); debounceId = setTimeout(()=>doSearch(1), 250); });
  }
  if (perSelect){
    perSelect.addEventListener('change', () => { doSearch(1); });
  }
  // Inicializa com os parâmetros atuais
  // Não força consulta se já há conteúdo; mas sincroniza paginação na próxima interação
})();
</script>
<?php endif; ?>

<?php if ($viewMode !== 'lista'): ?>
<!-- Cartão flutuante (fora do fluxo) -->
<div id="hoverCard" role="dialog" aria-hidden="true">
  <div class="brandbar"></div>
  <div class="content">
    <div class="header">
      <div class="mini-avatar"><span class="inits">??</span></div>
      <div>
        <div class="nm">Nome</div>
        <div class="sub">Cargo • Departamento</div>
      </div>
    </div>

    <div class="chips" id="chips"></div>

    <div class="actions" id="actions"></div>

    <div class="divider"></div>

    <div class="grid">
      <div class="lbl">E-mail</div>       <div class="val" id="valEmail">—</div>
      <div class="lbl">Telefone</div>     <div class="val" id="valFone">—</div>
      <div class="lbl">Ramal</div>        <div class="val" id="valRamal">—</div>
      <div class="lbl">Departamento</div> <div class="val" id="valDepto">—</div>
      <div class="lbl">Obs.</div>         <div class="val" id="valObs">—</div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($viewMode !== 'lista'): ?>
<script>
(() => {
  const stage = document.getElementById('stage');
  const svg   = document.getElementById('wires');
  const org   = document.getElementById('org');
  const card  = document.getElementById('hoverCard');

  const STROKE = getComputedStyle(document.documentElement).getPropertyValue('--stroke') || '#cbd5e1';
  const STRONG = getComputedStyle(document.documentElement).getPropertyValue('--stroke-strong') || '#b8c2d3';
  let GAP   = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--gap-x')) || 44;
  let SLOT2 = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--slot-2')) || 220;
  let MIN1  = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--min-slot-1')) || 260;
  const px = n => Math.round(n*10)/10;

  /* ===== Zoom via variáveis CSS ===== */
  const baseVars = { avatar:108, gapx:44, gapy:36, slot2:220, min1:260 };
  const zoomRange = document.getElementById('zoomRange');
  const zoomLabel = document.getElementById('zoomLabel');
  let currentZoom = parseFloat(zoomRange?.value || '1');
  let currentCompression = 1.0; // 1.0 = espaçamento padrão; <1 comprime.
  let userAdjustedZoom = false; // evita que o autofit "trave" o controle
  const urlZoomParam = new URLSearchParams(window.location.search).get('zoom');
  if (urlZoomParam !== null && urlZoomParam !== '') userAdjustedZoom = true;
  // Zoom padrão 0.80 quando nenhum parâmetro foi fornecido
  if (zoomRange && (urlZoomParam === null || urlZoomParam === '')){
    zoomRange.value = '0.80';
    currentZoom = 0.80;
    userAdjustedZoom = true; // evita o autofit alterar o zoom inicial
  }
  function applyZoom(z){
    const clamp = (v,min,max)=>Math.max(min,Math.min(max,v));
    z = clamp(z,0.6,1.6);
    // Compressão dinâmica: quanto menor o zoom, mais os espaços se comprimem.
    // Em z=1.0 => 1.0 (sem compressão). Em z=0.6 => ~0.45 (compressão forte).
    const minComp = 0.35;
    const ratio = clamp((z - 0.6) / 0.4, 0, 1); // 0 em 0.6, 1 em 1.0+
    const dynComp = minComp + (1 - minComp) * ratio;
    const spacingScale = z * dynComp * currentCompression;
    document.documentElement.style.setProperty('--avatar',        (baseVars.avatar*z)+'px');
    document.documentElement.style.setProperty('--gap-x',         (baseVars.gapx*spacingScale)+'px');
    document.documentElement.style.setProperty('--gap-y',         (baseVars.gapy*spacingScale)+'px');
    document.documentElement.style.setProperty('--slot-2',        (baseVars.slot2*spacingScale));
    document.documentElement.style.setProperty('--min-slot-1',    (baseVars.min1*spacingScale));
    document.documentElement.style.setProperty('--font-scale',    z);
    // Atualiza os números usados no layout JS para refletirem o zoom atual
    GAP  = baseVars.gapx * spacingScale;
    SLOT2= baseVars.slot2 * spacingScale;
    MIN1 = baseVars.min1 * spacingScale;
    currentZoom = z;
    if (zoomLabel) zoomLabel.textContent = (Math.round(z*100)/100).toFixed(2)+'×';
    requestAnimationFrame(redraw);
  }
  function applyCompression(c){
    const clamp = (v,min,max)=>Math.max(min,Math.min(max,v));
    currentCompression = clamp(c, 0.35, 1.0); // permite comprimir mais antes de mexer no avatar
    applyZoom(currentZoom);
  }
  if (zoomRange){
    applyZoom(parseFloat(zoomRange.value||'1')); // inicial
    zoomRange.addEventListener('input', ()=>{ userAdjustedZoom = true; applyZoom(parseFloat(zoomRange.value||'1')); });
    // Atualiza query para persistir zoom quando filtros recarregarem
    zoomRange.addEventListener('change', ()=>{
      const url = new URL(window.location.href);
      url.searchParams.set('zoom', String(zoomRange.value||'1'));
      history.replaceState(null, '', url.toString());
    });
    // Persistir o zoom padrão 0.80 na URL quando não há parâmetro
    if (urlZoomParam === null || urlZoomParam === ''){
      const url = new URL(window.location.href);
      url.searchParams.set('zoom', String(zoomRange.value||'0.80'));
      history.replaceState(null, '', url.toString());
    }
  }

  // Ajuste automático de zoom para caber os diretores na largura da viewport
  let didAutofit = false;
  function measureLevel1Width(){
    const root = org.querySelector('ul.lvl-0 details');
    const ul1 = root ? root.querySelector(':scope > ul.lvl-1') : org.querySelector('.lvl-1');
    const nodes = root ? Array.from(root.querySelectorAll(':scope > ul.lvl-1 > li > details > summary.person')) : [];
    const sw = (ul1 ? ul1.scrollWidth : org.scrollWidth) || 0;
    if (sw > 0) return sw;
    if (nodes.length){
      const left = Math.min(...nodes.map(n=>n.offsetLeft));
      const right = Math.max(...nodes.map(n=>n.offsetLeft + n.offsetWidth));
      return Math.max(0, right - left);
    }
    return org.scrollWidth || org.offsetWidth;
  }
  function maybeAutofit(force=false){
    if ((didAutofit && !force) || userAdjustedZoom) return;
    const wrap = document.querySelector('.wrap');
    const viewportW = (wrap?.clientWidth || window.innerWidth) - 16; // margem segura
    const contentW = measureLevel1Width();
      if (contentW > 0 && viewportW > 0 && contentW > viewportW){
        // Primeiro tentamos comprimir espaçamentos mantendo avatar/tipografia próximos do padrão
        if (currentZoom >= 0.9){
        const comp = Math.max(0.35, Math.min(1.0, (viewportW / contentW) * 0.96));
          if (Math.abs(comp - currentCompression) > 0.02){
            applyCompression(comp);
          }
        }
        // Se ainda não couber (ou se o zoom já for baixo), ajustamos o zoom como fallback
        const remeasure = measureLevel1Width();
        if (remeasure > viewportW){
        const z = Math.max(0.6, Math.min(1.6, (viewportW / remeasure) * 0.95));
          if (Math.abs(z - currentZoom) > 0.01){
            if (zoomRange) zoomRange.value = z.toFixed(2);
            applyZoom(z);
          }
        }
      }
    didAutofit = true;
  }

  /* ==== adorna iniciais ==== */
  function nameToHue(name){ let h=0; for(let i=0;i<name.length;i++) h=(h*31+name.charCodeAt(i))>>>0; return h%360; }
  function paintInitials(){
    document.querySelectorAll('.avatar-initials').forEach(el=>{
      const name = (el.getAttribute('data-name')||el.textContent||'').trim();
      el.style.background = `hsl(${nameToHue(name.toLowerCase())},55%,68%)`;
      el.style.color = '#0f172a';
    });
  }

  const isVisible = el => !!(el && el.getClientRects().length);

  function anchors(summary){
    const avatar = summary.querySelector('.avatar');
    const title  = summary.querySelector('.title');
    const bS = stage.getBoundingClientRect();
    const bA = avatar.getBoundingClientRect();
    const bT = title.getBoundingClientRect();
    return {
      cx: bA.left - bS.left + bA.width/2,
      cy: bA.top  - bS.top  + bA.height/2,
      r:  bA.width/2,
      bottomTitle: bT.bottom - bS.top
    };
  }

  function visibleChildren(detailsEl){
    const ul = detailsEl.querySelector(':scope > ul');
    if(!ul) return [];
    return Array.from(ul.querySelectorAll(':scope > li > details'))
                .filter(d => {
                  const sum = d.querySelector(':scope > summary');
                  if (!sum) return false;
                  // Mais robusto: ignora nós sem área visível
                  const r = sum.getBoundingClientRect();
                  const areaVisible = (r.width > 0 && r.height > 0);
                  const inFlow = sum.offsetParent !== null; // display:none, visibility:hidden, etc.
                  return areaVisible && inFlow;
                });
  }

  /* ===== largura por subárvore (bottom-up) ===== */
  function computeSubtreeWidth(details){
    const sum = details.querySelector(':scope > summary');
    const wSum = sum.getBoundingClientRect().width;

    const parentUL = details.parentElement?.parentElement;
    const isManagerLevel = parentUL && parentUL.classList.contains('lvl-1');
    const minWidth = isManagerLevel ? Math.max(MIN1, wSum) : Math.max(SLOT2, wSum);

    if (!details.open) { applyBasis(details, minWidth); return minWidth; }

    const kids = visibleChildren(details);
    if (!kids.length){ applyBasis(details, minWidth); return minWidth; }

    const widths = kids.map(k => computeSubtreeWidth(k));
    const sumChildren = widths.reduce((a,b)=>a+b,0) + GAP*(kids.length-1);

    const need = Math.max(minWidth, sumChildren);
    applyBasis(details, need);
    return need;
  }
  function applyBasis(details, w){
    const li = details.closest('li');
    if (li) li.style.flex = `0 0 ${Math.round(w)}px`;
  }

  /* ===== centragem de filho único ===== */
  function centerSingles(details){
    const ul = details.querySelector(':scope > ul'); if (!ul) return;
    ul.querySelectorAll(':scope > li').forEach(li=>{ li.style.marginLeft=''; li.style.marginRight=''; });

    if (!details.open) return;
    const kids = visibleChildren(details);
    if (kids.length === 1) {
      const li = kids[0].closest('li'); if (li){ li.style.marginLeft='auto'; li.style.marginRight='auto'; }
    }
    kids.forEach(centerSingles);
  }

  function layoutAll(){
    const rootDetails = org.querySelector('ul.lvl-0 > li > details') || org.querySelector('ul.lvl-0 details');
    if (rootDetails) computeSubtreeWidth(rootDetails);
    if (rootDetails) centerSingles(rootDetails);

    const orgRect = org.getBoundingClientRect();
    const contentW = Math.max(org.scrollWidth, orgRect.width, window.innerWidth);
    const PAD = parseFloat(getComputedStyle(stage).paddingBottom) || 0;
    const contentH = Math.max(org.scrollHeight, orgRect.height) + PAD;

    stage.style.width   = contentW + 'px';
    stage.style.height  = contentH + 'px';
    svg.setAttribute('width',  contentW);
    svg.setAttribute('height', contentH);
  }

  function clearSVG(){ while(svg.firstChild) svg.removeChild(svg.firstChild); }
  function line(x1,y1,x2,y2,strong=false, width=2.2){
    const l = document.createElementNS('http://www.w3.org/2000/svg','line');
    l.setAttribute('x1', px(x1)); l.setAttribute('y1', px(y1));
    l.setAttribute('x2', px(x2)); l.setAttribute('y2', px(y2));
    l.setAttribute('stroke', strong ? STRONG : STROKE);
    l.setAttribute('stroke-width', width);
    l.setAttribute('stroke-linecap','round');
    svg.appendChild(l);
  }
  function drawFor(parent){
    if(!parent.open) return;
    // Garante que a lista de filhos está visível (detalhes aberto)
    const ulVisible = parent.querySelector(':scope > ul');
    if (!ulVisible || ulVisible.offsetHeight < 1) return;
    const kids = visibleChildren(parent); if(!kids.length) return;

    const kA = kids.map(d => anchors(d.querySelector(':scope > summary'))).sort((a,b)=>a.cx-b.cx);
    const parentSummary = parent.querySelector(':scope > summary');
    const hasAvatarParent = !!parentSummary.querySelector('.avatar');

    if (kA.length === 1) {
      const c = kA[0];
      const pA = hasAvatarParent ? anchors(parentSummary) : null;
      const clearance = 10;
      const yTopChild = c.cy - c.r - clearance;
      if (hasAvatarParent) line(pA.cx, pA.bottomTitle + 8, pA.cx, yTopChild, true);
    } else {
      const ys = kA.map(a=>a.cy).sort((a,b)=>a-b);
      const barY = ys.length%2 ? ys[(ys.length-1)/2] : (ys[ys.length/2-1]+ys[ys.length/2])/2;

      if (hasAvatarParent) {
        const pA = anchors(parentSummary);
        line(pA.cx, pA.bottomTitle + 8, pA.cx, barY, true);
      }
      for(let i=0; i<kA.length-1; i++){
        const leftEnd  = kA[i].cx + kA[i].r + 10;
        const rightBeg = kA[i+1].cx - kA[i+1].r - 10;
        if(rightBeg > leftEnd) line(leftEnd, barY, rightBeg, barY);
      }
    }
    kids.forEach(drawFor);
  }

  function redraw(){
    paintInitials();
    layoutAll();
    clearSVG();
    const root = org.querySelector('ul.lvl-0 details');
    if(root) drawFor(root);
  }

  org.addEventListener('toggle', redraw);
  const ro = new ResizeObserver(redraw);
  ro.observe(stage);
  org.querySelectorAll('summary').forEach(el => ro.observe(el));
  // Primeiro desenho e auto ajuste de zoom para caber na tela
  requestAnimationFrame(()=>{ redraw(); requestAnimationFrame(()=>maybeAutofit(true)); });
  window.addEventListener('resize', ()=>{ redraw(); maybeAutofit(false); }, {passive:true});

  /* ===== Modo de navegação (accordion/foco) ===== */
  let navMode = '<?= e($modoNav) ?>';
  // Atualiza modo ao trocar rádio, sem recarregar
  document.querySelectorAll('#navForm input[name="modo"]').forEach(r=>{
    r.addEventListener('change', ()=>{ navMode = r.value; updateNavigation(); });
  });

  function closeSiblings(details){
    const ul = details?.parentElement?.parentElement; // li -> ul
    if (!ul) return;
    // Fecha todos os irmãos do mesmo nível
    ul.querySelectorAll(':scope > li > details').forEach(other=>{
      if (other !== details) other.open = false;
    });
  }

  org.addEventListener('toggle', (e)=>{
    const d = e.target.closest('details');
    if (!d || !d.open) return;
    if (navMode === 'foco') closeSiblings(d);
  }, { capture:true });

  /* ===== Drag to scroll ===== */
  const viewport = document.querySelector('.wrap');
  let isDown=false,startX=0,startY=0,startL=0,startT=0;
  viewport.addEventListener('mousedown', (e) => {
    if (e.target.closest('summary')) return;
    isDown = true; viewport.classList.add('dragging'); document.body.classList.add('dragging');
    startX=e.clientX; startY=e.clientY; startL=viewport.scrollLeft; startT=viewport.scrollTop; e.preventDefault();
  });
  window.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    const dx = e.clientX-startX, dy = e.clientY-startY;
    viewport.scrollLeft = startL - dx; viewport.scrollTop = startT - dy;
  });
  window.addEventListener('mouseup', () => { if(!isDown) return; isDown=false; viewport.classList.remove('dragging'); document.body.classList.remove('dragging'); });

  /* ===== Cartão flutuante (hover) ===== */
  const brandMap = (empresaRaw)=>{
    const s = (empresaRaw||'').toLowerCase();
    if (s.includes('barão') || s.includes('barao')) return 'brand--barao';
    if (s.includes('toy'))                          return 'brand--toymania';
    if (s.includes('alfa'))                         return 'brand--alfaness';
    if (s.includes('fun'))                          return 'brand--fun';
    return ''; // padrão
  };

  function showCardFor(summary){
    const d = summary.dataset;
    // Cabeçalho / avatar mini
    const brandbar = card.querySelector('.brandbar');
    brandbar.className = 'brandbar ' + brandMap(d.empresa);

    const mini = card.querySelector('.mini-avatar');
    mini.innerHTML = '';
    if (d.foto){
      const im = new Image(); im.src = d.foto; im.alt='';
      mini.appendChild(im);
    } else {
      const s = document.createElement('div');
      s.className='inits'; s.style.fontSize='22px';
      s.textContent = (d.nome||'??').split(' ').map(x=>x[0]).slice(0,2).join('').toUpperCase();
      mini.appendChild(s);
      mini.style.background = `hsl(${nameToHue((d.nome||'').toLowerCase())},55%,68%)`;
    }

    // Título
    card.querySelector('.nm').textContent  = d.nome || '—';
    card.querySelector('.sub').textContent = [d.cargo||'—', d.departamento||'—'].join(' • ');

    // Chips
    const chips = card.querySelector('#chips'); chips.innerHTML = '';
    if (d.empresa)      chips.append(childChip(d.empresa));
    if (d.tipo)         chips.append(childChip(d.tipo));
    if (d.admissao)     chips.append(childChip(formatDateBr(d.admissao),'📅'));

    // Ações
    const acts = card.querySelector('#actions'); acts.innerHTML = '';
    if (d.email)   acts.append(actionBtn('mailto:'+d.email, '✉️', 'E-mail'));
    if (d.telefone){
      acts.append(whatsappBtn(waFromPhone(d.telefone)));
    }
    if (d.teams)   acts.append(actionBtn(d.teams, '💬', 'Teams'));

    // Detalhes
    setVal('#valEmail', d.email);
    setVal('#valFone',  d.telefone);
    setVal('#valRamal', d.ramal);
    setVal('#valDepto', d.departamento);
    setVal('#valObs',   d.obs);

    // Posicionamento: calcula lado com mais espaço
    placeCardNear(summary);

    card.style.display = 'block';
  }

  function childChip(text, icon){
    const s = document.createElement('span'); s.className='chip';
    if (icon){ s.textContent = icon+' '+text; } else { s.textContent = text; }
    return s;
  }
  function actionBtn(href, icon, label, extraClass){
    const a = document.createElement('a'); a.className='btn-mini' + (extraClass?(' '+extraClass):''); a.href = href; a.target = (href.startsWith('http')?'_blank':'_self'); a.rel='noopener';
    if (icon){ const i=document.createElement('span'); i.className='ico'; i.textContent=icon; a.appendChild(i); }
    a.appendChild(document.createTextNode(' '+label));
    return a;
  }
  function setVal(sel, val){ card.querySelector(sel).textContent = val && val.trim() !== '' ? val : '—'; }
  function onlyNums(s){ return (s||'').replace(/\D+/g,''); }
  function waFromPhone(s){
    const num = onlyNums(s||'');
    const withCC = num.startsWith('55') ? num : ('55'+num);
    return 'https://wa.me/'+withCC;
  }
  function whatsappIconEl(){
    const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.setAttribute('viewBox','0 0 24 24');
    const circle = document.createElementNS('http://www.w3.org/2000/svg','circle');
    circle.setAttribute('cx','12'); circle.setAttribute('cy','12'); circle.setAttribute('r','10'); circle.setAttribute('fill','#25D366');
    const path = document.createElementNS('http://www.w3.org/2000/svg','path');
    path.setAttribute('fill','#fff');
    path.setAttribute('d','M16.2 15.1c-.3.3-.9.8-1.6.9-.7.2-1.6.2-2.7-.3-1.2-.5-2.6-1.6-3.8-3-1.2-1.4-2-2.8-2.4-4-.4-1.2-.4-2.1-.2-2.8.2-.7.6-1.2.9-1.5.3-.3.6-.3.8-.3h.6c.2 0 .5.1.7.5l1 2c.1.3.1.5 0 .8l-.5.8c-.1.2-.1.4 0 .6.3.6.8 1.4 1.6 2.1.8.7 1.5 1.1 2.1 1.4.2.1.4.1.6 0l.9-.4c.3-.1.6 0 .8.1l1.8 1.1c.3.2.4.4.4.6 0 .2-.1.5-.3.7z');
    svg.appendChild(circle); svg.appendChild(path);
    return svg;
  }
  function whatsappBtn(href){
    const a = document.createElement('a'); a.className='btn-mini btn-whatsapp'; a.href=href; a.target='_blank'; a.rel='noopener';
    a.appendChild(whatsappIconEl());
    a.appendChild(document.createTextNode(' WhatsApp'));
    return a;
  }
  function formatDateBr(iso){ // yyyy-mm-dd -> dd/mm/yyyy
    if(!iso) return '';
    const t = iso.split('-'); return (t.length===3)? `${t[2]}/${t[1]}/${t[0]}` : iso;
  }

  function placeCardNear(summary){
    const rect = summary.getBoundingClientRect();
    const vw = window.innerWidth, vh = window.innerHeight;

    const gap = 14;               // espaço entre avatar e cartão
    const cw  = card.offsetWidth || 380;
    const ch  = card.offsetHeight || 320;

    // Preferência: se há mais espaço à direita, abre à direita
    const spaceRight = vw - rect.right;
    const spaceLeft  = rect.left;

    let left, top, sideClass;
    if (spaceRight >= cw + gap || spaceRight >= spaceLeft) {
      // direita
      left = Math.min(vw - cw - 12, rect.right + gap);
      sideClass = 'at-right';
    } else {
      // esquerda
      left = Math.max(12, rect.left - cw - gap);
      sideClass = 'at-left';
    }

    // centraliza verticalmente no avatar, ajustando para caber na viewport
    top = rect.top + rect.height/2 - ch/2;
    top = Math.max(12, Math.min(vh - ch - 12, top));

    card.style.left = left + 'px';
    card.style.top  = top  + 'px';
    card.classList.remove('at-left','at-right');
    card.classList.add(sideClass);
  }

  // Mostrar/ocultar no hover do avatar (summary .avatar)
  let hideTimer = null;
  org.addEventListener('mouseenter', (e)=>{
    const sum = e.target.closest('summary.person');
    if (!sum) return;
    if (hideTimer){ clearTimeout(hideTimer); hideTimer=null; }
    showCardFor(sum);
  }, true);

  // Reposiciona ao mover o mouse sobre o summary
  org.addEventListener('mousemove', (e)=>{
    const sum = e.target.closest('summary.person');
    if (!sum || card.style.display!=='block') return;
    placeCardNear(sum);
  }, true);

  // Esconde quando sai do summary e do cartão
  function scheduleHide(){ if(hideTimer) clearTimeout(hideTimer); hideTimer = setTimeout(()=>{ card.style.display='none'; }, 150); }
  org.addEventListener('mouseleave', (e)=>{ if(e.target.matches('summary.person')) scheduleHide(); }, true);
  card.addEventListener('mouseenter', ()=>{ if(hideTimer){ clearTimeout(hideTimer); hideTimer=null; }});
  card.addEventListener('mouseleave', scheduleHide);

  /* ===== Selecionar tudo (empresas) ===== */
  const chkTodos = document.getElementById('chkTodos');
  const chks = Array.from(document.querySelectorAll('#navForm .chkEmpresa'));
  function updateNavigation(){
    const url = new URL(window.location.href);
    // Limpa empresas[] atuais
    url.searchParams.delete('empresas[]');
    // Empresas: calcula seleção atual e só usa "todos" se todas ou nenhuma estiver marcada
    const selected = chks.filter(c=>c.checked).map(c=>c.value);
    const allChecked = selected.length === chks.length;
    if (allChecked || selected.length === 0){
      url.searchParams.append('empresas[]','todos');
    } else {
      selected.forEach(v=> url.searchParams.append('empresas[]', v));
    }
    // Modo
    const mode = (document.querySelector('#navForm input[name="modo"]:checked')?.value)||'foco';
    url.searchParams.set('modo', mode);
    // Zoom
    url.searchParams.set('zoom', String(zoomRange?.value||'1'));
    // Preserva estado do painel para reabrir após recarregar
    try{ localStorage.setItem('navPanel','open'); }catch(e){}
    window.location.href = url.toString();
  }
  if (chkTodos){
    chkTodos.addEventListener('change', ()=>{
      const on = chkTodos.checked; chks.forEach(c=>{ c.checked = on; }); updateNavigation();
    });
  }
  chks.forEach(c=> c.addEventListener('change', ()=>{
    // Ajusta "Selecionar tudo" conforme estado atual
    if (chkTodos){ chkTodos.checked = chks.every(c=> c.checked); }
    updateNavigation();
  }));

  // Ocultar/mostrar painel
  const panel = document.getElementById('navPanel');
  const hideBtn = document.getElementById('hidePanelBtn');
  const toggleWrap = document.getElementById('navToggleWrap');
  const toggleBtn = document.getElementById('navToggle');
  const toggleLabel = document.getElementById('navToggleLabel');
  function hidePanel(){
    panel.style.display='none';
    if (toggleWrap){ toggleWrap.style.display='inline-flex'; }
    if (toggleBtn){ toggleBtn.classList.add('attn'); }
    if (toggleLabel){ toggleLabel.classList.add('attn'); }
    prepareFixed(toggleWrap);
    try{ localStorage.setItem('navPanel','hidden'); }catch(e){}
  }
  function showPanel(){
    panel.style.display='block';
    if (toggleWrap){ toggleWrap.style.display='none'; }
    if (toggleBtn){ toggleBtn.classList.remove('attn'); }
    if (toggleLabel){ toggleLabel.classList.remove('attn'); }
    prepareFixed(panel);
    try{ localStorage.setItem('navPanel','open'); }catch(e){}
  }
  if (hideBtn) hideBtn.addEventListener('click', hidePanel);
  if (toggleBtn) toggleBtn.addEventListener('click', showPanel);
  if (toggleLabel) toggleLabel.addEventListener('click', showPanel);

  // Estado inicial: respeita estado persistido
  let initial = 'open';
  try{ initial = localStorage.getItem('navPanel') || 'open'; }catch(e){}
  if (initial === 'hidden'){
    hidePanel();
    if (toggleBtn) toggleBtn.classList.add('attn');
    if (toggleLabel) toggleLabel.classList.add('attn');
  } else {
    showPanel();
  }

  // Pulo periódico para chamar atenção quando minimizado
  setInterval(()=>{
    if (panel && panel.style.display==='none' && toggleWrap){
      toggleWrap.classList.add('jump');
      setTimeout(()=>toggleWrap.classList.remove('jump'), 900);
    }
  }, 18000);

  /* ===== Arrastar painel e ícone ===== */
  function prepareFixed(el){
    if (!el || el.style.display==='none') return;
    const rect = el.getBoundingClientRect();
    el.style.left = rect.left + 'px';
    el.style.top  = rect.top  + 'px';
    el.style.right = '';
    el.style.bottom = '';
    el.style.position = 'fixed';
  }
  function makeDraggable(el, handle){
    if (!el || !handle) return;
    let isDown=false, startX=0, startY=0, startL=0, startT=0;
    const clamp=(v,min,max)=>Math.max(min,Math.min(max,v));
    const onDown=(e)=>{
      const p = e.touches ? e.touches[0] : e;
      isDown=true; e.preventDefault(); e.stopPropagation();
      const rect = el.getBoundingClientRect();
      startX=p.clientX; startY=p.clientY; startL=rect.left; startT=rect.top;
      document.body.classList.add('dragging');
    };
    const onMove=(e)=>{
      if (!isDown) return;
      const p = e.touches ? e.touches[0] : e;
      const dx=p.clientX-startX, dy=p.clientY-startY;
      const vw=window.innerWidth, vh=window.innerHeight;
      const w=el.offsetWidth, h=el.offsetHeight;
      const left = clamp(startL+dx, 8, vw-w-8);
      const top  = clamp(startT+dy, 8, vh-h-8);
      el.style.left = left+'px';
      el.style.top  = top+'px';
      el.style.right='';
    };
    const onUp=()=>{ if(!isDown) return; isDown=false; document.body.classList.remove('dragging'); };
    handle.addEventListener('mousedown', onDown);
    handle.addEventListener('touchstart', onDown, {passive:false});
    window.addEventListener('mousemove', onMove);
    window.addEventListener('touchmove', onMove, {passive:false});
    window.addEventListener('mouseup', onUp);
    window.addEventListener('touchend', onUp);
  }
  // preparar posições iniciais e ativar drag
  // Panel fica oculto inicialmente; posicionar contêiner do botão+label
  prepareFixed(toggleWrap);
  const navHeader = document.getElementById('navHeader');
  makeDraggable(panel, navHeader || panel);
  // contêiner do botão/label é arrastável
  makeDraggable(toggleWrap, toggleWrap);
})();
</script>
<?php endif; ?>
<footer class="site-footer">Desenvolvido internamente • Equipe de TI Grupo Barão • 2025</footer>
</body>
</html>
