<?php
// admin/config.php
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega o .env se o arquivo existir
if (file_exists(__DIR__ . '/../.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    } catch (Exception $e) {
        // Fallback manual se o phpdotenv falhar por algum motivo
        $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim(trim($value), '"\'');
            }
        }
    }
}

// Dados de conexão via Variáveis de Ambiente
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'portal';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

// Configura fuso horário para São Paulo
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Sincroniza o timezone do MySQL com o do PHP
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Garante sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega acessos do usuário
if (!isset($user_accesses)) {
    $user_accesses = [];
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT a.access_name FROM user_access ua
                               JOIN accesses a ON ua.access_id = a.id
                               WHERE ua.user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Define categorias permitidas com base nos acessos
$categorias_disponiveis = [];

if (in_array('Feeds TI', $user_accesses)) {
    $categorias_disponiveis[] = 'Maxtrade';
    $categorias_disponiveis[] = 'Portal';
}

if (in_array('Feeds RH', $user_accesses)) {
    $categorias_disponiveis[] = 'RH';
}

$categorias_disponiveis = array_unique($categorias_disponiveis);

/**
 * Recupera um valor de configuração da tabela sys_config
 */
function getSysConfig($chave, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT valor FROM sys_config WHERE chave = ? LIMIT 1");
        $stmt->execute([$chave]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['valor'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
/**
 * Grava logs de execução do Cron
 */
function logCron($msg) {
    try {
        $logFile = __DIR__ . '/../db/cron_log.txt';
        $date = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$date] $msg\n", FILE_APPEND);
    } catch (Exception $e) {
        // Silencioso se falhar o log
    }
}
?>
