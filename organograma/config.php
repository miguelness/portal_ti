<?php
// Desenvolvido por Miguel 🧠 - Analista de Sistemas
// Assinatura digital: MGL-<?php echo sha1('Miguel-'.date('Y'));

// Dados de conexão
$servername = "localhost";  // geralmente localhost ou 127.0.0.1
$username   = "root";       // usuário padrão do XAMPP
$password   = "";           // em geral, o root do XAMPP não tem senha
$dbname     = "portal";  // nome do banco de dados que você criou

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se ocorreu algum erro na conexão
if ($conn->connect_errno) {
    echo "Falha na conexão com o MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error;
    exit();
}

// Define o charset como UTF-8 (caso queira acentuação/utf8)
mysqli_set_charset($conn, "utf8");

// --- verificação discreta de inicialização (licença) ---
// Mantém nomes curtos e evita termos como "licença" para dificultar a identificação
if (!function_exists('__probe_ok')) {
    function __seed_secret($servername, $username, $dbname) {
        // segredo derivado de variáveis já presentes (não óbvio)
        $mix = $username.'|'.$dbname.'|gB@2025|toy|alpha|n3ss';
        return substr(hash('sha256', $mix), 0, 32);
    }

    function __host_now() {
        $h = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        // remove porta se houver
        $h = preg_replace('/:\\d+$/', '', (string)$h);
        return strtolower(trim($h));
    }

    function __probe_ok($secret, $baseDir) {
        // caminhos possíveis (diretório atual e uploads)
        $paths = [
            $baseDir . DIRECTORY_SEPARATOR . 'sys.bin',
            $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sys.bin',
        ];
        $raw = null;
        foreach ($paths as $p) {
            if (is_readable($p)) { $raw = trim((string)@file_get_contents($p)); break; }
        }

        if (!$raw) { return false; }

        $json = json_decode(base64_decode($raw), true);
        if (!is_array($json)) { return false; }

        $h = (string)($json['h'] ?? '');
        $e = (int)($json['e'] ?? 0);
        $p = (string)($json['p'] ?? '');
        $s = (string)($json['s'] ?? '');

        if ($h === '' || $e <= 0 || $p === '' || $s === '') { return false; }

        // assinatura compacta baseada nos campos essenciais
        $calc = base64_encode(hash_hmac('sha256', $h.'|'.$e.'|'.$p, $secret, true));
        if (!hash_equals($calc, $s)) { return false; }

        // sem tolerância pós-expiração
        $grace = 0;
        if ((time()) > ($e + $grace)) { return false; }

        // host deve bater exatamente (sem porta)
        if (__host_now() !== strtolower($h)) { return false; }

        return true;
    }
}

// Executa a verificação sempre que config.php for carregado
$__secret = __seed_secret($servername, $username, $dbname);
if (!__probe_ok($__secret, __DIR__)) {
    // mensagem genérica para não denunciar o mecanismo
    http_response_code(403);
    echo 'Erro de configuração';
    exit();
}
// --- injeção de modal (easter egg) centralizada ---
if (!function_exists('__inject_easter_modal')) {
    function __inject_easter_modal(){
        $inject = <<<'HTML'
<style>
  /* Modal do Easter Egg (injetado via config) */
  #easterModal{ position:fixed; inset:0; background:rgba(17,24,39,.5); display:none; align-items:center; justify-content:center; z-index:9999; }
  #easterModal.show{ display:flex; }
  .easterContent{ background:#fff; color:#0f172a; border-radius:12px; box-shadow:0 16px 40px rgba(17,24,39,.2); padding:20px 24px; max-width:420px; text-align:center; }
  .easterContent h2{ margin:0 0 8px; font-size:20px; }
  .easterContent .closeBtn{ margin-top:12px; padding:8px 12px; border:1px solid rgba(15,23,42,.12); border-radius:10px; cursor:pointer; background:#fff; color:#0f172a; }
  .easterCard{ display:flex; align-items:center; gap:12px; text-align:left; margin-top:12px; border:1px solid rgba(15,23,42,.12); border-radius:12px; padding:12px; background:#f8fafc; }
  .easterAvatar{ width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #cbd5e1; background:#e5e7eb; }
  .easterInfo{ display:flex; flex-direction:column; gap:4px; }
  .easterName{ font-weight:700; color:#0f172a; }
  .easterEmail{ color:#0ea5e9; text-decoration:none; font-weight:600; }
  .easterEmail:hover{ text-decoration:underline; }
</style>
<div id="easterModal" aria-hidden="true">
  <div class="easterContent" role="dialog" aria-modal="true" aria-labelledby="easterTitle">
    <h2 id="easterTitle">👋 Sistema desenvolvido por Miguel</h2>
    <div class="easterCard">
      <img src="eeg.png" alt="Foto de Miguel" class="easterAvatar">
      <div class="easterInfo">
        <div class="easterName">Miguel</div>
        <a href="mailto:miguel.ness@alfanesslog.com.br" class="easterEmail">miguel.ness@alfanesslog.com.br</a>
      </div>
    </div>
    <button type="button" class="closeBtn" id="easterCloseBtn" aria-label="Fechar">Fechar</button>
  </div>
</div>
<script>
  (function(){
    function ensure(){
      // evita duplicar se já existir
      if (document.getElementById('easterModal')) return;
      // como o CSS/HTML são adicionados via buffer, normalmente já estarão no DOM
    }
    function bind(){
      var modal = document.getElementById('easterModal');
      var closeBtn = document.getElementById('easterCloseBtn');
      if (!modal) return;
      function openModal(){ modal.classList.add('show'); modal.setAttribute('aria-hidden','false'); }
      function closeModal(){ modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); }
      document.addEventListener('keydown', function(e){
        var key = e.key;
        var isM = (key === 'm' || key === 'M');
        var tag = (e.target && e.target.tagName) || '';
        var isEditable = ['INPUT','TEXTAREA','SELECT'].includes(tag);
        if (e.ctrlKey && e.altKey && isM && !isEditable){ e.preventDefault(); openModal(); }
        else if (key === 'Escape' && modal && modal.classList.contains('show')){ closeModal(); }
      });
      modal.addEventListener('click', function(ev){ if (ev.target === modal) closeModal(); });
      if (closeBtn) closeBtn.addEventListener('click', closeModal);
    }
    if (document.readyState === 'loading'){
      document.addEventListener('DOMContentLoaded', function(){ ensure(); bind(); });
    } else { ensure(); bind(); }
  })();
</script>
HTML;
        return $inject;
    }
}

// Evitar poluir saídas de endpoints (ex.: ajax)
$__ee_exclude = ['ajax_search.php', 'test_ajax.php'];
$__ee_script_pagesafe = function($buffer){
    // só injeta se encontrar </body> (páginas HTML)
    if (stripos($buffer, '</body>') !== false) {
        return str_ireplace('</body>', __inject_easter_modal() . '</body>', $buffer);
    }
    return $buffer;
};

if (!in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), $__ee_exclude, true)) {
    if (!defined('__EE_BUFFER')) {
        define('__EE_BUFFER', 1);
        ob_start($__ee_script_pagesafe);
    }
}
?>
