<?php
// index.php — Organograma com cartão “vidro fosco” no hover
$requiredAccess = ['Organograma', 'Visualizar Organograma'];
require_once 'check_access.php';
/* ========= Conexão ========= */
$base = __DIR__;
if (!file_exists($base . '/config.php')) {
  die('Crie config.php no mesmo diretório com $conn.');
}
require_once $base . '/config.php';

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
<title>Organograma • Portal</title>
<script>
  (function(){
    try {
      var t = localStorage.getItem('theme') || 'system';
      if(t !== 'system') document.documentElement.setAttribute('data-theme', t);
    } catch(e){}
  })();
</script>
<?php if (($viewMode ?? 'org') === 'lista'): ?>
  <!-- Tabler CSS (apenas na visualização Lista) -->
  <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
<?php endif; ?>
<style>
  :root {
    --avatar: 108px;
    --gap-x: 44px;
    --gap-y: 36px;
    --min-slot-1: 260;
    --slot-2: 220;
    --pad-bottom: 120px;
    --footer-h: 0px;
    --font-scale: 1;

    /* Base - Light Theme Variables */
    --stroke: #cbd5e1;
    --stroke-strong: #94a3b8;
    --ring-green: #22c55e;
    --ring-gray: #cbd5e1;
    --bg-main: #f8fafc;
    --text-main: #0f172a;
    --text-muted: #6b7280;
    --accent: #0ea5e9;
    --accent-2: #38bdf8;
    
    --card-bg: rgba(255, 255, 255, 0.85);
    --card-border: rgba(15, 23, 42, 0.1);
    --card-text: #0f172a;
    --card-sub: #475569;
    --card-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
  }

  /* Dark Theme Variables */
  :root[data-theme="dark"] {
    --stroke: rgba(56, 189, 248, 0.3);
    --stroke-strong: rgba(129, 140, 248, 0.6);
    --ring-gray: rgba(255,255,255,0.15);
    --bg-main: #020617;
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
    --card-bg: rgba(15, 23, 42, 0.75);
    --card-border: rgba(255, 255, 255, 0.1);
    --card-text: #f8fafc;
    --card-sub: #cbd5e1;
    --card-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
  }

  /* System Theme fallback via media query */
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --stroke: rgba(56, 189, 248, 0.3);
        --stroke-strong: rgba(129, 140, 248, 0.6);
        --ring-gray: rgba(255,255,255,0.15);
        --bg-main: #020617;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --card-bg: rgba(15, 23, 42, 0.75);
        --card-border: rgba(255, 255, 255, 0.1);
        --card-text: #f8fafc;
        --card-sub: #cbd5e1;
        --card-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
    }
  }

  *{box-sizing:border-box}
  html,body{ margin:0; background:var(--bg-main); color:var(--text-main); overflow:hidden;
    font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; transition: background 0.3s, color 0.3s; }

  /* Ambient Animated Orbs */
  .ambient-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.35;
    animation: float 20s infinite ease-in-out alternate;
    z-index: 0;
    pointer-events: none;
    transition: opacity 0.5s;
  }
  :root:not([data-theme="dark"]) .ambient-orb {
      opacity: 0.15; /* Mais suave no tema claro */
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .ambient-orb { opacity: 0.35; }
  }

  /* Ambient Animated Orbs */
  .ambient-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.35;
    animation: float 20s infinite ease-in-out alternate;
    z-index: 0;
    pointer-events: none;
  }
  .orb-1 {
    width: 60vw; height: 60vw;
    background: radial-gradient(circle, var(--accent-2) 0%, transparent 70%);
    top: -20vh; left: -10vw;
    animation-delay: -5s;
  }
  .orb-2 {
    width: 50vw; height: 50vw;
    background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
    bottom: -15vh; right: -5vw;
    animation-duration: 25s;
  }

  @keyframes float {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(10vw, 5vh) scale(1.2); }
  }

  /* viewport arrastável */
  .wrap{ width:100vw; height:100vh; overflow:auto; padding-top: 20px; cursor:grab; position:relative; z-index:1; }
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
  summary{ list-style:none; cursor:pointer; position:relative; z-index:2; }
  summary::-webkit-details-marker{ display:none }

  .person{ display:flex; flex-direction:column; align-items:center; padding:6px 10px 0; margin:0 auto; max-width:max(var(--avatar), calc(var(--min-slot-1) * 1px)); }
  
  /* Avatares com borda adaptativa */
  .avatar{ width:var(--avatar); height:var(--avatar); border-radius:50%; overflow:hidden; background:var(--card-bg);
           box-shadow:0 0 0 3px var(--bg-main), 0 6px 16px rgba(0,0,0,.15); display:flex; align-items:center; justify-content:center; transition: box-shadow 0.3s; }
  /* ANÉIS */
  details.expandable:not([open]) > summary .avatar{ box-shadow:0 0 0 3px var(--bg-main), 0 0 0 6px var(--ring-green), 0 8px 18px rgba(0,0,0,.2); }
  details.expandable[open] > summary .avatar,
  details:not(.expandable) > summary .avatar{ box-shadow:0 0 0 3px var(--bg-main), 0 0 0 6px var(--ring-gray), 0 8px 18px rgba(0,0,0,.2); }

  .avatar img{ width:100%; height:100%; object-fit:cover; display:block }
  .avatar-initials{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:800; font-size: calc(24px * var(--font-scale)); color:var(--text-main); }
  .name{ font-size: calc(1.28rem * var(--font-scale)); font-weight:800; margin-top:10px; line-height:1.15; max-width:260px; color:var(--text-main); text-shadow:0 2px 4px rgba(0,0,0,0.1); transition: color 0.3s; }
  .title{ color:var(--text-muted); font-size: calc(.98rem * var(--font-scale)); margin-top:2px; max-width:260px; transition: color 0.3s; }

  /* ==============================================================
     NOVO DESIGN DO CARTÃO FLUTUANTE (VISION OS LIKE / COM ABAS)
     ============================================================== */
  #hoverCard{
    position:fixed; z-index:90; width:min(380px, calc(100vw - 24px));
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    background: var(--card-bg);
    border-radius:20px; 
    border:1px solid var(--card-border);
    box-shadow: var(--card-shadow), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    display:none;
    overflow: hidden;
    transform: translateY(10px) scale(0.98);
    opacity: 0;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease, background 0.3s, border 0.3s;
  }
  #hoverCard.show{
    transform: translateY(0) scale(1);
    opacity: 1;
  }
  
  /* Gradiente animado dinâmico na parte superior do cartão */
  #hoverCard .brandbar {
    height: 85px;
    background: linear-gradient(135deg, #64748b, #94a3b8);
    background-size: 200% 200%;
    animation: gradientShift 4s ease infinite;
  }
  @keyframes gradientShift { 0%{ background-position:0% 50% } 50%{ background-position:100% 50% } 100%{ background-position:0% 50% } }
  
  .brand--barao    { background: linear-gradient(135deg,#2563eb,#38bdf8) !important; animation: gradientShift 6s ease infinite !important; }
  .brand--toymania { background: linear-gradient(135deg,#7c3aed,#c084fc) !important; animation: gradientShift 6s ease infinite !important; }
  .brand--alfaness { background: linear-gradient(135deg,#111827,#4b5563) !important; animation: gradientShift 6s ease infinite !important; }
  .brand--fun      { background: linear-gradient(135deg,#eab308,#fde047) !important; animation: gradientShift 6s ease infinite !important; }

  #hoverCard .content{ padding:0 20px 20px 20px; }
  #hoverCard .header{
    display:flex; flex-direction: column; align-items:center; margin-top:-40px; text-align:center;
  }
  
  #hoverCard .mini-avatar{
    width:80px; height:80px; border-radius:50%;
    border: 3px solid var(--card-bg);
    box-shadow: 0 8px 24px rgba(0,0,0, 0.15);
    overflow:hidden; background:#e5e7eb; display:flex; align-items:center; justify-content:center;
    margin-bottom: 12px; transition: border-color 0.3s;
  }
  #hoverCard .mini-avatar img{ width:100%; height:100%; object-fit:cover; display:block; }
  #hoverCard .mini-avatar .inits{ font-weight:800; font-size: 1.5rem; color:#0f172a; }

  #hoverCard .nm{ font-weight:800; font-size:1.15rem; line-height:1.2; color: var(--card-text); transition: color 0.3s; }
  #hoverCard .sub{ color:var(--card-sub); font-size:.9rem; margin-top:4px; font-weight: 500; transition: color 0.3s; }

  /* Sistema de Abas internas do Card */
  #hoverCard .tabs-nav {
    display: flex; gap: 4px; margin: 16px 0 10px; background: rgba(15, 23, 42, 0.05); padding: 4px; border-radius: 12px;
  }
  :root[data-theme="dark"] #hoverCard .tabs-nav { background: rgba(0,0,0, 0.2); }
  @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) #hoverCard .tabs-nav { background: rgba(0,0,0, 0.2); } }

  #hoverCard .tabs-nav button {
    flex: 1; background: transparent; border: none; padding: 6px 0; font-size: 0.82rem; font-weight: 600; color: var(--card-sub);
    border-radius: 8px; cursor: pointer; transition: all 0.2s;
  }
  #hoverCard .tabs-nav button.active {
    background: var(--card-bg); color: var(--card-text); box-shadow: 0 2px 8px rgba(0,0,0, 0.1);
  }
  #hoverCard .tab-pane { display: none; margin-top: 10px; animation: fadeIn 0.2s ease; }
  #hoverCard .tab-pane.active { display: block; }

  @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

  /* Info Grid */
  #hoverCard .grid{
    display:grid; grid-template-columns: 36px 1fr; gap:10px 12px; font-size:.88rem; align-items: center; color:var(--card-text);
  }
  #hoverCard .grid .icon-wrap {
    width: 32px; height: 32px; background: rgba(15, 23, 42, 0.04); border-radius: 8px;
    display: flex; align-items: center; justify-content: center; color: var(--card-sub);
  }
  :root[data-theme="dark"] #hoverCard .grid .icon-wrap { background: rgba(255,255,255,0.05); }
  @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) #hoverCard .grid .icon-wrap { background: rgba(255,255,255,0.05); } }

  #hoverCard .grid .icon-wrap svg { width: 16px; height: 16px; }
  #hoverCard .grid-text { display: flex; flex-direction: column; }
  #hoverCard .grid-text span.lbl { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 700; margin-bottom: 2px; }

  /* Botões de Contato Premium */
  #hoverCard .actions-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px; mt: 10px;
  }
  .btn-premium {
    display:flex; flex-direction: column; align-items:center; justify-content: center; gap:4px;
    padding:10px 4px; border-radius:12px; font-weight:600; font-size: 0.82rem;
    background:var(--card-bg); color:var(--card-text); border: 1px solid var(--card-border);
    text-decoration:none;
    box-shadow: 0 4px 12px rgba(0,0,0, 0.05); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0, 0.15); filter: brightness(1.05); }
  .btn-premium .ico-wrapper { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 2px; color: #fff; }
  .btn-premium.btn-tel .ico-wrapper { background: linear-gradient(135deg, #10b981, #059669); }
  .btn-premium.btn-wa .ico-wrapper  { background: linear-gradient(135deg, #4ade80, #16a34a); box-shadow: 0 4px 10px rgba(34, 197, 94, 0.3); }
  .btn-premium.btn-tm .ico-wrapper  { background: linear-gradient(135deg, #818cf8, #4f46e5); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
  .btn-premium.btn-em .ico-wrapper  { background: linear-gradient(135deg, #38bdf8, #0284c7); }
  .btn-premium svg { width: 16px; height: 16px; }
  
  .chip-tag { display: inline-flex; font-size: 0.75rem; background: rgba(14, 165, 233, 0.1); color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(14, 165, 233, 0.2); margin-top: 6px; }

  /* EFEITO STAGGERED (ENTRADA DO ORGANOGRAMA) */
  .org ul li {
    opacity: 0;
    transform: translateY(-10px);
    animation: slideDownIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  }
  @keyframes slideDownIn { to { opacity: 1; transform: translateY(0); } }
  
  /* Lógica de Delay no CSS */
  .org .lvl-0 > li { animation-delay: 0.1s; }
  .org .lvl-1 > li { animation-delay: 0.25s; }
  .org .lvl-2 > li { animation-delay: 0.4s; }
  .org .lvl-3 > li { animation-delay: 0.55s; }
  .org .lvl-4 > li { animation-delay: 0.7s; }

  /* SPOTLIGHT SEARCH MODAL */
  #spotlight-overlay {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9999; display: none; align-items: flex-start; justify-content: center; padding-top: 12vh;
    opacity: 0; transition: opacity 0.2s;
  }
  #spotlight-overlay.active { display: flex; opacity: 1; }
  #spotlight-modal {
    width: 90%; max-width: 560px; background: rgba(255, 255, 255, 0.95); border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
    overflow: hidden; transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  #spotlight-overlay.active #spotlight-modal { transform: scale(1); }
  
  .spotlight-header {
    display: flex; align-items: center; padding: 0 20px; border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  }
  .spotlight-header svg { width: 20px; height: 20px; color: #64748b; }
  .spotlight-header input {
    width: 100%; border: none; background: transparent; padding: 20px 16px; font-size: 1.1rem;
    color: #0f172a; outline: none; font-weight: 500;
  }
  .spotlight-header input::placeholder { color: #94a3b8; }
  .spotlight-footer {
    display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;
    background: #f8fafc; border-top: 1px solid rgba(15, 23, 42, 0.05); font-size: 0.75rem; color: #64748b;
  }
  .spotlight-kbd { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; }
  
  #spotlight-results { max-height: 320px; overflow-y: auto; }
  .spotlight-item {
    display: flex; align-items: center; padding: 12px 20px; gap: 14px; cursor: pointer; border-bottom: 1px solid rgba(15, 23, 42, 0.04);
  }
  .spotlight-item:hover, .spotlight-item.selected { background: #f1f5f9; }
  .spotlight-item:last-child { border-bottom: none; }
  .spotlight-item .ava { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; background: #e2e8f0; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;}
  .spotlight-item .info { display: flex; flex-direction: column; }
  .spotlight-item .info strong { color: #0f172a; font-size: 0.95rem; }
  .spotlight-item .info span { color: #64748b; font-size: 0.8rem; }

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
  <a href="index.php?view=org&<?= $qsCommon ?>" class="tab <?= $viewMode==='org'?'active':'' ?>">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
    Organograma
  </a>
  <a href="index.php?view=lista&<?= $qsCommon ?>" class="tab <?= $viewMode==='lista'?'active':'' ?>">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
    Lista
  </a>
  <a href="export_xlsx.php?<?= $qsCommon ?><?= !empty($qTerm) ? '&q='.urlencode($qTerm) : '' ?>" class="tab tab-download" title="Baixar planilha">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
      <polyline points="7 10 12 15 17 10"></polyline>
      <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>
    Exportar
  </a>
  <a href="logout.php" class="tab tab-logout" title="Sair">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
    Sair
  </a>
  
  <div style="width: 1px; height: 24px; background: var(--stroke); margin: 0 4px;"></div>
  
  <button id="themeToggleBtn" class="tab" title="Alternar Tema" style="padding: 8px; border-radius: 50%;">
      <svg id="themeIcon" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
  </button>
</div>

<script>
  (function(){
    const btn = document.getElementById('themeToggleBtn');
    const icon = document.getElementById('themeIcon');
    const root = document.documentElement;
    
    function updateIcon(t) {
        if(t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
        } else {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';
        }
    }
    
    let currentTheme = localStorage.getItem('theme') || 'system';
    updateIcon(currentTheme);
    
    btn.addEventListener('click', () => {
        const isDark = root.getAttribute('data-theme') === 'dark' || 
                      (root.getAttribute('data-theme')!== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        const newTheme = isDark ? 'light' : 'dark';
        root.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme);
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if((localStorage.getItem('theme') || 'system') === 'system') {
            updateIcon('system');
        }
    });

  })();
</script>

<style>
  .tab{ display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:999px; border:1px solid var(--card-border); background:var(--card-bg); color:var(--text-main); font-weight:600; font-size:0.9rem; text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.08); transition: all 0.2s; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); cursor: pointer; }
  .tab:hover{ transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.15); }
  .tab.active{ background:var(--accent); color:#fff; border-color:var(--accent); }
  .tab-logout{ background:#ef4444; color:#fff; border-color:#ef4444; }
  .tab-logout:hover{ filter: brightness(1.1); background:#ef4444; color:#fff; }
  .tab-download{ background:#10b981; color:#fff; border-color:#10b981; }
  .tab-download:hover{ filter: brightness(1.1); background:#10b981; color:#fff; }
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

  /* Rodapé do site e Body */
  body{ padding-bottom: 0px; }

  /* Painel de Navegação Lateral */
  #navPanel form {
    background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px;
    padding: 16px; box-shadow: var(--card-shadow); font-family: inherit; color: var(--text-main);
    min-width: 260px; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
  }
  #navHeader { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:12px; cursor:move; font-weight:800; color: var(--text-main); }
  #navHeader button { border:none; background:transparent; font-size:18px; cursor:pointer; color: var(--text-main); }
  .nav-group-title { font-weight:700; margin:12px 0 8px; font-size: 0.95rem; color: var(--text-main); }
  .nav-label-text { font-size:.9rem; color: var(--card-sub); font-weight: 500;}
  .nav-checkbox-list { display:flex; flex-direction:column; gap:8px; font-size:.92rem; color: var(--card-text); }
  .nav-checkbox-list label { display: flex; align-items: center; gap: 6px; cursor: pointer; }

  /* Botão de toggle do painel (minimizado) */
  .navToggleWrap{ position:fixed; right:16px; top:16px; z-index:61; display:inline-flex; align-items:center; gap:8px; }
  #navToggle{ position:relative; width:46px; height:46px; border-radius:12px; border:1px solid var(--accent); background:var(--accent); color:#fff; box-shadow:0 10px 24px rgba(14,165,233,.35); cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
  #navToggle svg{ width:24px; height:24px; display:block; }
  #navToggle.attn{ animation:pulseGlow 2s ease-in-out infinite; }
  .navToggleWrap.jump{ animation:jump 0.9s ease; }
  #navToggleLabel{ background:var(--accent); color:#fff; font-weight:600; padding:6px 10px; border-radius:10px; border:1px solid rgba(15,23,42,.18); box-shadow:0 10px 24px rgba(14,165,233,.25); cursor: pointer; }
  #navToggleLabel.attn{ animation:pulseGlow 2s ease-in-out infinite; }
  @keyframes pulseGlow{ 0%{ box-shadow:0 0 0 0 rgba(14,165,233,.6); } 50%{ box-shadow:0 0 0 12px rgba(14,165,233,.0); } 100%{ box-shadow:0 0 0 0 rgba(14,165,233,.0); } }
  @keyframes jump{ 0%{ transform:translateY(0) } 20%{ transform:translateY(-8px) } 40%{ transform:translateY(0) } 60%{ transform:translateY(-4px) } 80%{ transform:translateY(0) } 100%{ transform:translateY(0) } }
</style>
</head>
<body>
  <!-- Ambient Background -->
  <div class="ambient-orb orb-1"></div>
  <div class="ambient-orb orb-2"></div>
<?php if ($viewMode !== 'lista'): ?>
<!-- Painel de navegação (canto direito) -->
<div id="navToggleWrap" class="navToggleWrap" aria-label="Botão Navegação">
  <button id="navToggle" title="Mostrar painel" aria-label="Mostrar painel">
    <!-- Ícone de paralelepípedo (cubóide) -->
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <polygon points="4,8 14,5 20,8 10,11" fill="#fff" opacity="0.95"></polygon>
      <polygon points="4,8 4,18 10,21 10,11" fill="#e0f2fe"></polygon>
      <polygon points="10,11 20,8 20,18 10,21" fill="#bae6fd"></polygon>
    </svg>
  </button>
  <div id="navToggleLabel">Navegação</div>
</div>
<div id="navPanel" style="position:fixed; right:16px; top:16px; z-index:60;">
  <form id="navForm" method="get" action="index.php">
    <div id="navHeader">
      <div>Navegação</div>
      <button type="button" id="hidePanelBtn" title="Ocultar painel">✕</button>
    </div>
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
      <label class="nav-label-text">Zoom</label>
      <input type="range" id="zoomRange" name="zoom" min="0.6" max="1.6" step="0.05" value="<?= htmlspecialchars((string)max(0.6,min(1.6,$zoomParam))) ?>" style="flex:1">
      <span id="zoomLabel" class="nav-label-text" style="width:40px; text-align:right;">1.00×</span>
    </div>
    <div class="nav-group-title">Empresas</div>
    <div class="nav-checkbox-list">
      <?php
        $sel = array_map('strtolower', $selectedEmpresas);
        $isTodos = in_array('todos', $sel) || empty($sel);
        $opts = ['Barão','Toymania','Alfaness'];
      ?>
      <label><input type="checkbox" id="chkTodos" name="empresas[]" value="todos" <?= $isTodos ? 'checked' : '' ?>> Selecionar tudo</label>
      <?php foreach($opts as $op): $ck = $isTodos || in_array(strtolower($op), $sel); ?>
        <label><input type="checkbox" class="chkEmpresa" name="empresas[]" value="<?= e($op) ?>" <?= $ck ? 'checked' : '' ?>> <?= e($op) ?></label>
      <?php endforeach; ?>
      <label style="opacity:.6"><input type="checkbox" checked disabled> Grupo Barão (sempre fixa)</label>
    </div>
    <div class="nav-group-title">Modo de expansão</div>
    <div class="nav-checkbox-list">
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
  
  <!-- SPOTLIGHT OVERLAY -->
  <div id="spotlight-overlay">
    <div id="spotlight-modal">
        <div class="spotlight-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" id="spotlight-input" placeholder="Buscar no Organograma..." autocomplete="off">
        </div>
        <div id="spotlight-results">
            <!-- Resultados renderizados via JS -->
        </div>
        <div class="spotlight-footer">
            <span>Use as setas <span class="spotlight-kbd">↑</span> <span class="spotlight-kbd">↓</span> para navegar, e <span class="spotlight-kbd">Enter</span> para ir até o colaborador.</span>
            <span><span class="spotlight-kbd">Esc</span> para fechar</span>
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

<!-- Cartão flutuante (fora do fluxo) -->
<div id="hoverCard" role="dialog" aria-hidden="true">
  <div class="brandbar"></div>
  <div class="content">
    <div class="header">
      <div class="mini-avatar"><span class="inits">??</span></div>
      <div class="nm">Nome</div>
      <div class="sub">Cargo • Departamento</div>
      
      <div class="tabs-nav">
        <button type="button" class="active" data-tab="tab-sobre">Sobre</button>
        <button type="button" data-tab="tab-contato">Contato</button>
      </div>
    </div>

    <!-- Aba Sobre (Chips + Observações Básicas) -->
    <div id="tab-sobre" class="tab-pane active">
        <div class="chips" id="chips"></div>
        <div class="divider"></div>
        <div class="grid">
            <div class="icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6.75h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <div class="grid-text">
                <span class="lbl">Depto / Unidade</span>
                <span id="valDepto" style="font-weight:600">—</span>
            </div>
            
            <div class="icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>
            <div class="grid-text">
                <span class="lbl">Info Extra</span>
                <span id="valObs">—</span>
            </div>
        </div>
    </div>

    <!-- Aba Contato (Grid Pessoal) -->
    <div id="tab-contato" class="tab-pane">
        <div class="actions-grid" id="actions-grid">
            <!-- Renderizado via JS -->
        </div>
        <div class="divider"></div>
        <div class="grid">
            <div class="icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.896-1.596-5.496-4.196-7.092-7.092l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                </svg>
            </div>
            <div class="grid-text">
                <span class="lbl">Telefone / Celular</span>
                <span id="valFone" style="font-weight:600">—</span>
            </div>
            
            <div class="icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75v-4.5m0 4.5h4.5m-4.5 0l6-6m-3 18c-8.284 0-15-6.716-15-15V4.5A2.25 2.25 0 014.5 2.25h1.372c.516 0 .966.351 1.091.852l1.106 4.423c.11.44-.055.902-.417 1.173l-1.293.97a17.14 17.14 0 007.092 7.092l.97-1.293c.271-.363.734-.527 1.173-.417l4.423 1.106c.5.125.852.575.852 1.091V19.5a2.25 2.25 0 01-2.25 2.25h-2.25z" />
                </svg>
            </div>
            <div class="grid-text">
                <span class="lbl">Ramal Interno</span>
                <span id="valRamal" style="font-weight:600">—</span>
            </div>
        </div>
    </div>
  </div>
<!-- Fim Cartão Flutuante -->

<?php if ($viewMode !== 'lista'): ?>
<script>
(() => {
  /* ===== SPOTLIGHT SEARCH (CTRL+K) ===== */
  const spotlightOverlay = document.getElementById('spotlight-overlay');
  const spotlightInput   = document.getElementById('spotlight-input');
  const spotlightResults = document.getElementById('spotlight-results');
  let selectedSpotlightIndex = -1;
  let spotlightItems = []; // DOM elements dos resultados

  // Coletar todos os dados dos colaboradores diretamente dos atributos data-* das tags <summary class="person">
  let allColabs = [];
  function populateSearchData() {
      allColabs = [];
      document.querySelectorAll('.org summary.person').forEach(sum => {
          if(!sum.dataset.nome) return;
          allColabs.push({
              nome: sum.dataset.nome,
              cargo: sum.dataset.cargo || '',
              departamento: sum.dataset.departamento || '',
              foto: sum.dataset.foto || '',
              element: sum
          });
      });
  }

  function openSpotlight() {
      populateSearchData();
      spotlightOverlay.classList.add('active');
      spotlightInput.value = '';
      spotlightResults.innerHTML = '';
      selectedSpotlightIndex = -1;
      setTimeout(() => spotlightInput.focus(), 50);
  }
  function closeSpotlight() {
      spotlightOverlay.classList.remove('active');
      spotlightInput.blur();
  }

  // Atalhos de Teclado
  document.addEventListener('keydown', (e) => {
      // Ctrl+K ou Cmd+K
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
          e.preventDefault();
          if (spotlightOverlay.classList.contains('active')) closeSpotlight();
          else openSpotlight();
      }
      // Esc para fechar
      if (e.key === 'Escape' && spotlightOverlay.classList.contains('active')) {
          closeSpotlight();
      }
  });

  spotlightOverlay.addEventListener('mousedown', (e) => {
      if (e.target === spotlightOverlay) closeSpotlight();
  });

  // Buscador Fuzzy/Live Search
  spotlightInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      spotlightResults.innerHTML = '';
      spotlightItems = [];
      selectedSpotlightIndex = -1;

      if (q.length < 1) return;

      const filtered = allColabs.filter(c => 
          c.nome.toLowerCase().includes(q) || 
          c.cargo.toLowerCase().includes(q) || 
          c.departamento.toLowerCase().includes(q)
      ).slice(0, 8); // Limita a 8 resultados

      if (filtered.length === 0) {
          spotlightResults.innerHTML = '<div style="padding:20px; text-align:center; color:#64748b;">Nenhum colaborador encontrado.</div>';
          return;
      }

      filtered.forEach((c, idx) => {
          const div = document.createElement('div');
          div.className = 'spotlight-item';
          
          const inits = c.nome.split(' ').map(x=>x[0]).slice(0,2).join('').toUpperCase();
          const avatarHtml = c.foto 
              ? `<img src="${c.foto}" class="ava">` 
              : `<div class="ava">${inits}</div>`;

          div.innerHTML = `
             ${avatarHtml}
             <div class="info">
                 <strong>${c.nome}</strong>
                 <span>${c.cargo} ${c.departamento ? '• '+c.departamento : ''}</span>
             </div>
          `;
          
          div.addEventListener('mouseenter', () => activateItem(idx));
          div.addEventListener('click', () => jumpToColab(c.element));

          spotlightResults.appendChild(div);
          spotlightItems.push(div);
      });
      // Ativa o primeiro por padrão
      if(spotlightItems.length > 0) activateItem(0);
  });

  // Navegação no teclado das setas
  spotlightInput.addEventListener('keydown', (e) => {
      if (spotlightItems.length === 0) return;
      if (e.key === 'ArrowDown') {
          e.preventDefault();
          let next = selectedSpotlightIndex + 1;
          if (next >= spotlightItems.length) next = 0;
          activateItem(next);
      } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          let prev = selectedSpotlightIndex - 1;
          if (prev < 0) prev = spotlightItems.length - 1;
          activateItem(prev);
      } else if (e.key === 'Enter') {
          e.preventDefault();
          if (selectedSpotlightIndex >= 0 && selectedSpotlightIndex < spotlightItems.length) {
              spotlightItems[selectedSpotlightIndex].click();
          }
      }
  });

  function activateItem(idx) {
      spotlightItems.forEach(i => i.classList.remove('selected'));
      selectedSpotlightIndex = idx;
      if(spotlightItems[idx]) {
          spotlightItems[idx].classList.add('selected');
          spotlightItems[idx].scrollIntoView({ block: 'nearest' });
      }
  }

  // Pulo 3D e expansão automática da árvore
  function jumpToColab(element) {
      closeSpotlight();
      if(!element) return;
      
      // Expande todas as tags <details> pai desse nó, subindo a árvore
      let parentDetails = element.closest('details');
      while (parentDetails) {
          parentDetails.open = true;
          parentDetails = parentDetails.parentElement.closest('details');
      }
      
      // Força um reflow imediato do layout SVG Wires antes de rolar
      if(typeof redraw === 'function') redraw();

      setTimeout(() => {
          // Centraliza a visão do viewport em cima do card escolhido
          const rect = element.getBoundingClientRect();
          const wrap = document.querySelector('.wrap');
          
          // Animação de zoom de destaque
          element.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
          element.style.transform = 'scale(1.15)';
          element.style.zIndex = '100';
          
          setTimeout(() => {
              element.style.transform = '';
              element.style.zIndex = '';
          }, 800);

          wrap.scrollTo({
              left: wrap.scrollLeft + rect.left - window.innerWidth / 2 + rect.width / 2,
              top: wrap.scrollTop + rect.top - window.innerHeight / 2 + rect.height / 2,
              behavior: 'smooth'
          });
          
          // Abre o hoverCard pra ele
          showCardFor(element);
      }, 50);
  }

})();
</script>
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

  org.addEventListener('toggle', redraw, true);
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
      s.className='inits'; s.style.fontSize='28px';
      s.textContent = (d.nome||'??').split(' ').map(x=>x[0]).slice(0,2).join('').toUpperCase();
      mini.appendChild(s);
      mini.style.border = `3px solid hsl(${nameToHue((d.nome||'').toLowerCase())},55%,68%)`;
      mini.style.background = '#fefefe';
    }

    // Título Central
    card.querySelector('.nm').textContent  = d.nome || '—';
    card.querySelector('.sub').textContent = [d.cargo||'—'].join(' • ');

    // Chips
    const chips = card.querySelector('#chips'); chips.innerHTML = '';
    if (d.empresa)      chips.append(childChip(d.empresa));
    if (d.tipo)         chips.append(childChip(d.tipo));
    if (d.admissao)     chips.append(childChip(formatDateBr(d.admissao),'📅'));

    // Ações Premium (Grid de Botões)
    const acts = card.querySelector('#actions-grid'); acts.innerHTML = '';
    
    if (d.email) {
        acts.append(premiumBtn('mailto:'+d.email, `<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>`, 'E-mail', 'btn-em'));
    }
    if (d.telefone){
      acts.append(premiumBtn(waFromPhone(d.telefone), `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.2 15.1c-.3.3-.9.8-1.6.9-.7.2-1.6.2-2.7-.3-1.2-.5-2.6-1.6-3.8-3-1.2-1.4-2-2.8-2.4-4-.4-1.2-.4-2.1-.2-2.8.2-.7.6-1.2.9-1.5.3-.3.6-.3.8-.3h.6c.2 0 .5.1.7.5l1 2c.1.3.1.5 0 .8l-.5.8c-.1.2-.1.4 0 .6.3.6.8 1.4 1.6 2.1.8.7 1.5 1.1 2.1 1.4.2.1.4.1.6 0l.9-.4c.3-.1.6 0 .8.1l1.8 1.1c.3.2.4.4.4.6 0 .2-.1.5-.3.7z"/></svg>`, 'WhatsApp', 'btn-wa'));
    }
    if (d.teams){
        acts.append(premiumBtn(d.teams, `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M9.186 4.797a2.42 2.42 0 1 0-2.86-2.448h1.178c.929 0 1.682.753 1.682 1.682zm-4.295 7.738h2.613c.929 0 1.682-.753 1.682-1.682V5.58h2.783a.7.7 0 0 1 .682.716v4.294a4.197 4.197 0 0 1-4.093 4.293c-1.618-.04-3-.99-3.667-2.35Zm10.737-9.372a1.674 1.674 0 1 1-3.349 0 1.674 1.674 0 0 1 3.349 0m-2.238 9.488-.12-.002a5.2 5.2 0 0 0 .381-2.07V6.306a1.7 1.7 0 0 0-.15-.725h1.792c.39 0 .707.317.707.707v3.765a2.6 2.6 0 0 1-2.598 2.598z"/><path d="M.682 3.349h6.822c.377 0 .682.305.682.682v6.822a.68.68 0 0 1-.682.682H.682A.68.68 0 0 1 0 10.853V4.03c0-.377.305-.682.682-.682Zm5.206 2.596v-.72h-3.59v.72h1.357V9.66h.87V5.945z"/></svg>`, 'Teams', 'btn-tm'));
    }

    // Grid Text
    setVal('#valFone',  d.telefone);
    setVal('#valRamal', d.ramal);
    setVal('#valDepto', d.departamento);
    setVal('#valObs',   d.obs);

    // Reseta abas para a primeira
    card.querySelectorAll('.tabs-nav button').forEach(b => b.classList.remove('active'));
    card.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    card.querySelector('button[data-tab="tab-sobre"]').classList.add('active');
    card.querySelector('#tab-sobre').classList.add('active');

    // Posicionamento: calcula lado com mais espaço e exibe
    placeCardNear(summary);
    card.style.display = 'block';
    // Timeout para transição suave de CSS (.show tem transição)
    setTimeout(() => card.classList.add('show'), 10);
  }

  // Lógica das Abas do Modal
  card.querySelectorAll('.tabs-nav button').forEach(btn => {
      btn.addEventListener('click', (e) => {
          // Tabs nav
          card.querySelectorAll('.tabs-nav button').forEach(b => b.classList.remove('active'));
          e.currentTarget.classList.add('active');
          // Panels
          card.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
          const target = e.currentTarget.getAttribute('data-tab');
          card.querySelector('#'+target).classList.add('active');
      });
  });

  function childChip(text, icon){
    const s = document.createElement('span'); s.className='chip-tag';
    if (icon){ s.textContent = icon+' '+text; } else { s.textContent = text; }
    return s;
  }
  function premiumBtn(href, svgContent, label, extraClass){
    const a = document.createElement('a'); a.className='btn-premium' + (extraClass?(' '+extraClass):''); 
    a.href = href; a.target = (href.startsWith('http')?'_blank':'_self'); a.rel='noopener';
    const icoWrap = document.createElement('div'); icoWrap.className = 'ico-wrapper';
    icoWrap.innerHTML = svgContent;
    a.appendChild(icoWrap);
    a.appendChild(document.createTextNode(label));
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
</body>
</html>
