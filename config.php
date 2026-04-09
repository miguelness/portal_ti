require_once __DIR__ . '/vendor/autoload.php';

// Carrega o .env se o arquivo existir
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configurações do Banco de Dados via .ENV
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'portal');

// Configurações de Segurança
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// Configurações de Fuso Horário
define('DEFAULT_TIMEZONE', 'America/Sao_Paulo');

// Configurações de Debug (desativar em produção)
define('DEBUG_MODE', false);

// Configurações de Email (se necessário)
define('EMAIL_FROM', 'noreply@portal.local');
define('EMAIL_ADMIN', 'admin@portal.local');

// Configurações de Upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Configurações de Sistema
define('SYSTEM_NAME', 'Portal');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_URL', 'http://localhost/portal/');
?>