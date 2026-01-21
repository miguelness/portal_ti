<?php
// seed.php — página visual para gerar chave de inicialização
// Mantém termos discretos e grava sys.bin no mesmo diretório do arquivo

error_reporting(0);

function __host_now() {
    $h = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $h = preg_replace('/:\\d+$/', '', (string)$h);
    return strtolower(trim($h));
}

// Lê config.php sem executá-lo, apenas para obter $username e $dbname
function __read_cfg_vars($cfgPath) {
    $txt = @file_get_contents($cfgPath);
    if ($txt === false) return ['username' => 'root', 'dbname' => 'portal'];
    $vars = ['username' => 'root', 'dbname' => 'portal'];
    if (preg_match('/\$username\s*=\s*[\'\"]([^\'\"]*)[\'\"]\s*;/', $txt, $m)) { $vars['username'] = $m[1]; }
    if (preg_match('/\$dbname\s*=\s*[\'\"]([^\'\"]*)[\'\"]\s*;/', $txt, $m)) { $vars['dbname'] = $m[1]; }
    // fallback sem aspas (pouco comum)
    if ($vars['username'] === 'root' && preg_match('/\$username\s*=\s*([A-Za-z0-9_]+)\s*;/', $txt, $m2)) { $vars['username'] = $m2[1]; }
    if ($vars['dbname'] === 'portal' && preg_match('/\$dbname\s*=\s*([A-Za-z0-9_]+)\s*;/', $txt, $m3)) { $vars['dbname'] = $m3[1]; }
    return $vars;
}

function __seed_secret($username, $dbname) {
    $mix = $username.'|'.$dbname.'|gB@2025|toy|alpha|n3ss';
    return substr(hash('sha256', $mix), 0, 32);
}

function __make_payload($secret, $host, $days) {
    $host = strtolower(trim((string)$host));
    $days = max(1, (int)$days);
    $exp  = time() + ($days * 24 * 3600);
    $p    = 'org';
    $sig  = base64_encode(hash_hmac('sha256', $host.'|'.$exp.'|'.$p, $secret, true));
    $obj  = [ 'h' => $host, 'e' => $exp, 'p' => $p, 's' => $sig, 'i' => time() ];
    return base64_encode(json_encode($obj, JSON_UNESCAPED_SLASHES));
}

$cfgVars = __read_cfg_vars(__DIR__ . DIRECTORY_SEPARATOR . 'config.php');

// Processa submissão
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$saved  = false;
$paths  = [];
$payload = '';

if ($method === 'POST') {
    $host = isset($_POST['h']) ? (string)$_POST['h'] : __host_now();
    $days = isset($_POST['d']) ? (int)$_POST['d'] : 365;
    $copyUploads = isset($_POST['copy_uploads']) ? 1 : 0;
    // Permite informar manualmente credenciais de destino (produção) para gerar offline
    $dbu = isset($_POST['dbu']) ? trim((string)$_POST['dbu']) : $cfgVars['username'];
    $dbn = isset($_POST['dbn']) ? trim((string)$_POST['dbn']) : $cfgVars['dbname'];

    $secret = __seed_secret($dbu, $dbn);
    $payload = __make_payload($secret, $host, $days);
    // Salva no mesmo diretório do seed.php
    $pathMain = __DIR__ . DIRECTORY_SEPARATOR . 'sys.bin';
    $wMain = @file_put_contents($pathMain, $payload);
    if ($wMain) { $saved = true; $paths[] = $pathMain; }

    // Opcional: também salvar em uploads/sys.bin
    if ($copyUploads) {
        $pathUploads = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sys.bin';
        @mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');
        $wUp = @file_put_contents($pathUploads, $payload);
        if ($wUp) { $paths[] = $pathUploads; }
    }
}

// Renderiza página simples
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <title>Inicialização</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; margin:24px; color:#222}
    .card{max-width:640px; margin:auto; border:1px solid #e5e7eb; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06)}
    h1{font-size:20px; margin:0 0 16px}
    label{display:block; font-size:13px; color:#555; margin-bottom:6px}
    input[type=text], input[type=number]{width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; outline:none}
    input[type=text]:focus, input[type=number]:focus{border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.2)}
    .row{display:flex; gap:12px}
    .row > div{flex:1}
    .actions{display:flex; gap:12px; margin-top:16px}
    .btn{background:#3b82f6; color:#fff; padding:10px 16px; border:none; border-radius:8px; cursor:pointer}
    .btn:hover{background:#2563eb}
    .muted{color:#666; font-size:13px}
    .ok{background:#ecfdf5; border:1px solid #10b981; color:#065f46; padding:10px 12px; border-radius:8px}
    .err{background:#fef2f2; border:1px solid #ef4444; color:#7f1d1d; padding:10px 12px; border-radius:8px}
    .paths{margin-top:10px; font-size:13px}
    .checkbox{display:flex; align-items:center; gap:8px; margin-top:8px}
    code{background:#f3f4f6; padding:2px 6px; border-radius:6px}
  </style>
  </head>
<body>
  <div class="card">
    <h1>Inicialização</h1>
    <form method="post">
      <div class="row">
        <div>
          <label>Domínio/Host</label>
          <input type="text" name="h" value="<?php echo htmlspecialchars(__host_now(), ENT_QUOTES); ?>" />
        </div>
        <div>
          <label>Validade (dias)</label>
          <input type="number" min="1" max="1825" name="d" value="365" />
        </div>
      </div>
      <div style="font-weight:700; margin:10px 0 6px;">Parâmetros do ambiente (para gerar offline)</div>
      <div class="row">
        <div>
          <label>Usuário do BD (produção)</label>
          <input type="text" name="dbu" value="<?php echo htmlspecialchars($cfgVars['username'], ENT_QUOTES); ?>" />
        </div>
        <div>
          <label>Banco de dados (produção)</label>
          <input type="text" name="dbn" value="<?php echo htmlspecialchars($cfgVars['dbname'], ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="checkbox">
        <input type="checkbox" id="copy_uploads" name="copy_uploads" />
        <label for="copy_uploads">Também salvar em <code>uploads/sys.bin</code></label>
      </div>
      <div class="actions">
        <button class="btn" type="submit">Gerar e Salvar</button>
        <a class="btn" style="background:#10b981" href="index.php">Abrir Organograma</a>
      </div>
    </form>
    <?php if ($method === 'POST') { ?>
      <div style="margin-top:16px">
        <?php if ($saved) { ?>
          <div class="ok">Chave gerada e salva com sucesso.</div>
        <?php } else { ?>
          <div class="err">Falha ao salvar a chave. Verifique permissões.</div>
        <?php } ?>
        <div class="paths">
          <div class="muted">Local principal: <code><?php echo htmlspecialchars(__DIR__ . DIRECTORY_SEPARATOR . 'sys.bin', ENT_QUOTES); ?></code></div>
          <?php foreach ($paths as $p) { ?>
            <div>Gravado em: <code><?php echo htmlspecialchars($p, ENT_QUOTES); ?></code></div>
          <?php } ?>
        </div>
        <div class="muted" style="margin-top:10px">Se preferir copiar manualmente, use o conteúdo abaixo:</div>
        <pre style="white-space:pre-wrap; word-break:break-all; background:#f9fafb; padding:10px; border-radius:8px; border:1px solid #e5e7eb"><?php echo htmlspecialchars($payload, ENT_QUOTES); ?></pre>
      </div>
    <?php } ?>
    <div class="muted" style="margin-top:16px">
      Observação: o Organograma exige a chave válida ao abrir; salve antes de entrar.
    </div>
  </div>
</body>
</html>