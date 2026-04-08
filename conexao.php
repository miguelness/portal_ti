<?php
require_once __DIR__ . '/vendor/autoload.php';

// Carrega o .env se o arquivo existir
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Dados de conexão via Variáveis de Ambiente
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'portal';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

// Cria a conexão (MySQLi)
$conn = new mysqli($host, $username, $password, $dbname);

// Verifica se ocorreu algum erro na conexão
if ($conn->connect_errno) {
    echo "Falha na conexão com o MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error;
    exit();
}

// Define o charset como UTF-8 (caso queira acentuação/utf8)
mysqli_set_charset($conn, "utf8");
?>
